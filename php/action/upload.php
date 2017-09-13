<?php

require_once(__DIR__."/../domain/File.php");
require_once(__DIR__."/../util/StoveResponse.php");
require_once(__DIR__."/../util/StoveLogger.php");
require_once(__DIR__."/../util/SQLException.php");

if (!isset($_FILES['image_file'])){
    $response = new \stove\StoveResponse(-1001, "文件还没有上传完整");
    echo $response->toJson();
}
else{
    $fu = new \stove\FileUtil($_FILES['image_file']['name']);
    $fu->save($_FILES['image_file']);
    $response = new \stove\StoveResponse(0);
    echo $response->toJson();
}

