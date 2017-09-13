<?php
/**
 * Created by IntelliJ IDEA.
 * User: lrj
 * Date: 2015/12/13
 * Time: 15:32
 */

namespace stove;

require_once(__DIR__."/../util/StoveConfig.php");
require_once(__DIR__."/../util/MySQLConnection.php");
require_once(__DIR__."/../util/SQLException.php");
require_once(__DIR__."/../util/StoveException.php");
require_once(__DIR__."/../util/StoveUtil.php");
require_once(__DIR__."/Journal.php");
require_once(__DIR__."/User.php");

class Paper{
    private $paperId, $paperTitle, $paperKeyWord, $paperSummary, $writeUser, $deliveryTS, $content, $imageURL;
    private $localPaperId, $topicID, $topic, $journalId, $journal, $auditTS, $pubTS, $rejectTS, $state;
    private $clickQty, $recommendationQty, $words, $images, $spendTime;
    private $author;

    public function __construct($paper_id='') {
        $this->paperId = $paper_id;
    }

    public function toJSON(){
        $json = '{"PAPER_ID":"'.$this->paperId.'", "PAPER_TITLE":"'.$this->paperTitle.'", "PAPER_SUMMARY":"'
            .$this->paperSummary.'", "PAPER_KEYWORD":"'.$this->paperKeyWord.'", "WRITE_USER":"'.$this->writeUser.'", "DELIVERY_DATE":"'
            .$this->deliveryTS.'", "IMAGE_URL":"'.$this->imageURL
            .'", "LOCAL_PAPER_ID":"'.$this->localPaperId.'","TOPIC_ID":"'.$this->topicID.'","JOURNAL_ID":"'.$this->journalId
            .'", "AUDIT_TS":"'.$this->auditTS
            .'", "PUB_TS":"'.$this->pubTS
            .'", "REJECT_TS":"'.$this->rejectTS
            .'", "STATE":"'.$this->state.'","CLICK_QTY":"'.( $this->clickQty == '' ? 0 : $this->clickQty )
            .'", "RECOMMENDATION_QTY":"'.( $this->recommendationQty == '' ? 0 : $this->recommendationQty )
            .'", "WORDS":"'.( $this->words == '' ? 0 : $this->words )
            .'", "IMAGES": "'.( $this->images == '' ? 0 : $this->images )
            .'", "SPEND_TIME": "'.$this->spendTime
            .'", "AUTHOR":'.$this->author->toJSON()
            .'}';
        return $json;
    }

    public function __set($name, $value) {
        $this->$name = $value;
    }

    public function __get($name)
    {
        return $this->$name;
    }
}

// 草稿
class PaperDraft{
    private $paperId, $paperTitle, $paperKeyWord, $paperSummary, $deliveryFlag, $deliveryDate, $writeUser, $content, $imageURL;
    private $journalId, $journal, $lastModifyDate, $delFlag;
    private $words, $images, $spendTime, $pubTs;
    private $author;

    /* Delivery_Flag:
     * 0-未投递,草稿（默认状态）
       1-已经投递
       2-已经采用
       3-已经发表
       4-已经退回
     */
    public function __construct($paper_id=-1) {
        $this->paperId = $paper_id;
    }

    public function toJSON(){
        $paperjson = '{"PAPER_ID":"'.$this->paperId.'", "PAPER_TITLE":"'.$this->paperTitle.'", "PAPER_SUMMARY":"'
            .$this->paperSummary.'", "PAPER_KEYWORD":"'.$this->paperKeyWord.'", "WRITE_USER":"'.$this->writeUser.'", "DELIVERY_DATE":"'
            .date('Y-m-d H:i:s', $this->deliveryDate).'", "IMAGE_URL":"'.$this->imageURL
            .'","JOURNAL_ID":"'.$this->journalId
            .'", "LAST_MODIFY_DATE":"'.(isset($this->lastModifyDate) ? date('Y-m-d H:i:s', $this->lastModifyDate) : '')
            .'", "DELIVERY_FLAG":"'.$this->deliveryFlag
            .'", "DEL_FLAG":"'.$this->delFlag
            .'", "WORDS":"'.( $this->words == '' ? 0 : $this->words )
            .'", "IMAGES": "'.( $this->images == '' ? 0 : $this->images )
            .'", "SPEND_TIME": "'.$this->spendTime
            .'", "AUTHOR":'.$this->author->toJSON()
            .'}';
        return $paperjson;
    }

