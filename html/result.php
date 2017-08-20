<?php

require_once("const.php");
require_once("logger.php");

class Result {
    const KEY_SEPARATOR = ",";

    function __construct($jobid) {
        $this->logger = new Logger();
        $this->jobid = $jobid;
        $this->results = array();
    }

    public function file_name() {
        return sprintf("%s.%s.csv", SEARCH_RESULT_FILE_PREFIX, $this->jobid);
    }

    public function clear() {
        $file = $this->file_name();
        if (!unlink($file)) {
            $this->logger->error("failed unlink status file ($file)");
            return false;
        }
        return true;
    }

    public function createKey($asin, $keyword) {
        return $asin. self::KEY_SEPARATOR. $keyword;
    }

    public function explodeKey($key) {
        return explode(self::KEY_SEPARATOR, $key);
    }

    public function add(
        $asin, $keyword,
        $asin_item_page=null, $asin_item_pos=null, $item_num=null
    ) {
        $key = $this->createKey($asin, $keyword);
        $this->results[$key] = array($asin_item_page, $asin_item_pos, $item_num);
    }

    public function output() {
        $output_str = $this->format();
        if (!file_put_contents($this->file_name(), $output_str)) {
            return false;
        } else {
            return true;
        }
    }

    protected function format() {
        $output = "";
        foreach ($this->results as $key => $arr) {
            list ($asin, $keyword) = $this->explodeKey($key);
            //$keyword = mb_strimwidth($keyword, 0, 20, "...", "UTF-8");
            $keyword = mb_convert_encoding($keyword, "SJIS", "UTF^8");
            $asin_page = isset($arr[0]) ? $arr[0] : "";
            $asin_pos = isset($arr[1]) ? $arr[1] : "";
            $item_num = isset($arr[2]) ? $arr[2] : "";
            $output = $output. sprintf("%s,%s,%s,%s,%s\n",
                $asin,
                $keyword,
                $asin_page,
                $asin_pos,
                $item_num
            );
        }
        return $output;
    }
}
