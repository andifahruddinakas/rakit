<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct script access.');

use System\Config;
use System\Database as DB;

class Database extends Driver
{
    /**
     * Nama key cache dari file konfigurasi.
     *
     * @var string
     */
    protected $key;

    /**
     * Buat instance driver database baru.
     *
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * Cek apakah item ada di cache.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return ! is_null($this->get($key));
    }

    /**
     * Ambil item dari driver cache.
     *
     * @param string $key
     *
     * @return mixed
     */
    protected function retrieve($key)
    {
        $cache = $this->table()->where('key', '=', $this->key.$key)->first();

        if (! is_null($cache)) {
            if (time() >= $cache->expiration) {
                return $this->forget($key);
            }

            return unserialize($cache->value);
        }
    }

    /**
     * Simpan item ke cache untuk beberapa menit.
     *
     * <code>
     *
     *      // Simpan sebuah item ke cache selama 15 menit.
     *      Cache::put('name', 'Budi', 15);
     *
     * </code>
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $minutes
     */
    public function put($key, $value, $minutes)
    {
        $key = $this->key.$key;
        $value = serialize($value);
        $expiration = $this->expiration($minutes);

        try {
            $this->table()->insert(compact('key', 'value', 'expiration'));
        } catch (\Throwable $e) {
            $this->table()->where('key', '=', $key)->update(compact('value', 'expiration'));
        } catch (\Exception $e) {
            $this->table()->where('key', '=', $key)->update(compact('value', 'expiration'));
        }
    }

    /**
     * Simpan item ke cache untuk selamanya (atau 5 tahun).
     *
     * @param string $key
     * @param mixed  $value
     */
    public function forever($key, $value)
    {
        return $this->put($key, $value, 2628000);
    }

    /**
     * Hapus item dari cache.
     *
     * @param string $key
     */
    public function forget($key)
    {
        $this->table()->where('key', '=', $this->key.$key)->delete();
    }

    /**
     * Ambil query builder untuk tabel di database.
     *
     * @return System\Database\Query
     */
    protected function table()
    {
        $config = Config::get('cache.database');

        return DB::connection($config['connection'])->table($config['table']);
    }
}