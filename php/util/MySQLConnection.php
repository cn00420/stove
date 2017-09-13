<?php

/**
 * Created by IntelliJ IDEA.
 * User: lrj
 * Date: 2015/11/20
 * Time: 11:32
 */
namespace stove;

require_once(__DIR__."/StoveConfig.php");
require_once(__DIR__."/SQLException.php");
require_once(__DIR__."/StoveLogger.php");

class MySQLConnection
{
    public static function getConnection($auto_commit=true){
        $mysql_server= constant("MYSQL_SERVER");
        $mysql_user= constant("MYSQL_USER");
        $mysql_pwd= constant("MYSQL_PWD");
        $conn = mysqli_connect($mysql_server, $mysql_user, $mysql_pwd);
        if (!$conn) {
            $sqle = new \stove\SQLException(mysqli_connect_errno(), mysqli_connect_error());
            throw $sqle;
        }
        mysqli_autocommit($conn, $auto_commit);
        mysqli_select_db($conn, constant("MYSQL_DB"));
        return $conn;
    }
}