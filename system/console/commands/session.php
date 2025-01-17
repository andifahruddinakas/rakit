<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct script access.');

use System\Container;
use System\Storage;
use System\Config;
use System\Session as BaseSession;
use System\Session\Drivers\Sweeper;

class Session extends Command
{
    /**
     * Buat tabel session di database.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function table(array $arguments = [])
    {
        $make = Container::resolve('command: make');

        $migration = $make->migration(['create_sessions_table']);
        $stub = __DIR__.DS.'stubs'.DS.'session.stub';

        Storage::put($migration, Storage::get($stub));

        echo PHP_EOL;
    }

    /**
     * Bersihkan session yang telah kedaluwarsa.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function sweep(array $arguments = [])
    {
        $driver = BaseSession::factory(Config::get('session.driver'));

        if ($driver instanceof Sweeper) {
            $lifetime = Config::get('session.lifetime');
            $driver->sweep(time() - ($lifetime * 60));
        }

        echo 'The session table has been swept!';
    }

    /**
     * Ubah driver session di file konfigurasi.
     *
     * @param string $driver
     *
     * @return void
     */
    protected function driver($driver)
    {
        $config = Storage::get(path('app').'config'.DS.'session.php');

        $pattern = "/(('|\")driver('|\"))\h*=>\h*(\'|\")\s?(\'|\")?.*/i";
        $replaced = preg_replace($pattern, "'driver' => '{$driver}',", $config);

        if (! is_null($replaced)) {
            Storage::put(path('app').'config'.DS.'session.php', $replaced);
            Config::set('session.driver', $driver);
        }
    }
}
