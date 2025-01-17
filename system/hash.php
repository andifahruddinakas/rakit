<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Hash
{
    /**
     * Buat hash password.
     * Method ini diadaptasi dari https://github.com/ircmaxell/password-compat.
     *
     * @param string $password
     * @param int    $cost
     *
     * @return string
     */
    public static function make($password, $cost = 10)
    {
        if (! is_int($cost) || $cost < 4 || $cost > 31) {
            throw new \Exception('Cost parameter must be an integer between 4 to 31.');
        }

        if (! function_exists('crypt')) {
            throw new \Exception('Crypt must be loaded to use the hashing library.');
        }

        if (is_null($password) || is_int($password)) {
            $password = (string) $password;
        }

        if (! is_string($password)) {
            throw new \Exception('Password must be a string.');
        }

        $buffer = Str::bytes(16);
        $salt = strtr(
            rtrim(base64_encode($buffer), '='),
            'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/',
            './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
        );

        $salt = mb_substr((string) $salt, 0, 22, '8bit');
        $hash = crypt($password, sprintf('$2y$%02d$', $cost).$salt);

        if (! is_string($hash) || 60 !== mb_strlen((string) $hash, '8bit')) {
            throw new \Exception('Malformatted password hash result.');
        }

        return $hash;
    }

    /**
     * Cek cocok atau tidaknya sebuah password dengan hashnya.
     * Method ini diadaptasi dari https://github.com/ircmaxell/password-compat.
     *
     * @param string $password
     * @param string $hash
     *
     * @return bool
     */
    public static function check($password, $hash)
    {
        if (! function_exists('crypt')) {
            throw new \Exception('Crypt must be loaded to use the hashing library.');
        }

        $crypt = crypt($password, $hash);

        if (! is_string($crypt)
        || mb_strlen((string) $crypt, '8bit') !== mb_strlen((string) $hash, '8bit')
        || mb_strlen((string) $crypt, '8bit') <= 13) {
            return false;
        }

        $status = 0;
        $length = mb_strlen((string) $crypt, '8bit');

        for ($i = 0; $i < $length; $i++) {
            $status |= (ord($crypt[$i]) ^ ord($hash[$i]));
        }

        return 0 === $status;
    }

    /**
     * Cek apakah hash yang dihasilkan masih lemah berdasarkan cost yang diberikan.
     * Method ini diadaptasi dari https://github.com/ircmaxell/password-compat.
     *
     * @param string $hash
     * @param int    $cost
     *
     * @return bool
     */
    public static function weak($hash, $cost = 10)
    {
        if (! is_int($cost) || $cost < 4 || $cost > 31) {
            throw new \Exception('Cost parameter must be an integer between 4 to 31.');
        }

        if ('$2y$' === mb_substr((string) $hash, 0, 4, '8bit')
        && 60 === mb_strlen((string) $hash, '8bit')) {
            list($strength) = sscanf($hash, '$2y$%d$');
            return $cost !== $strength;
        }

        return false;
    }
}
