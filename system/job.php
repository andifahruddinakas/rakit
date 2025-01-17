<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Job extends Event
{
    /**
     * Tambahkan sebuah job.
     *
     * @param string      $name
     * @param array       $payloads
     * @param string|null $scheduled_at
     *
     * @return bool
     */
    public static function add($name, array $payloads = [], $scheduled_at = null)
    {
        $config = Config::get('job');
        $name = Str::slug($name);
        $id = Database::table($config['table'])->insert_get_id([
            'name' => $name,
            'payloads' => serialize($payloads),
            'scheduled_at' => Date::make($scheduled_at)->format('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        static::log(sprintf('Job added: %s - #%s', $name, $id));
    }

    /**
     * Hapus job berdasarkan nama.
     *
     * @param string $name
     *
     * @return bool
     */
    public function forget($name)
    {
        $config = Config::get('job');
        $name = Str::slug($name);

        Event::fire('rakit.jobs.forget: '.$name);

        $jobs = Database::table($config['table'])->where('name', $name)->get();

        if (! empty($jobs)) {
            foreach ($jobs as $job) {
                Database::table($config['table'])->where('id', $job->id)->delete();
            }
        }

        $jobs = Database::table($config['failed_table'])->where('name', $name)->get();

        if (! empty($jobs)) {
            foreach ($jobs as $job) {
                Database::table($config['failed_table'])->where('id', $job->id)->delete();
            }
        }

        static::log(sprintf('Jobs deleted: %s', $name));
    }

    /**
     * Jalankan antrian job di database.
     *
     * @param string $name
     *
     * @return bool
     */
    public static function run($name)
    {
        $config = Config::get('job');
        $name = Str::slug($name);

        if (! $config['enabled']) {
            static::log('Job is disabled', 'error');
            return;
        }

        if (empty($name)) {
            static::log('Job is empty', 'error');
            return;
        }

        if ($config['cli_only'] && ! Request::cli()) {
            static::log('Job is set to CLI only', 'error');
            return;
        }

        $jobs = Database::table($config['table'])
            ->where('name', $name)
            ->where('scheduled_at', '<=', date('Y-m-d H:i:s'))
            ->order_by('created_at', 'ASC')
            ->take($config['max_job'])
            ->get();

        if (empty($jobs)) {
            static::log('Job is empty');
        } else {
            foreach ($jobs as $job) {
                try {
                    Event::fire('rakit.jobs.run: '.$job->name, unserialize($job->payloads));
                    Database::table($config['table'])->where('id', $job->id)->delete();
                    static::log(sprintf('Job executed: %s - #%s', $job->name, $job->id));
                } catch (\Throwable $e) {
                    $exception = get_class($e)
                        .(('' === $e->getMessage()) ? '' : ': '.$e->getMessage())
                        .' in '.$e->getFile().':'.$e->getLine()."\nStack trace:\n".$e->getTraceAsString();
                    Database::table($config['failed_table'])->insert([
                        'job_id' => $job->id,
                        'name' => $job->name,
                        'payload' => serialize($job->payloads),
                        'exception' => $e,
                        'failed_at' => date('Y-m-d H:i:s'),
                    ]);
                    static::log(sprintf('Job failed: %s - #%s', $job->name, $job->id));
                } catch (\Exception $e) {
                    $exception = get_class($e)
                        .(('' === $e->getMessage()) ? '' : ': '.$e->getMessage())
                        .' in '.$e->getFile().':'.$e->getLine()."\nStack trace:\n".$e->getTraceAsString();
                    Database::table($config['failed_table'])->insert([
                        'job_id' => $job->id,
                        'name' => $job->name,
                        'payload' => serialize($job->payloads),
                        'exception' => $e,
                        'failed_at' => date('Y-m-d H:i:s'),
                    ]);
                    static::log(sprintf('Job failed: %s - #%s', $job->name, $job->id));
                }
            }
        }
    }

    /**
     * Jalankan semua job di database.
     *
     * @return bool
     */
    public static function runall()
    {
        $config = Config::get('job');

        if (! $config['enabled']) {
            static::log('Job is disabled', 'error');
            return false;
        }

        if ($config['cli_only'] && ! Request::cli()) {
            static::log('Job is set to CLI only', 'error');
            return false;
        }

        $jobs = Database::table($config['table'])
            ->where('scheduled_at', '<=', date('Y-m-d H:i:s'))
            ->order_by('created_at', 'ASC')
            ->take($config['max_job'])
            ->get();

        if (empty($jobs)) {
            static::log('Job is empty');
        } else {
            foreach ($jobs as $job) {
                try {
                    Event::fire('rakit.jobs.run: '.$job->name, unserialize($job->payloads));
                    Database::table($config['table'])->where('id', $job->id)->delete();
                    static::log(sprintf('Job executed: %s - #%s', $job->name, $job->id));
                } catch (\Throwable $e) {
                    static::log(sprintf('Job failed: %s - #%s', $job->name, $job->id));
                    return false;
                } catch (\Exception $e) {
                    static::log(sprintf('Job failed: %s - #%s', $job->name, $job->id));
                    return false;
                }
            }
        }
    }

    private static function log($message, $type = 'info')
    {
        if (Config::get('job.logging')) {
            Log::channel('jobs');
            Log::{$type}($message);
            Log::channel(null);
        }

        if (Request::cli()) {
            echo '['.date('Y-m-d H:i:s').'] ['.strtoupper((string) $type).'] '.$message.PHP_EOL;
        }
    }
}
