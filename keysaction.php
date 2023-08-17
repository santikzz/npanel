<?php

require("includes/config.php");
session_start();
if(!isset($_SESSION["username"])){
    header("Location: login.php");
    die();
}
require("includes/functions.php");
if(isset($_GET["a"], $_GET["id"], $_GET["p"])){
  $action = $_GET["a"];
  $id = $_GET["id"];
  $product = $_GET["p"];
  if($action == "resethwid"){
    $result = $mysqli->query("UPDATE `licenses` SET `hwid`='RESET' WHERE `id` = $id");
    if($result){
      event_log($mysqli, $_SESSION["username"], "HWID RESET A KEY (ID ".$id.")");
      header("Location: keys.php?p=".$product);
    }
  }elseif ($action == "delete"){
    $result = $mysqli->query("DELETE FROM `licenses` WHERE `id` = $id");
    if($result){
      event_log($mysqli, $_SESSION["username"], "DELETED A KEY (ID ".$id.")");
      header("Location: keys.php?p=".$product);
    }
  }elseif($action == "ban"){
    $result = $mysqli->query("UPDATE `licenses` SET `status`='EXPIRED' WHERE `id` = $id AND `status` != 'NOT USED'");
    if($result){
      event_log($mysqli, $_SESSION["username"], "BANNED A KEY (ID ".$id.")");
      header("Location: keys.php?p=".$product);
    }
  }elseif($action == "unban"){
    $result = $mysqli->query("UPDATE `licenses` SET `status`='USED' WHERE `id` = $id AND `status` != 'NOT USED'");
    if($result){
      event_log($mysqli, $_SESSION["username"], "UNBANNED A KEY (ID ".$id.")");
      header("Location: keys.php?p=".$product);
    }
  }

}

?>