    public function __set($name, $value) {
        $this->$name = $value;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function createDraft(){
        $conn = MySQLConnection::getConnection(false);
        $statement = null;
        $draftId = 0;

        try{
            mysqli_query($conn, "set names utf8");
            $sql = "INSERT INTO PAPER_LOCAL(PAPER_TITLE, PAPER_KEYWORD, PAPER_SUMMARY, DELIVERY_FLAG, WRITE_USER, ".
                " LAST_MODIFY_DATE, IMAGE_URL, WORDS, IMAGES, SPEND_TIME, CONTENT) ".
                " VALUES(?, ?, ?, ?, ?, str_to_date(?, '%Y-%m-%d %H:%i:%s'), ?, ?, ?, ?, ?)";
            $statement = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($statement, 'ssssissiiis',$this->paperTitle, $this->paperKeyWord, $this->paperSummary,
                $this->deliveryFlag, $this->writeUser, date('Y-m-d H:i:s', $this->lastModifyDate), $this->imageURL, $this->words,
                $this->images, $this->spendTime, $this->content);

            if (!mysqli_stmt_execute($statement))
                throw new \stove\SQLException($conn->errno, $conn->error);

            $draftId = stove_get_last_insert_id($conn);
            mysqli_commit($conn);
        }
        finally{
            if (!$statement)
                mysqli_stmt_close($statement);
            mysqli_close($conn);
        }

        $this->paperId = $draftId;
        return $draftId;
    }

    // 更新稿件
    public function updateDraft(){
        $conn = MySQLConnection::getConnection(false);
        $statement = null;
        $draftId = 0;

        try{
            mysqli_query($conn, "set names utf8");
            $sql = "UPDATE PAPER_LOCAL SET PAPER_TITLE = ?, PAPER_KEYWORD = ?, PAPER_SUMMARY = ?, ".
                   " LAST_MODIFY_DATE = str_to_date(?, '%Y-%m-%d %H:%i:%s'), IMAGE_URL = ?, ".
                   " WORDS = ?, IMAGES = ?, SPEND_TIME = ?, CONTENT = ? WHERE PAPER_ID = ?";

            $statement = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($statement, 'sssssiiisi',$this->paperTitle, $this->paperKeyWord, $this->paperSummary,
                date('Y-m-d H:i:s', $this->lastModifyDate), $this->imageURL, $this->words,
                $this->images, $this->spendTime, $this->content, $this->paperId);

            if (!mysqli_stmt_execute($statement))
                throw new \stove\SQLException($conn->errno, $conn->error);

            $draftId = $this->paperId;
            mysqli_commit($conn);
        }
        finally{
            if (!$statement)
                mysqli_stmt_close($statement);
            mysqli_close($conn);
        }

        $this->paperId = $draftId;
        return $draftId;
    }

    // 发表稿件,入参为专题
    public function publish($conn, $topic){
        // 插入稿件表
        $sql = "INSERT INTO PAPER(PAPER_TITLE, PAPER_KEYWORD, PAPER_SUMMARY, WRITE_USER, DELIVERY_TS, CONTENT, ".
               "LOCAL_PAPER_ID, IMAGE_URL, WORDS, IMAGES, SPEND_TIME, JOURNAL_ID, TOPIC_ID, PUB_TS, STATE) ".
               " VALUES(?, ?, ?, ?, str_to_date(?, '%Y-%m-%d %H:%i:%s'), ?, ?, ?, ?, ?, ?, ?, ?, ".
               " str_to_date(?, '%Y-%m-%d %H:%i:%s'), ?)";
        $statement = null;

        $deliveryTs = time();
        $this->deliveryDate = $deliveryTs;

        $state = '0';
        $deliveryFlag = '1';

        $pubTs = null;
        if ($this->writeUser == $topic->sponsorId){   // 如果是投稿给自己的刊物，直接发表
            $state = '2';
            $deliveryFlag = '3';
            $pubTs = $deliveryTs;
        }

        $this->deliveryFlag = $deliveryFlag;
        $this->pubTs = $pubTs;

        $paperId = 0;
        $topicId = $topic->topicId;
        $journalId = $topic->journalId;
        $topicName = $topic->topicName;
        $journalName = $topic->journal->journalName;

        try{
            $statement = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($statement, "sssissisiiiiiss", $this->paperTitle, $this->paperKeyWord, $this->paperSummary,
                $this->writeUser,date('Y-m-d H:i:s', $deliveryTs), $this->content, $this->paperId, $this->imageURL,
                $this->words, $this->images, $this->spendTime, $journalId, $topicId,
                date('Y-m-d H:i:s', $pubTs), $state);

            if (!mysqli_stmt_execute($statement))
                throw new SQLException($conn->errno, $conn->error);

            $paperId = stove_get_last_insert_id($conn);

            mysqli_stmt_close($statement);

            $sql = "UPDATE PAPER_LOCAL SET DELIVERY_FLAG = ?, JOURNAL_ID = ?, DELIVERY_JOURNAL = ?, ".
                   " DELIVERY_COLUMN = ?, DELIVERY_DATE = str_to_date(?, '%Y-%m-%d %H:%i:%s') ".
                   " WHERE PAPER_ID = ?";
            $statement = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($statement, "sisssi", $this->deliveryFlag, $journalId, $journalName,
                $topicName,date('Y-m-d H:i:s', $deliveryTs), $this->paperId);
            if (!mysqli_stmt_execute($statement))
                throw new SQLException($conn->errno, $conn->error);
        }
        finally{
            if ($statement)
                mysqli_stmt_close($statement);
        }

        return $paperId;
    }
}

class PaperRepository{
    // 得到文章列表
    public static function getPaperList($orderBy, $start, $rows){
        $conn = MySQLConnection::getConnection();
        $conn1 = MySQLConnection::getConnection();
        $papers = array();
        $statement = null;

        try{
            mysqli_query($conn, "set names utf8");
            $sql = "SELECT PAPER_ID, PAPER_TITLE, PAPER_KEYWORD, PAPER_SUMMARY, WRITE_USER, LOCAL_PAPER_ID, IMAGE_URL, ".
                " JOURNAL_ID, TOPIC_ID, DELIVERY_TS, AUDIT_TS, PUB_TS, REJECT_TS,  ".
                " CLICK_QTY, RECOMMENDATION_QTY, WORDS, IMAGES, SPEND_TIME, STATE ".
                " FROM PAPER WHERE STATE = '2' ORDER BY ".($orderBy == "1" ? " PUB_TS DESC " : " RECOMMENDATION_QTY DESC ").
                " LIMIT ?, ?";
            $statement = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($statement, 'ii',$start,$rows);

            if (!mysqli_stmt_execute($statement))
                throw new SQLException($conn->errno, $conn->error);

            // 获取数据并构造Journal对象列表
            mysqli_stmt_bind_result($statement, $id, $title, $keyword, $summary, $write_user, $local_paper_id, $image_url,
                $journal_id, $topic_id, $delivery_ts, $audit_ts, $pub_ts, $reject_ts,
                $click_qty, $recommendation_qty, $words, $images, $spend_time, $state);

            $i = 0;
            while(mysqli_stmt_fetch($statement)){
                $paper = new Paper($id);
                $paper->paperTitle = stove_trans_4_json($title);
                $paper->paperKeyWord = stove_trans_4_json($keyword);
                $paper->paperSummary = stove_trans_4_json($summary);
                $paper->writeUser = $write_user;
                $paper->localPaperId = $local_paper_id;
                $paper->imageURL = $image_url;
                $paper->journalId = $journal_id;
                $paper->topicID = $topic_id;
                $paper->deliveryTS = $delivery_ts;
                $paper->auditTS = $audit_ts;
                $paper->pubTS = $pub_ts;
                $paper->rejectTS = $reject_ts;
                $paper->clickQty = $click_qty;
                $paper->recommendationQty = $recommendation_qty;
                $paper->words = $words;
                $paper->images = $images;
                $paper->spendTime = $spend_time;
                $paper->state = $state;
                $papers[$i] = $paper;

                // 获取用户信息
                $paper->author = stove_get_user_by_id($conn1, $write_user);
                $i++;
            }

        }

        finally{
            if (!$statement)
                mysqli_stmt_close($statement);
            mysqli_close($conn);
            mysqli_close($conn1);
        }

        return $papers;
    }

