<?php

require_once("logger.php");

class Searcher {
    const URL = "https://www.amazon.co.jp/gp/aw/s";
    const MAX_PAGE = 10;

    function __construct() {
        $this->logger= new Logger();
    }

    public function createPageUrl($keyword, $page=1) {
        $param = array(
            "page" => $page,
            "keywords" => $keyword,
            "sf" => "qz",
        );
        $url = self::URL. "?". http_build_query($param);
        return $url;
    }

    public function search($asin, $keyword) {
        $item_num = null;
        $asin_item_page = null;
        $asin_item_pos = null;

        for ($page = 1; $page <= self::MAX_PAGE; $page++) {
            $url = $this->createPageUrl($keyword, $page);
            $header = array(
                'User-Agent:Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Mobile Safari/537.36',
            );
            $max_retry = 3;
            for ($retry_cnt = 0; $retry_cnt < $max_retry; $retry_cnt++) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                $html = curl_exec($ch);
                curl_close($ch);
            
                if (empty($html)) {
                    if ($retry_cnt == $max_retry - 1) {
                        $this->logger->warning("failed request $url");
                        return false;
                    } else {
                        continue;
                    }
                }
                //file_put_contents("/tmp/$keyword.out", $html);
                $html = str_replace(array("\r", "\n"), "", $html);
        
                if (!isset($item_num)) {    
                    $item_num = $this->extract_item_num($html);
                    if (empty($item_num)) {
                        if ($retry_cnt == $max_retry - 1) {
                            $this->logger->warning("could not extract item num $url");
                        } else {
                            continue;
                        }
                    }
                    if (empty($asin)) {
                        return array("", "", $item_num);
                    }
                }
                break;
            }
        
            $asin_list = $this->extract_asin_list($html);
            if (empty($asin_list)) {
                $this->logger->warning("could not extract asin list $url");
                continue;
            }
    
            $this->logger->debug(sprintf("asin_list:%s", var_export($asin_list, true)));
            $idx = array_search($asin, $asin_list);
            if ($idx !== false) {
                $asin_item_page = $page;
                $asin_item_pos = $idx + 1;
                break;
            }
        }

        return array($asin_item_page, $asin_item_pos, $item_num);
    }

    function extract_item_num($html) {
        mb_regex_encoding("UTF-8");
        $regex = '|検索結果 ([0-9, 以上]+)件|u';
        if (preg_match($regex, $html, $matches)) {
            $item_num = str_replace(",", "", $matches[1]);
            $item_num = mb_convert_encoding($item_num, "SJIS", "UTF-8");
            return $item_num;
        } else {
            return false;
        }
    }

    function extract_asin_list($html) {
        $regex = '|<a data-asin="([^"]+)"|';
        if (preg_match_all($regex, $html, $matches)) {
            return $matches[1];
        } else {
            return array();
        }
    }
}
