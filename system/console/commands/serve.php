<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct script access.');

class Serve extends Command
{
    /**
     * Jalankan development server.
     *
     * @return void
     */
    public function run(array $arguments = [])
    {
        $port = empty($arguments) ? 8000 : $arguments[0];
        $port = (int) ((is_numeric($port) && $port >= 20 && $port <= 65535) ? $port : 8000);

        echo 'Development server started at: http://localhost:'.$port.PHP_EOL;
        echo 'Press Ctrl-C to quit.'.PHP_EOL;
        echo PHP_EOL;

        if (ob_get_level() > 0) {
            ob_end_flush();
        }

        passthru(escapeshellcmd('php -S localhost:'.$port.' -t .'));
    }
}
