<?php

require_once("const.php");
require_once("logger.php");
require_once("status.php");
require_once("render.php");


function main() {
    $logger = new Logger();
    $render = new Render();

    $status_header = Status::header();
    $status_field_num = mb_substr_count($status_header, ",");
    $header = "ジョブID,{$status_header},削除\n";
    $files = Status::all_files();
    $output = $header;
    foreach ($files as $file) {
        // ジョブID
        $jobid = Status::jobid_from_file($file);

        // ステータスファイル中身
        $raw = Status::read($file);
        if ($raw === false) {
            $logger->info("invalid status file $file");
            continue;
        }

        // 削除ボタン
        $del_btn = "<button id='{$jobid}' class='del'>削除</button>";

        $output = $output. sprintf("%s,%s,%s\n", $jobid, $raw, $del_btn);
    }

    $render->text_exit($output);
}

main();
