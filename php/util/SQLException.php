<?php
/**
 * Created by IntelliJ IDEA.
 * User: lrj
 * Date: 2015/11/23
 * Time: 22:04
 */

namespace stove;


class SQLException extends \Exception
{
    private $sqlcode;
    private $sqlmsg;

    public function __construct($sqlcode, $sqlmsg)
    {
        $this->sqlcode=$sqlcode;
        $this->sqlmsg=$sqlmsg;
    }

    public function getException(){
        return $this->sqlcode.':'.$this->sqlmsg;
    }

    public function getSQLCode(){
        return $this->sqlcode;
    }

    public function getSQLMessage(){
        return $this->sqlmsg;
    }
}