    public static function getPaperById($paperId){
        $conn = MySQLConnection::getConnection();
        $paper = null;

        try{
            $paper =self::getPaperByIdWithConn($conn, $paperId);
        }
        catch(SQLException $sqle){
            throw $sqle;
        }
        finally{
            mysqli_close($conn);
        }

        return $paper;
    }

    private static function getPaperByIdWithConn($conn, $paperId){
        $conn1 = MySQLConnection::getConnection();
        $paper = null;
        $statement = null;

        try{
            mysqli_query($conn, "set names utf8");
            $sql = "SELECT PAPER_TITLE, PAPER_KEYWORD, PAPER_SUMMARY, WRITE_USER, LOCAL_PAPER_ID, IMAGE_URL, ".
                " JOURNAL_ID, TOPIC_ID, DELIVERY_TS, AUDIT_TS, PUB_TS, REJECT_TS,  ".
                " CLICK_QTY, RECOMMENDATION_QTY, WORDS, IMAGES, SPEND_TIME, STATE, CONTENT ".
                " FROM PAPER WHERE PAPER_ID = ?";
            $statement = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($statement, 'i',$paperId);

            if (!mysqli_stmt_execute($statement))
                throw new \stove\SQLException($conn->errno, $conn->error);

            // 获取数据并构造Journal对象列表
            mysqli_stmt_bind_result($statement, $title, $keyword, $summary, $write_user, $local_paper_id, $image_url,
                $journal_id, $topic_id, $delivery_ts, $audit_ts, $pub_ts, $reject_ts,
                $click_qty, $recommendation_qty, $words, $images, $spend_time, $state, $content);

            if(mysqli_stmt_fetch($statement)){
                $paper = new Paper($paperId);
                $paper->paperTitle = $title;
                $paper->paperKeyWord = $keyword;
                $paper->paperSummary = $summary;
                $paper->writeUser = $write_user;
                $paper->localPaperId = $local_paper_id;
                $paper->imageURL = $image_url;
                $paper->journalId = $journal_id;
                $paper->topicID = $topic_id;
                $paper->deliveryTS = $delivery_ts;
                $paper->auditTS = $audit_ts;
                $paper->pubTS = $pub_ts;
                $paper->rejectTS = $reject_ts;
                $paper->clickQty = $click_qty;
                $paper->recommendationQty = $recommendation_qty;
                $paper->words = $words;
                $paper->images = $images;
                $paper->spendTime = $spend_time;
                $paper->state = $state;
                $paper->content = $content;

                // 获取用户信息
                $paper->author = stove_get_user_by_id($conn1, $write_user);
            }

        }

        finally{
            if (!$statement)
                mysqli_stmt_close($statement);
            mysqli_close($conn1);
        }

        return $paper;
    }

