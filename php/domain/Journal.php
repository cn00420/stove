<?php
/**
 * Created by IntelliJ IDEA.
 * User: lrj
 * Date: 2016/1/1
 * Time: 20:58
 */
namespace stove;

require_once(__DIR__ . "/../util/StoveConfig.php");
require_once(__DIR__ . "/../util/MySQLConnection.php");
require_once(__DIR__ . "/../util/SQLException.php");
require_once(__DIR__ . "/../util/StoveException.php");
require_once(__DIR__ . "/../util/StoveUtil.php");
require_once(__DIR__ . "/../domain/User.php");

// 刊物类
class Journal
{
    public $journalId, $journalName, $journalDesc, $sponsorId, $sponsorAcct, $pubCode, $state, $imageURL, $subscribeQty;
    public $createTS, $author, $statistic, $tags;

    public function __construct($journal_id)
    {
        $this->journalId = $journal_id;
        $this->state = "1";
    }

    public function toJSON()
    {
        $author_string = (isset($this->author)) ? $this->author->toJSON() : "{}";
        $stat_string = (isset($this->statistic)) ? json_encode($this->statistic) : "{}";

        $json = '{"JOURNAL_ID":"' . $this->journalId . '", "JOURNAL_NAME":"' . $this->journalName . '", "JOURNAL_DESC":"'
            . $this->journalDesc . '", "SPONSOR_ID":"' . $this->sponsorId . '", "SPONSOR_ACCT":"' . $this->sponsorAcct . '", "CREATE_TS":"'
            . $this->createTS . '", "PUB_CODE":"' . $this->pubCode
            . '", "SUBSCRIBE_QTY":"' . $this->subscribeQty
            . '", "AUTHOR":' . $author_string
            . ', "STATISTICS":' . $stat_string
            . ', "IMAGE_URL":"' . $this->imageURL
            . '", "TAGS":"'.$this->tags
            . '"}';
        return $json;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function getStatistics()
    {
        $conn = MySQLConnection::getConnection();

        try {
            $topic_qty = $this->countTopics($conn);
            $paper_qty = $this->countPapers($conn);

            $this->statistic = new JournalStatistics();
            $this->statistic->topicQty = $topic_qty;
            $this->statistic->paperQty = $paper_qty;
        } catch (\Exception $e) {
            throw $e;
        } finally {
            if (!$conn)
                mysqli_close($conn);
        }
    }

    public function countTopics($conn)
    {
        $topic_qty = 0;
        $statement = null;

        try {
            $sql = "SELECT COUNT(1) FROM journal_topic WHERE JOURNAL_ID = ?";
            $statement = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($statement, 'i', $this->journalId);

            if (!mysqli_stmt_execute($statement))
                throw new SQLException($conn->errno, $conn->error);

            // 获取数据并构造Journal对象列表
            mysqli_stmt_bind_result($statement, $topic_qty);

            mysqli_stmt_fetch($statement);

        } catch (\Exception $e) {
            throw $e;
        } finally {
            if (!$statement)
                mysqli_stmt_close($statement);
        }

        return $topic_qty;
    }

    public function countPapers($conn)
    {
        $paper_qty = 0;
        $statement = null;

        try {
            $sql = "SELECT COUNT(1) FROM paper WHERE JOURNAL_ID = ?";
            $statement = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($statement, 'i', $this->journalId);

            if (!mysqli_stmt_execute($statement))
                throw new SQLException($conn->errno, $conn->error);

            // 获取数据并构造Journal对象列表
            mysqli_stmt_bind_result($statement, $paper_qty);

            mysqli_stmt_fetch($statement);

        } catch (\Exception $e) {
            throw $e;
        } finally {
            if (!$statement)
                mysqli_stmt_close($statement);
        }

        return $paper_qty;
    }

    // 保存刊物信息
    public function save($conn)
    {
        $statement = null;
        $current = time();

        mysqli_query($conn, "set names utf8");
        $sql = "UPDATE JOURNAL SET JOURNAL_NAME = ?, JOURNAL_DESC = ?, IMAGE_URL = ?, MODIFY_TS = str_to_date(?, '%Y-%m-%d %H:%i:%s')".
            " WHERE JOURNAL_ID = ?";

        $statement = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($statement, 'ssssi', $this->journalName, $this->journalDesc, $this->imageURL,
            $current, $this->journalId);

        mysqli_stmt_execute($statement);
        mysqli_stmt_close($statement);
    }

    // 保存刊物和标签的关系，使用multi_query
    public function relateTag($conn, $tags){
        $this->deleteTags($conn);
        for($i = 0; $i < count($tags); $i++){
            $sql = 'INSERT INTO JOURNAL_TAGS(JOURNAL_ID, TAG_NAME, TAG_SEQ) VALUES ('.$this->journalId.", '".$tags[$i]."',".$i.")";
            mysqli_query($conn, $sql);
        }
    }

    // 删除刊物关联的所有标签
    private function deleteTags($conn){
        $sql = "DELETE FROM JOURNAL_TAGS WHERE JOURNAL_ID = ".$this->journalId;
        mysqli_query($conn, $sql);
    }

    // 读取刊物的标签，标签之间以逗号隔开
    public function readTags($conn){
        mysqli_query($conn, "set names utf8");
        $sql = "SELECT TAG_NAME FROM JOURNAL_TAGS WHERE JOURNAL_ID = ? ORDER BY TAG_SEQ ";
        $statement = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($statement, 'i', $this->journalId);
        mysqli_stmt_execute($statement);

        // 获取数据并构造Journal对象列表
        mysqli_stmt_bind_result($statement, $tag_name);
        $i = 0;
        $tags = '';
        while (mysqli_stmt_fetch($statement)) {
            $tags .= $tag_name.',';
        }

        $tags = rtrim($tags, ',');
        $this->tags = $tags;

        return $tags;
    }
}

// 专题类
class Topic
{
    private $topicId, $journalId, $journal, $topicName, $topicDesc, $imageURL, $state, $topicURL, $sponsorId;

