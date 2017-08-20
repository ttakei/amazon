<?php

class Render {
    public function text_exit($str = "", $status="200") {
        http_response_code($status);
        echo($str);
        exit;
    }
}

