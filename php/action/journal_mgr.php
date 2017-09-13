<?php
/**
 * Created by IntelliJ IDEA.
 * User: lrj
 * Date: 2015/12/12
 * Time: 20:02
 */
namespace stove;

require_once(__DIR__. "/../domain/Journal.php");
require_once(__DIR__."/../util/StoveResponse.php");
require_once(__DIR__."/../util/StoveLogger.php");
require_once(__DIR__."/../util/SQLException.php");
require_once(__DIR__."/../util/MySQLConnection.php");

$action = $_POST['action'];
if (!$action)
    $action='get-journals';

switch($action){
    case 'get-journals':
        $json = getJournals();
        echo $json;
        break;
    case 'match-journals':
        $json = matchJournals();
        echo $json;
        break;
    case 'match-topics':
        $json = matchTopics();
        echo $json;
        break;
    case 'create-journal':
        $json = createJournal();
        echo $json;
        break;
    case 'update-journal':
        $json = updateJournal();
        echo $json;
        break;
    case 'get-journal-tags-def':
        $json = getJournalTagsDef();
        echo $json;
        break;
    case 'get-journal-for-edit':
        $json = getJournalForEdit();
        echo $json;
        break;
    default:
        $response = new \stove\StoveResponse(-101, "无效的动作");
        echo $response->toJson();
}

function getJournals(){
    $page = $_POST['page_index'];
    $order_by = $_POST['order_by'];
    $page_size = $_POST['page_size'];

    $start = (((int) $page) - 1)*((int) $page_size);
    $journals = JournalRepository::getJournalList($order_by, $start,(int) $page_size);
    $json = "[";

    for($i = 0; $i < count($journals); $i++){
        $json = $json.($i > 0 ? ',' : '').$journals[$i]->toJSON();
    }

    $json = $json."]";
    return $json;
}

function matchJournals(){
    $name = $_POST['name'];
    $json = null;
    try {
        $journals = JournalRepository::matchJournalByName($name);
        $json = "[";

        for ($i = 0; $i < count($journals); $i++) {
            $json = $json . ($i > 0 ? ',' : '') . $journals[$i]->toJSON();
        }

        $json = $json . "]";
        return $json;
    }
    catch(SQLException $sqle){
        $json = '[]';
    }
}

function matchTopics(){
    $name = $_POST['name'];
    $journal_id = $_POST['journal_id'];

    $json = null;
    try {
        $topics = JournalRepository::matchTopicByName($journal_id, $name);
        $json = "[";

        for ($i = 0; $i < count($topics); $i++) {
            $json = $json . ($i > 0 ? ',' : '') . $topics[$i]->toJSON();
        }

        $json = $json . "]";
        return $json;
    }
    catch(SQLException $sqle){
        $json = '[]';
    }
}

function createJournal(){
    // 假设用户已经登录
    stove_start_session();

    $name = $_POST['journal_name'];
    $tags = explode(',', $_POST['journal_tags']);
    $desc = $_POST['journal_desc'];
    $url = $_POST['image_url'];

    $response = null;

    $conn = MySQLConnection::getConnection(false);

    try {
        $journalId = JournalRepository::createJournal($conn, $name, $tags, $desc, $url);
        mysqli_commit($conn);
        $response = new StoveResponse(0, '', "{journal_id: " + $journalId + " }");
    }
    catch(SQLException $sqle){
        $response = new StoveResponse($sqle->getSQLCode(), $sqle->getSQLMessage());
    }
    catch(Exception $e){
        $response = new StoveResponse(-1, $e->getMessage());
    }
    finally{
        mysqli_close($conn);
    }

    return $response->toJson();
}

// 更新刊物信息
function updateJournal(){
    // TODO 判断用户权限
    // stove_start_session();

    $id = $_POST['journal_id'];
    $name = $_POST['journal_name'];
    $tags = $_POST['journal_tags'];
    $tagsArray = explode(',', $tags);
    $desc = $_POST['journal_desc'];
    $url = $_POST['image_url'];

    $response = null;

    $conn = MySQLConnection::getConnection(false);

    try {
        $journal = JournalRepository::getJournalByIdWithConn($conn, $id);
        $journal->journalName = $name;
        $journal->journalDesc = $desc;
        $journal->imageURL = $url;
        $journal->save($conn);

        $journal->tags = $tags;
        $journal->relateTag($conn, $tagsArray);

        mysqli_commit($conn);
        $response = new StoveResponse(0);
    }
    catch(SQLException $sqle){
        $response = new StoveResponse($sqle->getSQLCode(), $sqle->getSQLMessage());
    }
    catch(Exception $e){
        $response = new StoveResponse(-1, $e->getMessage());
    }
    finally{
        mysqli_close($conn);
    }

    return $response->toJson();
}

function getJournalTagsDef(){
    $key = $_POST['key'];
    $conn = MySQLConnection::getConnection();

    try{
        $tags = JournalRepository::getJournalTagsDef($conn, $key, 0, 10);
        $json = "[";

        for($i = 0; $i < count($tags); $i++){
            $json = $json.($i > 0 ? ',' : '').'{"name":"'.$tags[$i].'", "value": "'.$tags[$i].'"}';
        }

        $json = $json."]";
        return $json;
    }
    finally{
        mysqli_close($conn);
    }
}

function getJournalForEdit(){
    $journalId = $_POST['journal_id'];
    // todo 判断权限
    $journal = JournalRepository::getJournalById($journalId);
    return $journal->toJSON();
}