    public function __construct($id)
    {
        $this->topicId = $id;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function toJSON()
    {
        $json = '{"TOPIC_ID":"' . $this->topicId . '", "TOPIC_NAME":"' . $this->topicName . '", "TOPIC_DESC":"'
            . $this->topicDesc . '", "SPONSOR_ID":"' . $this->sponsorId . '", "JOURNAL_ID":"' . $this->journalId
            . '", "JOURNAL":"' . ($this->journal == null ? '' : $this->journal->toJSON())
            . '", "IMAGE_URL":"' . $this->imageURL . '"}';
        return $json;
    }
}

class JournalRepository
{
// orderBy: 1-按创建时间倒排序 2-按订阅量排序
    public static function getJournalList($orderBy, $start, $rows)
    {
        $conn = MySQLConnection::getConnection();
        $conn1 = MySQLConnection::getConnection();
        $journals = array();
        $statement = null;

        try {
            mysqli_query($conn, "set names utf8");
            $sql = "SELECT JOURNAL_ID, JOURNAL_NAME, JOURNAL_DESC, SPONSOR_ID, SPONSOR_ACCT, CREATE_TS, IMAGE_URL, PUB_CODE, STATE, SUBSCRIBE_QTY " .
                " FROM journal ORDER BY " . ($orderBy == "1" ? " CREATE_TS DESC " : " SUBSCRIBE_QTY DESC ") .
                " LIMIT ?, ?";
            $statement = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($statement, 'ii', $start, $rows);

            if (!mysqli_stmt_execute($statement))
                throw new \stove\SQLException($conn->errno, $conn->error);

            // 获取数据并构造Journal对象列表
            mysqli_stmt_bind_result($statement, $journal_id, $journal_name, $journal_desc, $sponsor_id, $sponsor_acct,
                $create_ts, $image_url, $pub_code, $state, $subscribe_qty);

            $i = 0;
            while (mysqli_stmt_fetch($statement)) {
                $image_url = stove_get_image_url($image_url);
                $journals[$i] = new Journal($journal_id);
                $journals[$i]->journalName = $journal_name;
                $journals[$i]->journalDesc = $journal_desc;
                $journals[$i]->sponsorId = $sponsor_id;
                $journals[$i]->sponsorAcct = $sponsor_acct;
                $journals[$i]->createTS = $create_ts;
                $journals[$i]->imageURL = $image_url;
                $journals[$i]->pubCode = $pub_code;
                $journals[$i]->state = $state;
                $journals[$i]->subscribeQty = $subscribe_qty;
                $journals[$i]->author = stove_get_user_by_id($conn1, $sponsor_id);

                $i++;
            }

        } finally {
            if (!$statement)
                mysqli_stmt_close($statement);
            mysqli_close($conn);
            mysqli_close($conn1);
        }

        return $journals;
    }

