<?php
/**
 * Created by IntelliJ IDEA.
 * User: lrj
 * Date: 2015/12/13
 * Time: 20:19
 */

namespace stove;

require_once(__DIR__."/../domain/Paper.php");
require_once(__DIR__."/../util/StoveResponse.php");
require_once(__DIR__."/../util/StoveLogger.php");
require_once(__DIR__."/../util/SQLException.php");

$action = $_POST['action'];
if (!$action)
    $action='get-papers';

switch($action){
    case 'get-papers':
        $json = getPapers();
        echo $json;
        break;
    case 'get-journal-papers':
        $json = getPapersInJournal();
        echo $json;
        break;
    case 'save':
        $json = savePaperDraft();
        echo $json;
        break;
    case 'publish':
        $json = publishDraft();
        echo $json;
        break;
    default:
        $response = new StoveResponse(-101, "无效的动作");
        echo $response->toJson();
}

function getPapers(){
    $page = $_POST['page_index'];
    $order_by = $_POST['order_by'];
    $page_size = $_POST['page_size'];

    $start = (((int) $page) - 1)*((int) $page_size);
    $papers = PaperRepository::getPaperList($order_by, $start, (int) $page_size);
    $json = "[";

    for($i = 0; $i < count($papers); $i++){
        $json = $json.($i > 0 ? ',' : '').$papers[$i]->toJSON();
    }

    $json = $json."]";
    return $json;
}

function savePaperDraft(){
    $localPaperId = -1;
    if (isset($_POST['paper_id']))
        $localPaperId = $_POST['paper_id'];

    $savedpaper = null;
    $response = null;
    try {
        if ($localPaperId > -1)
            $localPaperId = updatePaperDraft();
        else
            $localPaperId = createPaperDraft();

        $response = new StoveResponse(0, '', '{"PAPER_ID": "'.$localPaperId.'"}');
    }
    catch(SQLException $sqle){
        $response = new StoveResponse($sqle->getSQLCode(), $sqle->getSQLMessage());
    }

    return $response->toJson();
}

// 创建新的稿件
function createPaperDraft(){
    $paper = new PaperDraft();
    if (!isset($_POST['paper_title']))
        $paper->paperTitle = "无标题";
    else {
        $paper->paperTitle = $_POST['paper_title'];
        if (strlen($paper->paperTitle) == 0)
            $paper->paperTitle = "无标题";
    }

    if (!isset($_POST['paper_keyword']))
        $paper->paperKeyWord = "";
    else
        $paper->paperKeyWord = $_POST['paper_keyword'];


    if (!isset($_POST['paper_summary']))
        $paper->paperSummary = "";
    else
        $paper->paperSummary = $_POST['paper_summary'];

    $paper->deliveryFlag="0";


    session_start();
    $paper->writeUser = $_SESSION['user_id'];
    $paper->content = $_POST['content'];
    $paper->imageURL = "";

    $paper->lastModifyDate = time();
    $paper->words = $_POST['words'];
    $paper->images = $_POST['images'];;

    $paper->spendTime = stove_calc_spend_time($paper->words, $paper->images);

    $paper->createDraft();
    return $paper->paperId;
}

function updatePaperDraft(){
    $paper = new PaperDraft();
    $paper->paperId = $_POST['paper_id'];

    if (!isset($_POST['paper_title']))
        $paper->paperTitle = "无标题";
    else {
        $paper->paperTitle = $_POST['paper_title'];
        if (strlen($paper->paperTitle) == 0)
            $paper->paperTitle = "无标题";
    }

    if (!isset($_POST['paper_keyword']))
        $paper->paperKeyWord = "";
    else
        $paper->paperKeyWord = $_POST['paper_keyword'];


    if (!isset($_POST['paper_summary']))
        $paper->paperSummary = "";
    else
        $paper->paperSummary = $_POST['paper_summary'];

    $paper->content = $_POST['content'];
    $paper->imageURL = "";

    $paper->lastModifyDate = time();
    $paper->words = $_POST['words'];
    $paper->images = $_POST['images'];;

    $paper->spendTime = stove_calc_spend_time($paper->words, $paper->images);

    $paper->updateDraft();

    return $paper->paperId;
}

function readPaperDraft(){

}

function deletePaperDraft(){

}

// 发表文章
function publishDraft(){
    $response = null;
    $draftId = $_POST['draft_id'];
    $topicId = $_POST['topic_id'];

    try {
        PaperRepository::publish($draftId, $topicId);
        $response = new StoveResponse(0);
    }
    catch(SQLException $sqle){
        $response = new StoveResponse($sqle->getSQLCode(), $sqle->getSQLMessage());
    }

    return $response->toJson();
}

function getPapersInJournal(){
    $page = $_POST['page_index'];
    $order_by = $_POST['order_by'];
    $page_size = $_POST['page_size'];
    $journal_id = $_POST['journal_id'];

    $start = (((int) $page) - 1)*((int) $page_size);
    $papers = PaperRepository::getPaperListInJournal($journal_id, $order_by, $start, (int) $page_size);
    $json = "[";

    for($i = 0; $i < count($papers); $i++){
        $json = $json.($i > 0 ? ',' : '').$papers[$i]->toJSON();
    }

    $json = $json."]";
    return $json;
}