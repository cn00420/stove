<?php
/**
 * Created by IntelliJ IDEA.
 * User: lrj
 * Date: 2015/12/13
 * Time: 20:56
 */

namespace stove;

require_once(__DIR__."/StoveConfig.php");
require_once(__DIR__."/../../template/lib/Smarty.class.php");

function stove_trans_4_json($str){
    $validstr = '';
    if (strlen($str) > 0) {
        $validstr = str_replace('"', '\\"', $str);
        $validstr = str_replace("\r", '@stove_r@', $validstr);
        $validstr = str_replace("\n", '@stove_n@', $validstr);
    }
    return $validstr;
}

function stove_get_template_engine(){
    stove_start_session();
    $smarty = null;
    if (isset($_SESSION['template_obj']))
        $smarty = $_SESSION['template_obj'];
    else {
        $smarty = new \Smarty();
        $smarty->setTemplateDir(__DIR__ . "/../../html");
        $smarty->setCompileDir(__DIR__ . "/../../template/bin");
        $smarty->setCacheDir(__DIR__ . "/../../template/cache");
        $smarty->setLeftDelimiter("{#");
        $smarty->setRightDelimiter("#}");
        //$smarty->setCaching(true);
        //$smarty->setCacheLifetime(3600*24);   // 以s为单位
        $_SESSION['template_obj'] = $smarty;
    }

    return $smarty;
}

function stove_get_last_insert_id($conn){
    $sql = "SELECT LAST_INSERT_ID()";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_row($result);
    $id=$row[0];
    $result->free();
    return $id;
}


// 计算阅读时间
/*
 * Read time is based on the average reading speed of an adult (roughly 275 WPM). We take the total word count of a post and
 * translate it into minutes. Then, we add 12 seconds for each inline image. Boom, read time. So we amended our read time
 * calculation to count 12 seconds for the first image, 11 for the second,
 * and minus an additional second for each subsequent image. Any images after the tenth image are counted at three seconds.
 */
function stove_calc_spend_time($words, $images){
    $textTime = $words/275*60;    // 文本阅读秒数
    $imageTime = 0;  // 图片阅读秒数
    for($i=0; $i < $images; $i++){
        $temp = 12 - $i;
        if ($temp < 3)
            $temp = 3;
        $imageTime += $temp;
    }

    $spendTime = ceil(($textTime + $imageTime) / 60);
    return $spendTime;
}

function stove_start_session(){
    if (!isset($_SESSION))
        session_start();
}

// 得到图片的根目录，因为一部分老的图片存在另外一台服务器上，为了保持兼容
function stove_get_image_url($rawurl){
    $url = '';
    if (substr($rawurl, 0, 7) == '/upload')
        $url = $rawurl;
    else if (substr($rawurl, 0, 4) == 'http')
        $url = $rawurl;
    else
        $url = $imageRoot = IMAGE_ROOT.$rawurl;
    return $url;
}

// 通过GET方法调用solr的查询服务
function stove_solr_get($qryarray){
    $solr = curl_init();
    $this_header = array(
        "content-type: application/x-www-form-urlencoded; 
        charset=UTF-8"
    );
    curl_setopt($solr,CURLOPT_HTTPHEADER,$this_header);
    curl_setopt($solr, CURLOPT_PROTOCOLS, CURLPROTO_HTTP );  // 设置访问地址用的协议类型为HTTP
    curl_setopt($solr, CURLOPT_CONNECTTIMEOUT, 15);  // 访问的超时时间限制为15s
    curl_setopt($solr, CURLOPT_VERBOSE, false);
    $url = SOLR."/select".'?q='.build_solr_query($qryarray).'&wt=json';
    curl_setopt($solr, CURLOPT_URL, $url);  // 设置即将访问的URL

    $result = curl_exec($solr);
    return $result;
}

// 构建solr查询串
function build_solr_query($qryarray){
    $count = count($qryarray);
    $qrystr = '';
    for($i = 0; $i < $count; $i++){
        if ($i > 0)
            $qrystr.='%0A';
        $qrystr.=$qryarray[$i];
    }
    return $qrystr;
}