    public static function getJournalById($id)
    {
        $conn = MySQLConnection::getConnection();
        $journal = null;

        try {
            $journal = self::getJournalByIdWithConn($conn, $id);
        } catch (SQLException $sqle) {
            throw $sqle;
        } finally {
            mysqli_close($conn);
        }

        return $journal;
    }

    public static function getJournalByIdWithConn($conn, $id)
    {
        $journal = null;
        $statement = null;

        try {
            mysqli_query($conn, "set names utf8");
            $sql = "SELECT JOURNAL_ID, JOURNAL_NAME, JOURNAL_DESC, SPONSOR_ID, SPONSOR_ACCT, CREATE_TS, IMAGE_URL, PUB_CODE, STATE, SUBSCRIBE_QTY " .
                " FROM journal WHERE JOURNAL_ID = ?";
            $statement = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($statement, 'i', $id);

            if (!mysqli_stmt_execute($statement))
                throw new SQLException($conn->errno, $conn->error);

            // 获取数据并构造Journal对象列表
            mysqli_stmt_bind_result($statement, $journal_id, $journal_name, $journal_desc, $sponsor_id, $sponsor_acct,
                $create_ts, $image_url, $pub_code, $state, $subscribe_qty);

            if (mysqli_stmt_fetch($statement)) {
                $journal = new Journal($id);
                $image_url = stove_get_image_url($image_url);
                $journal->journalName = $journal_name;
                $journal->journalDesc = $journal_desc;
                $journal->sponsorId = $sponsor_id;
                $journal->sponsorAcct = $sponsor_acct;
                $journal->createTS = $create_ts;
                $journal->imageURL = $image_url;
                $journal->pubCode = $pub_code;
                $journal->state = $state;
                $journal->subscribeQty = $subscribe_qty;

                mysqli_stmt_close($statement);
                $journal->author = stove_get_user_by_id($conn, $sponsor_id);

                // 读取刊物标签信息
                $journal->readTags($conn);
            }

        } finally {
            if (!$statement)
                mysqli_stmt_close($statement);
        }

        return $journal;
    }

    // 根据ID查询专题，返回专题对象
    public static function getTopicById($topicId)
    {
        $topic_conn = MySQLConnection::getConnection();
        $topic = null;

        try {
            $topic = self::getTopicByIdWithConn($topic_conn, $topicId);
        } catch (SQLException $sqle) {
            throw $sqle;
        } finally {
            mysqli_close($topic_conn);
        }

        return $topic;
    }

