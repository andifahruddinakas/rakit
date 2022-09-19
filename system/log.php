<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Log
{
    /**
     * Nama file log.
     *
     * @var string
     */
    protected static $channel;

    /**
     * Set nama file tempat menyimpan log.
     *
     * @param string|null $file
     *
     * @return void
     */
    public static function channel($name = null)
    {
        $name = basename($name);
        $name = Str::replace_last('.log', '', Str::replace_last('.php', '', basename($name)));
        static::$channel = $name.'.log.php';
    }

    /**
     * Tulis log info.
     *
     * @param string     $message
     * @param mixed|null $data
     */
    public static function info($message, $data = null)
    {
        static::write('info', $message, $data);
    }

    /**
     * Tulis log warning.
     *
     * @param string     $message
     * @param mixed|null $data
     */
    public static function warning($message, $data = null)
    {
        static::write('warning', $message, $data);
    }

    /**
     * Tulis log error.
     *
     * @param string     $message
     * @param mixed|null $data
     */
    public static function error($message, $data = null)
    {
        static::write('error', $message, $data);
    }

    /**
     * Tulis pesan ke file log.
     *
     * @param string     $type
     * @param string     $message
     * @param mixed|null $data
     */
    protected static function write($type, $message, $data = null)
    {
        if (! is_string($message)) {
            throw new \Exception(sprintf(
                'The error message should be a string. %s given.',
                gettype($message)
            ));
        }

        $message .= ($data === null) ? null : Foundation\Oops\Dumper::toText($data, ['truncate' => PHP_INT_MAX]);

        if (Event::exists('rakit.log')) {
            Event::fire('rakit.log', [$type, $message]);
        }

        $message = static::format($type, $message);
        $path = path('storage').'logs'.DS.(static::$channel ? static::$channel : date('Y-m-d'));
        $path .= '.log.php';

        if (is_file($path)) {
            file_put_contents($path, $message, LOCK_EX | FILE_APPEND);
        } else {
            file_put_contents($path, $message, LOCK_EX);
        }
    }

    /**
     * Format pesan logging.
     *
     * @param string $type
     * @param string $message
     *
     * @return string
     */
    protected static function format($type, $message)
    {
        $context = Foundation\Oops\Debugger::$productionMode ? 'production' : 'development';
        return '['.date('Y-m-d H:i:s').'] '.$context.'.'.strtoupper($type).': '.$message.PHP_EOL;
    }
}
