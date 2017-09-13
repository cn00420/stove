<?php
/**
 * Created by IntelliJ IDEA.
 * User: lrj
 * Date: 2015/11/20
 * Time: 14:06
 */

namespace stove;


class StoveResponse{
    private $status = 0, $description = '', $bo='';

    public function __construct($status, $description='', $bo='')
    {
        $this->status = $status;
        $this->description = $description;
        $this->bo = $bo;
    }

    public function toJson(){
        $json = '{"status":"'.$this->status.'","description":"'.$this->description.'","bo":""}';
        if (strlen($this->bo) > 0)
            $json = '{"status":"'.$this->status.'","description":"'.$this->description.'","bo":'.$this->bo.'}';
        return $json;
    }
}