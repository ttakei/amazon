<?php

class Logger {
    public function log($msg, $level="INFO") {
        // $time = date("Y/m/d H:i:s", time());
        // $log = sprintf("[%s] [%s] %s", $time, $level, $msg);
        $log = sprintf("[%s] %s", $level, $msg);
        error_log($log);
    }

    public function info($msg) {
        $this->log($msg, "INFO");
    }

    public function error($msg) {
        $this->log($msg, "ERROR");
    }

    public function warning($msg) {
        $this->log($msg, "WARNING");
    }

    public function debug($msg) {
        $this->log($msg, "DEBUG");
    }
}
