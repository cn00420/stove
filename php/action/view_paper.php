<?php
/**
 * Created by IntelliJ IDEA.
 * User: lrj
 * Date: 2016/1/3
 * Time: 10:34
 */
namespace stove;

require_once(__DIR__."/../util/StoveResponse.php");
require_once(__DIR__."/../util/StoveLogger.php");
require_once(__DIR__."/../util/SQLException.php");
require_once(__DIR__."/../util/StoveUtil.php");
require_once(__DIR__."/../domain/Paper.php");

header("Content-Type: text/html;charset=utf-8");

$paper_id = $_GET['paper_id'];

$paper = PaperRepository::getPaperById($paper_id);

if ($paper){
    $smarty = stove_get_template_engine();
    $smarty->assign("paper_title", $paper->paperTitle);
    $smarty->assign("paper_summary", $paper->paperSummary);
    $smarty->assign("content", $paper->content);
    $smarty->display("view_paper.html");
}
else
    echo "文章可能已经被删除";