<?php

namespace System\Foundation\Oops;

defined('DS') or exit('No direct script access.');

class Firelog
{
    const DEBUG = 'debug';
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR = 'error';
    const EXCEPTION = 'exception';
    const CRITICAL = 'critical';

    /**
     * Berapa dalam array/object yang harus ditampilkan oleh dump()?
     *
     * @var int
     */
    public $maxDepth = 10;

    /**
     * Berapa banyak karakter harus ditampilkan oleh dump()?
     *
     * @var int
     */
    public $maxLength = 300;

    /**
     * Berisi payload firelog.
     *
     * @var array
     */
    private $payload = ['logs' => []];

    /**
     * Kirim pesan ke console firelog.
     *
     * @param mixed $message
     *
     * @return bool
     */
    public function log($message, $priority = self::DEBUG)
    {
        if (! isset($_SERVER['HTTP_X_FIRELOGGER']) || headers_sent()) {
            return false;
        }

        $item = [
            'name' => 'PHP',
            'level' => $priority,
            'order' => count($this->payload['logs']),
            'time' => str_pad(number_format((microtime(true) - Debugger::$time) * 1000, 1, '.', ' '), 8, '0', STR_PAD_LEFT).' ms',
            'template' => '',
            'message' => '',
            'style' => 'background:#767ab6',
        ];

        $args = func_get_args();

        if (isset($args[0]) && is_string($args[0])) {
            $item['template'] = array_shift($args);
        }

        if (isset($args[0]) && (($args[0] instanceof \Exception) || ($args[0] instanceof \Throwable))) {
            $e = array_shift($args);
            $trace = $e->getTrace();

            if (isset($trace[0]['class'])
            && 'System\Foundation\Oops\Debugger' === $trace[0]['class']
            && ('shutdownHandler' === $trace[0]['function'] || 'errorHandler' === $trace[0]['function'])) {
                unset($trace[0]);
            }

            $file = str_replace(dirname(dirname(dirname($e->getFile()))), "\xE2\x80\xA6", $e->getFile());
            $item['template'] = (($e instanceof \ErrorException) ? '' : Helpers::getClass($e).': ')
                .$e->getMessage().($e->getCode() ? ' #'.$e->getCode() : '').' in '.$file.':'.$e->getLine();
            $item['pathname'] = $e->getFile();
            $item['lineno'] = $e->getLine();
        } else {
            $trace = debug_backtrace();

            if (isset($trace[1]['class'])
            && 'System\Foundation\Oops\Debugger' === $trace[1]['class']
            && ('fireLog' === $trace[1]['function'])) {
                unset($trace[0]);
            }

            foreach ($trace as $frame) {
                if (isset($frame['file']) && is_file($frame['file'])) {
                    $item['pathname'] = $frame['file'];
                    $item['lineno'] = $frame['line'];
                    break;
                }
            }
        }

        $item['exc_info'] = ['', '', []];
        $item['exc_frames'] = [];

        foreach ($trace as $frame) {
            $frame += ['file' => null, 'line' => null, 'class' => null, 'type' => null, 'function' => null, 'object' => null, 'args' => null];
            $item['exc_info'][2][] = [$frame['file'], $frame['line'], "$frame[class]$frame[type]$frame[function]", $frame['object']];
            $item['exc_frames'][] = $frame['args'];
        }

        if (isset($args[0])
        && in_array($args[0], [self::DEBUG, self::INFO, self::WARNING, self::ERROR, self::CRITICAL], true)) {
            $item['level'] = array_shift($args);
        }

        $item['args'] = $args;

        $this->payload['logs'][] = $this->jsonDump($item, -1);

        foreach (str_split(base64_encode(json_encode($this->payload)), 4990) as $k => $v) {
            header("Firelog-de11e-$k:$v");
        }

        return true;
    }

    /**
     * Implementasi dumper untuk JSON.
     *
     * @param mixed $var
     * @param int   $level
     *
     * @return mixed
     */
    private function jsonDump(&$var, $level = 0)
    {
        if (is_bool($var) || null === $var || is_int($var) || is_float($var)) {
            return $var;
        } elseif (is_string($var)) {
            return Dumper::encodeString($var, $this->maxLength);
        } elseif (is_array($var)) {
            static $marker;

            if (null === $marker) {
                $marker = uniqid("\x00", true);
            }

            if (isset($var[$marker])) {
                return "\xE2\x80\xA6RECURSION\xE2\x80\xA6";
            } elseif ($level < $this->maxDepth || ! $this->maxDepth) {
                $var[$marker] = true;
                $res = [];

                foreach ($var as $k => &$v) {
                    if ($k !== $marker) {
                        $res[$this->jsonDump($k)] = $this->jsonDump($v, $level + 1);
                    }
                }

                unset($var[$marker]);

                return $res;
            }
            return " \xE2\x80\xA6 ";
        } elseif (is_object($var)) {
            $arr = (array) $var;

            static $list = [];

            if (in_array($var, $list, true)) {
                return "\xE2\x80\xA6RECURSION\xE2\x80\xA6";
            } elseif ($level < $this->maxDepth || ! $this->maxDepth) {
                $list[] = $var;
                $res = ["\x00" => '(object) '.Helpers::getClass($var)];

                foreach ($arr as $k => &$v) {
                    if (isset($k[0]) && "\x00" === $k[0]) {
                        $k = substr($k, strrpos($k, "\x00") + 1);
                    }

                    $res[$this->jsonDump($k)] = $this->jsonDump($v, $level + 1);
                }

                array_pop($list);

                return $res;
            }

            return " \xE2\x80\xA6 ";
        } elseif (is_resource($var)) {
            return 'resource '.get_resource_type($var);
        }

        return 'unknown type';
    }
}