    public static function getDraftById($id){
        $conn = MySQLConnection::getConnection();
        $draft = null;

        try{
            $draft = self::getDraftByIdWithConn($conn, $id);
        }
        catch(SQLException $sqle){
            throw $sqle;
        }
        finally{
            mysqli_close($conn);
        }

        return $draft;
    }

    private static function getDraftByIdWithConn($conn, $id){
        $draft = null;
        $statement = null;

        try{
            mysqli_query($conn, "set names utf8");
            $sql = "SELECT PAPER_TITLE, PAPER_KEYWORD, PAPER_SUMMARY, WRITE_USER, IMAGE_URL, CONTENT, WORDS, ".
                "IMAGES, SPEND_TIME ".
                " FROM PAPER_LOCAL WHERE PAPER_ID = ?";
            $statement = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($statement, 'i',$id);

            if (!mysqli_stmt_execute($statement))
                throw new SQLException($conn->errno, $conn->error);

            // 获取数据并构造Journal对象列表
            mysqli_stmt_bind_result($statement, $title, $keyword, $summary, $write_user, $image_url, $content, $words,
                $images, $spend_time);

            if(mysqli_stmt_fetch($statement)){
                $draft = new PaperDraft($id);
                $draft->paperTitle = $title;
                $draft->paperKeyWord = $keyword;
                $draft->paperSummary = $summary;
                $draft->writeUser = $write_user;

                $draft->imageURL = $image_url;
                $draft->content = $content;
                $draft->words = $words;

                $draft->images = $images;
                $draft->spendTime = $spend_time;
            }

        }

        finally{
            if (!$statement)
                mysqli_stmt_close($statement);
        }

        return $draft;
    }

