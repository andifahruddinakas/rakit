<?php

namespace System;

defined('DS') or exit('No direct script access.');

/*
|--------------------------------------------------------------------------
| Buat / Baca Rakit Key
|--------------------------------------------------------------------------
| Pastikan file key.php sudah ada di base path, buat jika belum ada.
*/

if (is_file($path = path('rakit_key'))) {
    $dir = path('system').'foundation'.DS.'oops'.DS.'assets'.DS.'debugger'.DS.'key'.DS;

    if (! is_readable(dirname((string) $path))) {
        http_response_code(500);
        require $dir.'unreadable.phtml';

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        exit;
    }

    if (strlen((string) require $path) < 10) {
        http_response_code(500);
        require $dir.'too-short.phtml';

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        exit;
    }
} else {
    $path = path('rakit_key');

    if (! is_writable(dirname((string) $path))) {
        http_response_code(500);
        require $dir.'unwritable.phtml';

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        exit;
    }

    try {
        if (isset($_SERVER['HTTP_COOKIE'])) {
            $cookies = explode(';', $_SERVER['HTTP_COOKIE']);

            foreach ($cookies as $cookie) {
                $parts = explode('=', $cookie);
                setcookie(trim($parts[0]), '', time() - 2628000);
                setcookie(trim($parts[0]), '', time() - 2628000, '/');
            }
        }

        $key = bin2hex(openssl_random_pseudo_bytes(rand(5, 10)));
        file_put_contents(
            path('rakit_key'),
            '<?php'.PHP_EOL.PHP_EOL
            .'defined(\'DS\') or exit(\'No direct script access.\');'.PHP_EOL.PHP_EOL
            .'/*'.PHP_EOL
            .'|--------------------------------------------------------------------------'.PHP_EOL
            .'| Application Key'.PHP_EOL
            .'|--------------------------------------------------------------------------'.PHP_EOL
            .'|'.PHP_EOL
            .'| File ini (key.php) dibuat otomatis oleh rakit. Salin file ini ke tempat'.PHP_EOL
            .'| yang aman karena file ini adalah kunci untuk membuka aplikasi anda.'.PHP_EOL
            .'|'.PHP_EOL
            .'| Jika terjadi error "Hash verification Failed", silahkan muat ulang halaman.'.PHP_EOL
            .'|'.PHP_EOL
            .'*/'.PHP_EOL
            .PHP_EOL
            .sprintf('return \'%s\';', $key).PHP_EOL
        );
    } catch (\Throwable $e) {
        require $dir.'unwritable.phtml';

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        exit;
    } catch (\Exception $e) {
        http_response_code(500);
        require $dir.'unwritable.phtml';

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        exit;
    }
}
