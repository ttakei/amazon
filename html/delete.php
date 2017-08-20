<?php

require_once("const.php");
require_once("logger.php");
require_once("render.php");
require_once("status.php");
require_once("result.php");

function main() {
    $logger = new Logger();
    $render = new Render();

    if (empty($_POST["jobid"])) {
        $logger->error("empty jobid");
        $render->text_exit("入力が不正です", "400");
    }
    $jobid = $_POST["jobid"];

    $status = new Status($jobid);
    $result = new Result($jobid);

    if (!$status->clear()) {
        $logger->error("failed delete status file, jobid:$jobid");
        $render->text_exit("ステータスファイルの削除に失敗しました", "500");
    }

    if (!$result->clear()) {
        $logger->error("failed delete result file, jobid:$jobid");
        $render->text_exit("結果ファイルの削除に失敗しました", "500");
    }

    $logger->info("delete jobid:$jobid");
    $render->text_exit("削除に成功しました");
}

main();
