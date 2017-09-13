<?php
/**
 * Created by IntelliJ IDEA.
 * User: lrj
 * Date: 2015/12/12
 * Time: 18:46
 */

$dir=__DIR__;
require_once($dir."/../util/MySQLConnection.php");
require_once($dir."/../util/StoveUtil.php");
require_once($dir."/../util/StoveResponse.php");
require_once($dir."/../util/SQLException.php");
require_once($dir . "/../domain/Paper.php");
require_once($dir . "/../domain/Journal.php");

header("Content-Type: text/html;charset=utf-8");
//get_session();

$current = date('Y-m-d H:i:s', time());
print_r(\stove\stove_solr_get(array('id:4', 'journal_name_t:OSS')));

//print_r(phpinfo());


function stove_test_match_topic(){
    $topics = \stove\JournalRepository::matchTopicByName(26, 'tmf');
    echo $topics[0]->toJSON();
}

function stove_test_match_journal(){
    $journals = \stove\JournalRepository::matchJournalByName('oss');
    print_r($journals);
}

function stove_test_publish(){
    $paper = \stove\PaperRepository::publish(131, 1);
    print_r($paper);
}

function stove_test_topic(){
    $topic = \stove\JournalRepository::getTopicById(1);
    print_r($topic);
}

function stove_test_template()
{
    $smarty = \stove\stove_get_template_engine();

    $smarty->assign("paper_title", "Title");
    $smarty->assign("paper_summary", "Summary");
    $smarty->assign("content", "Content");
    $smarty->display("view_paper.html");
}