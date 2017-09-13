<?php
/**
 * Created by IntelliJ IDEA.
 * User: lrj
 * Date: 2016/2/5
 * Time: 15:48
 */

namespace stove;

require_once(__DIR__."/../util/StoveResponse.php");
require_once(__DIR__."/../util/StoveLogger.php");
require_once(__DIR__."/../util/SQLException.php");
require_once(__DIR__."/../util/StoveUtil.php");
require_once(__DIR__."/../domain/Journal.php");

header("Content-Type: text/html;charset=utf-8");

$journal_id = -1;
if (isset($_GET['journal_id']))
    $journal_id = $_GET['journal_id'];

if ($journal_id > -1) {

    $journal = JournalRepository::getJournalById($journal_id);
    if (!$journal){
      echo '无效的刊物';
      return;
    }

    $journal->getStatistics();

    stove_start_session();

    $smarty = stove_get_template_engine();
    $smarty->assign("journal_name", $journal->journalName);
    $smarty->assign("journal_desc", $journal->journalDesc);
    $smarty->assign("image_url", $journal->imageURL);

    $smarty->assign("figure_url", stove_get_image_url($journal->author->figureURL));
    $smarty->assign("author_alias", $journal->author->alias);
    $smarty->assign("journal_id", $journal_id);

    $smarty->assign("paper_qty", $journal->statistic->paperQty);
    $smarty->assign("topic_qty", $journal->statistic->topicQty);
    $smarty->assign("subscribe_qty", $journal->subscribeQty);

    $is_my_journal = 'false';
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $journal->sponsorId)
        $is_my_journal = 'true';
    $smarty->assign("is_my_journal", $is_my_journal);

    $has_subscribed = 'false';
    $smarty->assign("has_subscribed", $has_subscribed);

    $smarty->display("journal_center.html");
}