    public static function getTopicByIdWithConn($conn, $topicId)
    {
        $sql = "SELECT JOURNAL_ID, TOPIC_NAME, TOPIC_DESC, IMAGE_URL, STATE, TOPIC_URL, SPONSOR_ID FROM JOURNAL_TOPIC " .
            " WHERE TOPIC_ID = ?";
        $statement = null;
        $topic = null;

        try {
            mysqli_query($conn, "set names utf8");

            $statement = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($statement, "i", $topicId);

            if (!mysqli_stmt_execute($statement))
                throw new SQLException($conn->errno, $conn->error);

            mysqli_stmt_bind_result($statement, $journal_id, $topic_name, $topic_desc, $image_url, $state, $topic_url,
                $sponsor_id);
            if (mysqli_stmt_fetch($statement)) {
                $topic = new Topic($topicId);
                $topic->journalId = $journal_id;
                $topic->topicName = $topic_name;
                $topic->topicDesc = $topic_desc;
                $topic->imageURL = $image_url;
                $topic->state = $state;
                $topic->topicURL = $topic_url;
                $topic->sponsorId = $sponsor_id;

                mysqli_stmt_close($statement);

                // 查询刊物信息
                $journal = self::getJournalByIdWithConn($conn, $journal_id);
                $topic->journal = $journal;
            }
        } catch (SQLException $sqle) {
            throw $sqle;
        } finally {
            if (!$statement)
                mysqli_stmt_close($statement);
        }

        return $topic;
    }

    public static function matchJournalByName($name)
    {
        $conn = MySQLConnection::getConnection();
        $journals = array();
        $statement = null;

        try {
            mysqli_query($conn, "set names utf8");
            $sql = "SELECT JOURNAL_ID, JOURNAL_NAME FROM JOURNAL WHERE JOURNAL_NAME LIKE ? " .
                " LIMIT 0, 10";
            $statement = mysqli_prepare($conn, $sql);

            $name = '%' . $name . '%';
            mysqli_stmt_bind_param($statement, 's', $name);

            if (!mysqli_stmt_execute($statement))
                throw new SQLException($conn->errno, $conn->error);

            // 获取数据并构造Journal对象列表
            mysqli_stmt_bind_result($statement, $journal_id, $journal_name);

            $i = 0;
            while (mysqli_stmt_fetch($statement)) {
                $journals[$i] = new Journal($journal_id);
                $journals[$i]->journalName = $journal_name;
                $i++;
            }

        } catch (SQLException $sqle) {
            throw $sqle;
        } finally {
            if (!$statement)
                mysqli_stmt_close($statement);
            mysqli_close($conn);
        }

        return $journals;
    }

    public static function matchTopicByName($journalId, $name)
    {
        $conn = MySQLConnection::getConnection();
        $topics = array();
        $statement = null;

        try {
            mysqli_query($conn, "set names utf8");
            $sql = "SELECT TOPIC_ID, TOPIC_NAME FROM JOURNAL_TOPIC WHERE TOPIC_NAME LIKE ? AND JOURNAL_ID = ? " .
                " LIMIT 0, 10";
            $statement = mysqli_prepare($conn, $sql);

            $name = '%' . $name . '%';
            mysqli_stmt_bind_param($statement, 'si', $name, $journalId);

            if (!mysqli_stmt_execute($statement))
                throw new SQLException($conn->errno, $conn->error);

            // 获取数据并构造Journal对象列表
            mysqli_stmt_bind_result($statement, $topicId, $topic_name);

            $i = 0;
            while (mysqli_stmt_fetch($statement)) {
                $topics[$i] = new Topic($topicId);
                $topics[$i]->topicName = $topic_name;
                $i++;
            }

        } catch (SQLException $sqle) {
            throw $sqle;
        } finally {
            if (!$statement)
                mysqli_stmt_close($statement);
            mysqli_close($conn);
        }

        return $topics;
    }

