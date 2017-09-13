<?php
/**
 * Created by IntelliJ IDEA.
 * User: lrj
 * Date: 2015/11/20
 * Time: 11:33
 */

namespace stove;

require_once(__DIR__."/StoveConfig.php");

/**
 * Class StoveLogger
 * @package stove
 * 职责：负责记录日志
 * 日志级别
 *   1-错误
 *   2-告警
 *   3-信息
 *   4-调试
 */
class StoveLogger
{
    public static function log($msg, $level=1){
        $loglevel = constant("LOG_LEVEL");
        if ($level <= $loglevel) {
            $file = constant("LOG_ROOT") . "/stove.log";
            $date = date('Y-m-d H:i:s', time());
            $log_msg = $date . ' ' . $_SERVER['REMOTE_ADDR'] . ' ' . $msg . "\n";
            error_log($log_msg, 3, $file) or die("Log to " . $file . " failure.");
        }
    }
}