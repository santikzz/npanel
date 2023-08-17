<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// check for subdomain
if($_SERVER['HTTP_HOST'] != ""){
  die();
}

// conntect to db
$mysqli = mysqli_init();
$mysqli->real_connect("localhost", "", "", "");

function event_log($mysqli, $user, $event){
  $result = $mysqli->query("INSERT INTO `event_log` (`date`, `user`, `event`) VALUES (NOW(), '".$user."', '".$event."')");
}

// check if post data is set
if(isset($_POST["pkey"], $_POST["hwid"], $_POST["product"])){

  // set params
  $pkey = $_POST["pkey"];
  $hwid = $_POST["hwid"];
  $product = $_POST["product"];

  //echo "key: ".$pkey." / hwid: ".$hwid."<br>";

  // get info about key
  $check = $mysqli->prepare("SELECT `pkey`, `product`, `hwid`, `duration_days`, `expiration_date`, `status` FROM `licenses` WHERE `pkey` = ? AND `product` = ?  LIMIT 1");
  $check->bind_param("ss", $pkey, $product);
  $check->execute();
  $result = $check->get_result();

  // if key exist
  if($result->num_rows > 0){
    
    // get db key data
    $key = mysqli_fetch_assoc($result);

    // get now unix time
    $unix_now = json_decode(file_get_contents("http://worldtimeapi.org/api/timezone/America/Argentina/Buenos_Aires"), true)["unixtime"];

    //echo "unix now: ".$unix_now."<br>";

    // if key is valid and not used
    if($key["status"] == "NOT USED"){

      // calc unix time for expiration date = (now + 86400 * days)
      $unix_expire = $unix_now+(86400*$key["duration_days"]);

      // bind hwid to key and set dates
      $activate = $mysqli->prepare("UPDATE `licenses` SET `hwid` = ?, `activation_date` = ".$unix_now.", `expiration_date` = ".$unix_expire.", `status` = 'USED' WHERE `pkey` = ?");
      $activate->bind_param("ss", $hwid, $pkey);
    
      // check if query ok
      if($activate->execute()){
        echo "unused_valid_key:".$unix_expire;
        event_log($mysqli, "API", "REEDEMED KEY: ".$pkey);
        die();
      }else{
        echo "error_unknown";
        die();
      }
    
    // if key already redeemed
    }elseif($key["status"] == "USED"){

      // if key is expired
      if($unix_now > $key["expiration_date"]){
          // set status to expired
          $expire = $mysqli->prepare("UPDATE `licenses` SET `status`='EXPIRED' WHERE `pkey` = ?");
          $expire->bind_param("s", $pkey);
          // check for errors
          if($expire->execute()){
            echo "error_expired_key";
            event_log($mysqli, "API", "EXPIRED KEY: ".$pkey);
            die();
          }else{
            echo "error_unknown";
            die();
          }
      }

      if($key["hwid"] == "RESET"){
        $reset = $mysqli->prepare("UPDATE `licenses` SET `hwid` = ? WHERE `pkey` = ?");
        $reset->bind_param("ss", $hwid, $pkey);      
        // check if query ok
        if($reset->execute()){
          echo "hwid_reset_ok:".$key["expiration_date"];
          event_log($mysqli, "API", "HWID RESET FOR: ".$pkey);
          die();
        }else{
          echo "error_unknown";
          die();
        }
      }

      // if not expired and hwid match
      if($key["hwid"] == $hwid){
        echo "used_valid_key:".$key["expiration_date"];
        die();
      }else{
      // if hwid not allowed
        echo "error_hwid_not_allowed";
        event_log($mysqli, "API", "INVALID HWID LOGIN ATTEMPT FOR: ".$pkey);
        die();
      }

    // if key expired
    }elseif($key["status"] == "EXPIRED"){
      echo "error_expired_key";
      event_log($mysqli, "API", "EXPIRED KEY: ".$pkey);
      die();
    
    }

  // key not found or invalid
  }else{
    echo "error_invalid_key";
    die();
  }

}else{
  echo "error_no_data";
  die();
}

?>