    // 发表文章，返回Paper对象实例
    public static function publish($draftId, $topicId){
        $conn = MySQLConnection::getConnection(false);
        $draft = null;
        $topic = null;
        $paperId = null;
        $paper = null;

        try{
            // 查询稿件信息
            $draft = self::getDraftByIdWithConn($conn, $draftId);

            // 查询专题信息
            $topic = JournalRepository::getTopicByIdWithConn($conn, $topicId);

            // 发表稿件
            $draft->publish($conn, $topic);

            $paperId = stove_get_last_insert_id($conn);

            // 查询稿件信息
            $paper = self::getPaperByIdWithConn($conn, $paperId);
            $paper->topic = $topic;
            $paper->journal = $topic->journal;

            mysqli_commit($conn);
        }
        catch(SQLException $sqle){
            mysqli_rollback($conn);
            throw $sqle;
        }
        finally{
            mysqli_close($conn);
        }


        return $paper;
    }

    // 得到刊物下的文章列表
    public static function getPaperListInJournal($journalId, $orderBy, $start, $rows){
        $conn = MySQLConnection::getConnection();
        $conn1 = MySQLConnection::getConnection();
        $papers = array();
        $statement = null;

        try{
            mysqli_query($conn, "set names utf8");
            $sql = "SELECT PAPER_ID, PAPER_TITLE, PAPER_KEYWORD, PAPER_SUMMARY, WRITE_USER, LOCAL_PAPER_ID, IMAGE_URL, ".
                " JOURNAL_ID, TOPIC_ID, DELIVERY_TS, AUDIT_TS, PUB_TS, REJECT_TS,  ".
                " CLICK_QTY, RECOMMENDATION_QTY, WORDS, IMAGES, SPEND_TIME, STATE ".
                " FROM PAPER WHERE STATE = '2' AND JOURNAL_ID = ? ".
                " ORDER BY ".($orderBy == "1" ? " PUB_TS DESC " : " RECOMMENDATION_QTY DESC ").
                " LIMIT ?, ?";
            $statement = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($statement, 'iii',$journalId, $start,$rows);

            if (!mysqli_stmt_execute($statement))
                throw new SQLException($conn->errno, $conn->error);

            // 获取数据并构造Journal对象列表
            mysqli_stmt_bind_result($statement, $id, $title, $keyword, $summary, $write_user, $local_paper_id, $image_url,
                $journal_id, $topic_id, $delivery_ts, $audit_ts, $pub_ts, $reject_ts,
                $click_qty, $recommendation_qty, $words, $images, $spend_time, $state);

            $i = 0;
            while(mysqli_stmt_fetch($statement)){
                $paper = new Paper($id);
                $paper->paperTitle = stove_trans_4_json($title);
                $paper->paperKeyWord = stove_trans_4_json($keyword);
                $paper->paperSummary = stove_trans_4_json($summary);
                $paper->writeUser = $write_user;
                $paper->localPaperId = $local_paper_id;
                $paper->imageURL = $image_url;
                $paper->journalId = $journal_id;
                $paper->topicID = $topic_id;
                $paper->deliveryTS = $delivery_ts;
                $paper->auditTS = $audit_ts;
                $paper->pubTS = $pub_ts;
                $paper->rejectTS = $reject_ts;
                $paper->clickQty = $click_qty;
                $paper->recommendationQty = $recommendation_qty;
                $paper->words = $words;
                $paper->images = $images;
                $paper->spendTime = $spend_time;
                $paper->state = $state;
                $papers[$i] = $paper;

                // 获取用户信息
                $paper->author = stove_get_user_by_id($conn1, $write_user);
                $i++;
            }

        }

        finally{
            if (!$statement)
                mysqli_stmt_close($statement);
            mysqli_close($conn);
            mysqli_close($conn1);
        }

        return $papers;
    }
}
