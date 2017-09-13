<?php
/**
 * Created by IntelliJ IDEA.
 * User: lrj
 * Date: 2016/3/19
 * Time: 15:43
 */

namespace stove;
require_once(__DIR__ . "/../util/StoveUtil.php");

header("Content-Type: text/html;charset=utf-8");

search();

// search使用get方法
function search(){
    $q = $_GET["q"];
    $what = $_GET["what"];
    $result = null;
    if ($what == 'j') {
        searchJournal($q);
    }
}

// 查询刊物
function searchJournal($q){
    $qlist = array();
    $key = urlencode($q);
    $qlist[0] = 'journal_name_t:('.$key.')';
    $qlist[1] = 'journal_desc_t:('.$key.')';
    return stove_solr_get($qlist);
}