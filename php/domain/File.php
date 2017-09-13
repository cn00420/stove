<?php
/**
 * Created by IntelliJ IDEA.
 * User: lrj
 * Date: 2015/12/6
 * Time: 22:18
 */

namespace stove;

require_once(__DIR__."/../util/StoveConfig.php");
require_once(__DIR__."/../util/StoveException.php");

class FileUtil{
  private $LocalName, $FullName;   // 本地文件名和服务器端的全文件名

  public function __construct($localName){
      $this->LocalName = $localName;
  }

  public function save($f){
      // 服务器端按年/月/日保存,文件名为用户ID_时分秒.文件后缀
      $date = date("Y-m-d-H-i-s");
      $parts = explode('-', $date);
      $year = $parts[0];
      $month = $parts[1];
      $day = $parts[2];
      $hour = $parts[3];
      $minute = $parts[4];
      $second = $parts[5];
      session_start();
      $user_id = $_SESSION['user_id'];

      $dir = UPLOAD_ROOT.'/'.$year.'/'.$month.'/'.$day;
      if (!is_dir($dir))
          mkdir($dir,0777,true);

      $filename = $f['name'];
      $suffix = substr($filename,strrpos($filename, '.') + 1);
      $newfile = $user_id.'_'.$hour.$minute.$second.(strlen($suffix) == 0 ? '' : '.'.$suffix);

      $fullname = $dir.'/'.$newfile;
      move_uploaded_file($f['tmp_name'], $fullname);

      $this->FullName = $fullname;
      return $this->FullName;
  }
}