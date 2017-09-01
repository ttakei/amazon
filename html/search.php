<?php
require_once("const.php");
require_once("logger.php");
require_once("render.php");
require_once("status.php");
require_once("result.php");
require_once("searcher.php");

function validate_input() {
    $required = array("keywords");
    foreach ($required as $id) {
        if (!isset($_POST[$id])) {
            return false;
        }
    }
    return true;
}

function issue_jobid($asin) {
    $date = date("Ymdhis");
    return uniqid("{$asin}_{$date}_");
}

function main() {
    $logger = new Logger();
    $render = new Render();
    $searcher = new Searcher();

    // 入力チェックと入力パラメータの取得
    if (!validate_input()) {
        $render->text_exit("入力が不正です", "400");
    }
    $asin = isset($_POST["asin"]) ? $_POST["asin"] : "";
    $keywords_raw = $_POST["keywords"];
    $keywords = explode("\n", $keywords_raw);
   
    // ジョブIDの発行、開始時刻の保存
    $jobid = issue_jobid($asin);
    $status = new Status($jobid);
    $result = new Result($jobid);
    $logger->info("start {$jobid}");
    
    // 検索処理 
    $total = count($keywords);
    $done_cnt = 0;
    $fail_cnt = 0;
    foreach ($keywords as $keyword) {
        $keyword = rtrim($keyword);
        if (empty($keyword)) {
            continue;
        }

        // 検索実行
        $searched = $searcher->search($asin, $keyword);
        if (!isset($searched[2])) {
            $logger->warning("failed search asin:{$asin} keyword:{$keyword} jobid:{$jobid}");
            $fail_cnt++;
            $result->add($asin, $keyword);
        } else {
            $asin_item_page = $searched[0];
            $asin_item_pos = $searched[1];
            $item_sum = $searched[2];
            $result->add($asin, $keyword, $asin_item_page, $asin_item_pos, $item_sum);
        }

        // ステータス更新
        $done_cnt++;
        $dont_cnt_str = "{$done_cnt} (/{$total})";
        if (!$status->update_processing($dont_cnt_str, $fail_cnt)) {
            $logger->error("failed update status to {$done_percent} jobid:{$jobid}");
       }
    }
   
    // 結果のファイル出力
    if (!$result->output()) {
        $logger->error("failed output result jobid:{$jobid}");
        $render->text_exit("処理(id:{$jobid})は結果の出力に失敗しました", "500");
    }

    // ステータス更新
    if (!$status->update_done($done_cnt, $fail_cnt)) {
        $logger->error("failed update status to done jobid:{$jobid}");
        $render->text_exit("処理(id:{$jobid})はステータスの更新に失敗しました", "500");
    }

    // 終了通知
    $logger->info("done {$jobid}");
    $render->text_exit(sprintf("処理(id:%s)が完了しました", $jobid));
}

main();
