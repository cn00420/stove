<?php
/**
 * Created by IntelliJ IDEA.
 * User: lrj
 * Date: 2016/1/1
 * Time: 20:52
 */

namespace stove;

require_once(__DIR__ . "/../util/StoveConfig.php");
require_once(__DIR__ . "/../util/MySQLConnection.php");
require_once(__DIR__ . "/../util/SQLException.php");
require_once(__DIR__ . "/../util/StoveException.php");
require_once(__DIR__ . "/../util/StoveUtil.php");

class User
{
    private $loginAcct, $password, $alias, $gender, $mobilePhone;
    private $userId = -1;
    private $userDesc, $registerDate, $imageURL, $figureURL;

    public function  __construct()
    {

    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }

    public function login()
    {
        $conn = MySQLConnection::getConnection();
        $statement = null;
        try {
            mysqli_query($conn, "set names utf8");
            $sql = "SELECT USER_ID FROM USER WHERE LOGIN_ACCT = ? AND PASSWORD= ? ";
            $statement = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($statement, 'ss', $this->loginAcct, $this->password);

            if (!mysqli_stmt_execute($statement))
                throw new \stove\SQLException($conn->errno, $conn->error);

            $this->userId = -2;
            mysqli_stmt_bind_result($statement, $this->userId);
            mysqli_stmt_fetch($statement);
        } catch (SQLException $sqle) {
            throw $sqle;
        } finally {
            if (!$statement)
                mysqli_stmt_close($statement);
            mysqli_close($conn);
        }
    }

    public function register()
    {
        $conn = MySQLConnection::getConnection(false);
        $statement = null;
        try {
            mysqli_query($conn, "set names utf8");
            $user_id = -1;
            $user_id = $this->getUserByLoginAccount($conn, $this->loginAcct);
            if ($user_id <> -1) {
                throw new StoveException("账号: $this->loginAcct ($user_id) 已经存在", -1001);
                return;
            }
            $sql = "INSERT INTO USER(LOGIN_ACCT, PASSWORD, ALIAS) VALUES(?, ?, ?)";
            $statement = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($statement, 'sss', $this->loginAcct, $this->password, $this->alias);
            mysqli_stmt_execute($statement);

            $this->userId = stove_get_last_insert_id($conn);
            mysqli_commit($conn);

        } catch (SQLException $sqle) {
            throw $sqle;
        } finally {
            if (!$statement)
                mysqli_stmt_close($statement);
            mysqli_close($conn);
        }
    }


    /**
     * 判断用户是否已经存在
     * @param $LoginAccount 用户账号
     */
    public function userExists($LoginAccount)
    {
        $conn = MySQLConnection::getConnection();
        try {
            $sql = "SELECT USER_ID FROM USER WHERE LOGIN_ACCT = ? LIMIT 1";
        } finally {
            mysqli_close($conn);
        }
    }

    public function getUserByLoginAccount($conn, $LoginAccount)
    {
        $sql = "SELECT USER_ID FROM USER WHERE LOGIN_ACCT = ? LIMIT 1";
        $statement = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($statement, 's', $this->loginAcct);

        $user_id = -1;
        if (!mysqli_stmt_execute($statement))
            throw new \stove\SQLException($conn->errno, $conn->error);

        mysqli_stmt_bind_result($statement, $user_id);
        mysqli_stmt_fetch($statement);

        return $user_id;
    }

    public function toJSON()
    {
        $json = '{"USER_ID":"'.$this->userId.'", "LOGIN_ACCT":"'.$this->loginAcct.'", "PASSWORD":"'
            .$this->password.'", "ALIAS":"'.$this->alias.'", "GENDER":"'.$this->gender.'", "REGISTER_DATE":"'
            .$this->registerDate.'", "IMAGE_URL":"'.stove_get_image_url($this->imageURL)
            .'", "MOBILE_PHONE":"'.$this->mobilePhone.'","USER_DESC":"'.$this->userDesc.'","FIGURE_URL":"'.stove_get_image_url($this->figureURL)
            .'"}';
        return $json;
    }
}

//这个方法会被其他的php文件调用，所以没有定义到Repository类里面
function stove_get_user_by_id($conn, $userId)
{
    $sql = "SELECT LOGIN_ACCT, PASSWORD, ALIAS, USER_DESC, GENDER, MOBILE_PHONE, REGISTER_DATE, IMAGE_URL, FIGURE_URL "
        . " FROM USER WHERE USER_ID = ? ";
    $statement = null;

    try {
        mysqli_query($conn, "set names utf8");
        $statement = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($statement, 'i', $userId);

        if (!mysqli_stmt_execute($statement))
            throw new SQLException($conn->errno, $conn->error);

        mysqli_stmt_bind_result($statement, $loginAcct, $password, $alias, $userDesc, $gender, $mobilePhone, $registerDate,
            $imageURL, $figuerURL);

        $user = null;
        if (mysqli_stmt_fetch($statement)) {
            $user = new User();
            $user->loginAcct = $loginAcct;
            $user->password = $password;
            $user->alias = $alias;
            $user->gender = $gender;
            $user->mobilePhone = $mobilePhone;

            $user->userId = $userId;
            $user->userDesc = $userDesc;
            $user->registerDate = $registerDate;
            $user->imageURL = $imageURL;
            $user->figureURL = $figuerURL;
        }
    } finally {
        if (!$statement)
            mysqli_stmt_close($statement);
    }

    return $user;
}

