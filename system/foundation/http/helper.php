<?php

namespace System\Foundation\Http;

defined('DS') or exit('No direct script access.');

class Helper extends Header
{
    const COOKIES_FLAT = 'flat';
    const COOKIES_ARRAY = 'array';
    const DISPOSITION_ATTACHMENT = 'attachment';
    const DISPOSITION_INLINE = 'inline';

    protected $computedCacheControl = [];
    protected $cookies = [];

    /**
     * Konstruktor.
     *
     * @param array $headers
     */
    public function __construct(array $headers = [])
    {
        parent::__construct($headers);

        if (! isset($this->headers['cache-control'])) {
            $this->set('cache-control', '');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $string = '';
        $cookies = $this->getCookies();

        foreach ($cookies as $cookie) {
            $string .= 'Set-Cookie: '.$cookie."\r\n";
        }

        return parent::__toString().$string;
    }

    /**
     * {@inheritdoc}
     */
    public function replace(array $headers = [])
    {
        parent::replace($headers);

        if (! isset($this->headers['cache-control'])) {
            $this->set('cache-control', '');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $values, $replace = true)
    {
        parent::set($key, $values, $replace);

        $keys = ['cache-control', 'etag', 'last-modified', 'expires'];

        if (in_array(strtr(strtolower((string) $key), '_', '-'), $keys)) {
            $computed = $this->computeCacheControlValue();
            $this->headers['cache-control'] = [$computed];
            $this->computedCacheControl = $this->parseCacheControl($computed);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        parent::remove($key);

        if ('cache-control' === strtr(strtolower((string) $key), '_', '-')) {
            $this->computedCacheControl = [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheControlDirective($key)
    {
        return array_key_exists($key, $this->computedCacheControl);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheControlDirective($key)
    {
        return array_key_exists($key, $this->computedCacheControl)
            ? $this->computedCacheControl[$key]
            : null;
    }

    /**
     * Sets sebuah cookie.
     *
     * @param Cookie $cookie
     */
    public function setCookie(Cookie $cookie)
    {
        $this->cookies[$cookie->getDomain()][$cookie->getPath()][$cookie->getName()] = $cookie;
    }

    /**
     * Hapus cookie berdasarkan namanya
     * (cookie di browser tidak akan dihapus).
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     */
    public function removeCookie($name, $path = '/', $domain = null)
    {
        if (null === $path) {
            $path = '/';
        }

        unset($this->cookies[$domain][$path][$name]);

        if (empty($this->cookies[$domain][$path])) {
            unset($this->cookies[$domain][$path]);

            if (empty($this->cookies[$domain])) {
                unset($this->cookies[$domain]);
            }
        }
    }

    /**
     * Mereturn seluruh data cookie.
     *
     * @param string $format
     *
     * @return array
     */
    public function getCookies($format = self::COOKIES_FLAT)
    {
        if (! in_array($format, [self::COOKIES_FLAT, self::COOKIES_ARRAY])) {
            throw new \InvalidArgumentException(sprintf(
                'Format "%s" invalid (%s).',
                $format,
                implode(', ', [self::COOKIES_FLAT, self::COOKIES_ARRAY])
            ));
        }

        if (self::COOKIES_ARRAY === $format) {
            return $this->cookies;
        }

        $flattened = [];

        foreach ($this->cookies as $path) {
            foreach ($path as $cookies) {
                foreach ($cookies as $cookie) {
                    $flattened[] = $cookie;
                }
            }
        }

        return $flattened;
    }

    /**
     * Hapus cookie di browser berdasarkan namanya.
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     */
    public function clearCookie($name, $path = '/', $domain = null)
    {
        $this->setCookie(new Cookie($name, null, 1, $path, $domain));
    }

    /**
     * Buat header content-disposition.
     *
     * @param string $disposition
     * @param string $filename
     * @param string $filenameFallback
     *
     * @return string
     */
    public function makeDisposition($disposition, $filename, $filenameFallback = '')
    {
        if (! in_array($disposition, [self::DISPOSITION_ATTACHMENT, self::DISPOSITION_INLINE])) {
            throw new \InvalidArgumentException(sprintf(
                "The disposition must be either '%s' or '%s'.",
                self::DISPOSITION_ATTACHMENT,
                self::DISPOSITION_INLINE
            ));
        }

        if ('' === $filenameFallback) {
            $filenameFallback = $filename;
        }

        if (preg_match('/[^\x00-\x7F]/', $filenameFallback)) {
            throw new \InvalidArgumentException(
                'The filename fallback must only contain ASCII characters.'
            );
        }

        if (false !== strpos((string) $filenameFallback, '%')) {
            throw new \InvalidArgumentException(
                "The filename fallback cannot contain the '%' character."
            );
        }

        if (false !== strpos((string) $filename, '/')
        || false !== strpos((string) $filename, '\\')
        || false !== strpos((string) $filenameFallback, '/')
        || false !== strpos((string) $filenameFallback, '\\')) {
            throw new \InvalidArgumentException(
                "The filename and the fallback cannot contain the '/' and '\' characters."
            );
        }

        $filenameFallback = str_replace('"', '\\"', $filenameFallback);
        $output = $disposition.'; filename="'.$filenameFallback.'"';

        if ($filename !== $filenameFallback) {
            $output .= sprintf("; filename*=utf-8''%s", rawurlencode($filename));
        }

        return $output;
    }

    /**
     * Mereturn value yang header cache-control yang telah dikakulasi dan
     * di modifikasi ke bentuk yang lebih masuk akal.
     *
     * @return string
     */
    protected function computeCacheControlValue()
    {
        if (! $this->cacheControl && ! $this->has('ETag')
        && ! $this->has('Last-Modified')
        && ! $this->has('Expires')) {
            return 'no-cache';
        }

        if (! $this->cacheControl) {
            return 'private, must-revalidate';
        }

        $header = $this->getCacheControlHeader();

        if (isset($this->cacheControl['public'])
        || isset($this->cacheControl['private'])) {
            return $header;
        }

        if (! isset($this->cacheControl['s-maxage'])) {
            return $header.', private';
        }

        return $header;
    }
}
