<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct script access.');

class File extends Driver
{
    /**
     * Berisi path file cache.
     *
     * @var string
     */
    protected $path;

    /**
     * Buat instance driver file baru.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
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
        $key = $this->naming($key);

        if (! is_file($this->path.$key)) {
            return;
        }

        $cache = file_get_contents($this->path.$key);
        $cache = $this->unguard($cache);

        if (time() >= substr($cache, 0, 10)) {
            return $this->forget($key);
        }

        return unserialize(substr($cache, 10));
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
        $key = $this->naming($key);

        if ($minutes <= 0) {
            return;
        }

        $value = $this->guard($this->expiration($minutes).serialize($value));

        file_put_contents($this->path.$key, $value, LOCK_EX);
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
        $key = $this->naming($key);

        if (is_file($this->path.$key)) {
            unlink($this->path.$key);
        }
    }

    /**
     * Helper untuk format nama file cache.
     *
     * @param string $key
     *
     * @return string
     */
    protected function naming($key)
    {
        return md5((string) $key).'.cache.php';
    }

    /**
     * Helper untuk proteksi akses file via browser.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function guard($value)
    {
        $value = (string) $value;
        $guard = "<?php defined('DS') or exit('No direct script access.'); ?>";
        $value = $guard.PHP_EOL.PHP_EOL.$value;

        return $value;
    }

    /**
     * Helper untuk buang proteksi akses file via browser.
     * (Kebalikan dari method guard).
     *
     * @param string $value
     *
     * @return string
     */
    protected static function unguard($value)
    {
        $value = (string) $value;
        $value = ltrim($value, "<?php defined('DS') or exit('No direct script access.'); ?>".PHP_EOL.PHP_EOL);

        return $value;
    }
}