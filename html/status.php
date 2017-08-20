<?php

require_once("const.php");
require_once("logger.php");

class Status {
    static $field_num = 6;

    function __construct($jobid) {
        $this->logger = new Logger();
        $this->jobid = $jobid;
        $this->start_time = time();
    }

    public static function header() {
        return "処理開始日時,処理終了日時,状態,完了件数,失敗件数,結果ファイル";
    }

    public static function all_files() {
        $files = glob(SEARCH_STATUS_FILE_PREFIX. ".*");
        usort($files, function ($a, $b) { return filemtime($b) > filemtime($a); });
        return $files;
    }

    public static function jobid_from_file($file) {
        return pathinfo($file, PATHINFO_EXTENSION);
    }

    public static function read($file) {
        if (!is_file($file)) {
            return false;
        }
        $raw = file_get_contents($file);
        if ($raw === false) {
            return false;
        }
        if (mb_substr_count($raw, ",") + 1 != self::$field_num) {
            return false;
        }
        return $raw;
    }

    public function file_name() {
        return sprintf("%s.%s", SEARCH_STATUS_FILE_PREFIX, $this->jobid);
    }

    public function clear() {
        $file = $this->file_name();
        if (!unlink($file)) {
            $this->logger->error("failed unlink status file ($file)");
            return false;
        }
        return true;
    }

    public function update($progress, $done_cnt, $fail_cnt, $start_time, $done_time = "") {
        $start_time_str = date("Y/m/d H:i:s", $start_time);
        if (!empty($done_time)) {
            $done_time_str = date("Y/m/d H:i:s", $done_time);
        } else {
            $done_time_str = "";
        }
    
        if ($progress == PROGRESS_DONE) {
            $result_file_url = SEARCH_RESULT_URL_PREFIX. ".{$this->jobid}.csv";
            $result_file_url_link = sprintf('<a href="%s" target="_blank">%s</a>', $result_file_url, $result_file_url);
        } else {
            $result_file_url_link = "";
        }
    
        $raw = sprintf("%s,%s,%s,%s,%s,%s",
            $start_time_str,
            $done_time_str,
            $progress,
            $done_cnt,
            $fail_cnt,
            $result_file_url_link
        );
    
        $file = $this->file_name();
        return file_put_contents($file, $raw);
    }

    public function update_processing($done_cnt, $fail_cnt) {
        return $this->update(PROGRESS_PROCESSING, $done_cnt, $fail_cnt, $this->start_time);
    }

    public function update_done($done_cnt, $fail_cnt) {
        return $this->update(PROGRESS_DONE, $done_cnt, $fail_cnt, $this->start_time, time());
    }
}
