<?php

namespace System\Auth\Drivers;

defined('DS') or exit('No direct script access.');

use System\Arr;
use System\Hash;
use System\Config;

class Facile extends Driver
{
    /**
     * Ambil user saat ini.
     * Jika ia belum login, NULL akan direturn.
     *
     * @param int|object $token
     *
     * @return mixed|null
     */
    public function retrieve($token)
    {
        if (false !== filter_var($token, FILTER_VALIDATE_INT)) {
            return $this->model()->find($token);
        } elseif (is_object($token) && get_class($token) === Config::get('auth.model')) {
            return $token;
        }
    }

    /**
     * Coba loginkan user.
     *
     * @param array $arguments
     *
     * @return bool
     */
    public function attempt(array $arguments = [])
    {
        $user = $this->model()->where(function ($query) use ($arguments) {
            $query->where('email', '=', $arguments['email']);
            $except = Arr::except($arguments, ['email', 'password', 'remember']);

            foreach ($except as $column => $val) {
                $query->where($column, '=', $val);
            }
        })->first();

        if (! is_null($user) && Hash::check($arguments['password'], $user->password)) {
            return $this->login($user->get_key(), Arr::get($arguments, 'remember'));
        }

        return false;
    }

    /**
     * Ambil instance model baru.
     *
     * @return Facile
     */
    protected function model()
    {
        $model = Config::get('auth.model');

        if (! $model) {
            throw new \Exception('Please set the auth model in your config file.');
        }

        return new $model();
    }
}
