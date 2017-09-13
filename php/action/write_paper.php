<?php
/**
 * Created by IntelliJ IDEA.
 * User: lrj
 * Date: 2016/1/21
 * Time: 18:20
 */
namespace stove;

require_once(__DIR__."/../util/StoveResponse.php");
require_once(__DIR__."/../util/StoveLogger.php");
require_once(__DIR__."/../util/SQLException.php");
require_once(__DIR__."/../util/StoveUtil.php");
require_once(__DIR__."/../domain/Paper.php");

header("Content-Type: text/html;charset=utf-8");

$draft_id = -1;
if (isset($_GET['draft_id']))
    $draft_id = $_GET['draft_id'];


if ($draft_id > -1) {
    $draft = PaperRepository::getDraftById($draft_id);

    if (!$draft) {
        echo '文章可能已经被删除';
        return;
    }

// 判断权限
    session_start();
    if ((!isset($_SESSION['user_id'])) || ($draft->writeUser <> $_SESSION['user_id'])) {
        echo '您没有权限修改这篇文章';
        return;
    }

    $smarty = stove_get_template_engine();
    $smarty->assign("paper_id", $draft->paperId);
    $smarty->assign("paper_title", $draft->paperTitle);
    $smarty->assign("paper_summary", $draft->paperSummary);
    $smarty->assign("content", $draft->content);
    $smarty->assign("content_length", strlen($draft->content));

    $smarty->display("write_paper.html");
}
else{
    $smarty = stove_get_template_engine();
    $smarty->assign("paper_id", -1);
    $smarty->assign("paper_title", "");
    $smarty->assign("paper_summary", "");
    $smarty->assign("content", "");
    $smarty->assign("content_length", 0);

    $smarty->display("write_paper.html");
}