    // 创建新刊物, 返回新刊物ID
    public static function createJournal($conn, $name, $tags, $desc, $url)
    {
        $statement = null;
        $journal_id = -1;

        try {
            $user_id = $_SESSION['user_id'];
            $login_acct = $_SESSION['login_acct'];

            $current = date('Y-m-d H:i:s', time());

            mysqli_query($conn, "set names utf8");
            $sql = "INSERT INTO JOURNAL(JOURNAL_NAME, JOURNAL_DESC, SPONSOR_ID, SPONSOR_ACCT, IMAGE_URL, STATE, CREATE_TS, MODIFY_TS) " .
                " VALUES(?, ?, ?, ?, ?, '1', str_to_date(?, '%Y-%m-%d %H:%i:%s'), str_to_date(?, '%Y-%m-%d %H:%i:%s'))";
            $statement = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($statement, 'ssissss', $name, $desc, $user_id, $login_acct, $url, $current, $current);
            mysqli_stmt_execute($statement);
            mysqli_stmt_close($statement);

            $journal_id = stove_get_last_insert_id($conn);

            // 创建刊物标签定义
            JournalTagsDefinition::saveTagsDefinition($conn, $tags);

            // 创建刊物和标签的关系
            $journal = new Journal($journal_id);
            $journal->relateTag($conn, $tags);

        } catch (SQLException $sqle) {
            throw $sqle;
        } finally {
            if (!$statement)
                mysqli_stmt_close($statement);
        }

        return $journal_id;
    }

    // 得到刊物标签定义
    public static function getJournalTagsDef($conn, $key, $start, $rows){
        $journals = array();
        $statement = null;

        try {
            mysqli_query($conn, "set names utf8");
            $sql = "SELECT TAG_NAME FROM JOURNAL_TAGS_DEF WHERE TAG_NAME LIKE ? ORDER BY TAG_NAME " .
                " LIMIT ?, ?";
            $statement = mysqli_prepare($conn, $sql);

            $matchkey = '%'.$key.'%';
            mysqli_stmt_bind_param($statement, 'sii', $matchkey, $start, $rows);

            if (!mysqli_stmt_execute($statement))
                throw new SQLException($conn->errno, $conn->error);

            // 获取数据并构造Journal对象列表
            mysqli_stmt_bind_result($statement, $tag_name);

            $i = 0;
            while (mysqli_stmt_fetch($statement)) {
                $journals[$i] = $tag_name;
                $i++;
            }

        } finally {
            if (!$statement)
                mysqli_stmt_close($statement);
        }

        return $journals;
    }
}

class JournalStatistics
{
    public $topicQty = 0, $paperQty = 0;

    public function __construct()
    {
    }
}

class JournalTagsDefinition{
    // 保存标签定义，$tags是一个数组
    public static function saveTagsDefinition($conn, $tags){
        $current = date('Y-m-d H:i:s', time());
        for($i = 0; $i < count($tags); $i++){
            if (!self::tagDefinitionExists($conn, $tags[$i])){
                self::addTagDefinition($conn, $tags[$i], $current);
            }
        }
    }

    // 判断标签定义是否已经存在
    public static function tagDefinitionExists($conn, $tag){
        $result = false;

        $sql = "SELECT TAG_NAME FROM JOURNAL_TAGS_DEF WHERE TAG_NAME = ?";
        $statement = null;
        try{
            $statement = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($statement, 's', $tag);

            if (!mysqli_stmt_execute($statement))
                throw new SQLException($conn->errno, $conn->error);

            // 获取数据并构造Journal对象列表
            mysqli_stmt_bind_result($statement, $tag_name);
            mysqli_stmt_fetch($statement);

            if (isset($tag_name) && (strlen($tag_name) > 0))
                $result = true;
        }
        finally{
            if ($statement)
                mysqli_stmt_close($statement);
        }

        return $result;
    }

    public static function addTagDefinition($conn, $tag, $time){
        $sql = "INSERT INTO JOURNAL_TAGS_DEF(TAG_NAME, IS_SYS, CREATE_TS) VALUES(?, '2', str_to_date(?, '%Y-%m-%d %H:%i:%s'))";
        $statement1 = null;

        try{
            $statement1 = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($statement1, 'ss', $tag, $time);

            if (!mysqli_stmt_execute($statement1))
                throw new SQLException($conn->errno, $conn->error);
        }
        finally{
            if ($statement1)
                mysqli_stmt_close($statement1);
        }
    }
}