<?php
/**
 * Created by IntelliJ IDEA.
 * User: lrj
 * Date: 2015/11/20
 * Time: 11:34
 */

require_once(__DIR__. "/../domain/User.php");
require_once(__DIR__."/../util/StoveResponse.php");
require_once(__DIR__."/../util/StoveLogger.php");
require_once(__DIR__."/../util/SQLException.php");

$action = $_POST['action'];
if (!$action)
  $action="login";

switch ($action) {
    case "login":
       $login_account = $_POST["user_code"];
       $user_pwd = $_POST["user_pwd"];
       login($login_account, $user_pwd);
       break;
    case "get-session":
       get_session();
       break;
    case "register":
        register();
        break;
    default:
        $response = new \stove\StoveResponse(-101, "无效的动作");
        echo $response->toJson();
}

function login($loginAcct, $pwd){
    $user = new \stove\User();
    $user->loginAcct = $loginAcct;
    $user->password = $pwd;

    try {
      $user->login();
    }
    catch(\stove\SQLException $sqle){
        \stove\StoveLogger::log($sqle->getException(), 1);
        $response = new \stove\StoveResponse($sqle->getSQLCode(), $sqle->getSQLMessage());
        echo $response->toJson();
        return;
    }
    catch(Exception $e){
        \stove\StoveLogger::log($e->getCode()." ".$e->getMessage(), 1);
        $response = new \stove\StoveResponse($e->getCode(), $e->getMessage());
        echo $response->toJson();
        return;
    }

    $user_id = $user->userId;

    // 登录成功
    if ( $user_id> -1){
        // 设置会话信息
        session_start();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['login_acct'] = $loginAcct;
        $_SESSION['pwd'] = $pwd;
        $_SESSION['clent_ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['login_time'] = $_SERVER['REQUEST_TIME'];

        // 记录日志
        $msg = "用户:".$loginAcct."登录成功";
        \stove\StoveLogger::log($msg, 3);

        // 返回
        $response = new \stove\StoveResponse(0, '登录成功', $user->toJSON());
        echo $response->toJson();
    }
    else{
        // 记录日志
        $msg = "用户:".$user->loginAcct."登录失败,密码：".$user->password;
        \stove\StoveLogger::log($msg, 2);

        // 返回
        $response = new \stove\StoveResponse(-1, "用户名或者密码错误");
        echo $response->toJson();
    }
}

function register(){
    $login_account = $_POST["user_code"];
    $user_pwd = $_POST["user_pwd"];
    $user_nick = $_POST["user_nick"];
    $user = new \stove\User();
    $user->loginAcct = $login_account;
    $user->password = $user_pwd;
    $user->alias = $user_nick;

    try {
        $user->register();
    }
    catch(\stove\SQLException $sqle){
        \stove\StoveLogger::log($sqle->getException(), 1);
        $response = new \stove\StoveResponse($sqle->getSQLCode(), $sqle->getSQLMessage());
        echo $response->toJson();
        return;
    }
    catch(Exception $e){
        \stove\StoveLogger::log($e->getCode()." ".$e->getMessage(), 1);
        $response = new \stove\StoveResponse($e->getCode(), $e->getMessage());
        echo $response->toJson();
        return;
    }

    $user_id = $user->userId;

    // 注册成功
    if ( $user_id> -1){
        // 设置会话信息
        session_start();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['login_acct'] = $login_account;
        $_SESSION['pwd'] = $user_pwd;
        $_SESSION['clent_ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['login_time'] = $_SERVER['REQUEST_TIME'];

        // 记录日志
        $msg = "用户:".$user->loginAcct."注册并登录成功";
        \stove\StoveLogger::log($msg, 3);

        // 返回
        $response = new \stove\StoveResponse(0);
        echo $response->toJson();
    }
    else{
        // 记录日志
        $msg = "用户:".$user->loginAcct."注册失败";
        \stove\StoveLogger::log($msg, 2);

        // 返回
        $response = new \stove\StoveResponse(-1, "注册失败");
        echo $response->toJson();
    }
}

function get_session(){
    $status = 0;
    $response = null;
    session_start();
    if (!isset($_SESSION['user_id'])) {   // 没有session
        if (isset($_POST['user_code'])) {   // 如果设置了用户密码，自动登录
            login();
            return;
        }
        else {
            $status = -1;
            $response = new \stove\StoveResponse($status);
            echo $response->toJson();
            return;
        }
    }
    else {   // 有session则跳过登录，直接返回成功信息
        $response = new \stove\StoveResponse($status, '', '{"USER_ID": "'.$_SESSION['user_id'].'"}');
        echo $response->toJson();
    }
}
