<?php

require_once("includes/config.php");
session_start();

if(!isset($_SESSION["username"])){
    header("Location: login.php");
    die();
}

if(isset($_GET["p"])){

  $product = $_GET["p"];
  if (!in_array($product, $allowed_products)){
    echo "error: unknown product: (".$product.")";
    die();
  }
  $licenses = $mysqli->query("SELECT `id`, `pkey`, `hwid`, `creation_date`, `duration_days`, `activation_date`, `expiration_date`, `status`, `description` FROM `licenses` WHERE `product` = '".$product."'");

}else{
  header("Location: keys.php?p=".$allowed_products[0]);
  die();
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto">
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
  <link rel="icon" type="image/png" sizes="16x16" href="images/icon.png">
  <title>nimrodcore panel</title>
</head>

<dialog id="deleteDialog">
  <form>
    <div>
    </div>
    <div>
      <h2>Confirm delete?</h2>
      <input type="text" id="idInput" value="" readonly>
      <input type="text" id="idProd" value="" readonly>
      <button value="cancel" formmethod="dialog">Cancel</button>
      <button id="confirmDelete" value="default">Confirm</button>
    </div>
  </form>
</dialog>

  <body>
     <?php require("nav.php"); ?>
          <div class="content-wrapper">
            <div class="page-header">
              <h3 class="page-title"><?php echo strtoupper($product); ?></h3>
            </div>
                 
                <!-- CREATE KEY SECTION -->
                <div class="key_create bg-black">
                  <form class="forms-sample" role="form" method="POST" action="createkey.php">
                    <div class="key_input_group">
                      <div class="key_input">
                        <label>Time (days)</label>
                        <input type="number" id="key_time" name="key_time" value="1">
                      </div>
                      <div class="key_input">
                        <label>Amount</label>
                        <input type="number" id="key_amount" name="key_amount" value="1">
                      </div>
                      <div class="key_input">
                        <label>Mask</label>
                        <select id="key_mask" name="key_mask">
                          <option selected value="XXXXXX-XXXXXX-XXXXXX-XXXXXX-XXXXXX-XXXXXX">Default</option>
                          <option value="custom" disabled>Custom</option>
                        </select>
                      </div>
                      <div class="key_input">
                        <label>Note</label>
                        <input type="text" id="key_description" name="key_description" placeholder="@kiroshi#1337">
                      </div>
                      <div class="key_input key_buttons">
                        <button type="submit" class="btn btn-purple">Generate <i class="fa-solid fa-key"></i></button>
                      </div>
                      <div class="key_input key_buttons">
                        <button type="submit" class="btn btn-purple" name="export" value="true">Generate export <i class="fa-solid fa-key"></i></button>
                      </div>
                    </div>
                  <input type="hidden" name="key_product" value="<?php echo $product; ?>">
                  </form>

                  <div class="table-options">
                    <button type="button" class="btn btn-red" id="hide_expired"><i class="fa-solid fa-eye"></i> <span id="hide_expired_txt">Hide</span> expired</button>
                  </div>

                </div>
                <!-- CREATE KEY SECTION -->

                    <div class="table-responsive">
                      <table class="table">
                        <thead>
                          <tr>
                            <th>KEY</th>
                            <th>HWID</th>
                            <!-- <th>CREATION</th> -->
                            <th>DURATION</th>
                            <!-- <th>USED ON</th> -->
                            <!-- <th>EXPIRATION</th> -->
                            <th>TIME LEFT</th>
                            <th>STATUS</th>
                            <th>NOTE</th>
                            <th>ACTIONS</th>
                          </tr>
                        </thead>
                        <tbody>

                        <?php while ($row = mysqli_fetch_assoc($licenses)){ ?>

                          <tr class='<?php echo $row["status"]=="EXPIRED"?"expired":""; ?>'>
                            <td><?php echo $row["pkey"]; ?></td>
                            <td><?php echo $row["hwid"]; ?></td>
                            <!-- <td><?php //echo $row["creation_date"]; ?></td> -->
                            <td><?php echo $row["duration_days"]; ?> DAYS</td>
                            <!-- <td><?php //echo $row["activation_date"]==0?"N/A":gmdate("Y-m-d H:i:s", $row["activation_date"]); ?></td> -->
                            <!-- <td><?php //echo $row["expiration_date"]==0?"N/A":gmdate("Y-m-d H:i:s", $row["expiration_date"]); //echo $row["expiration_date"]==0?"N/A":$row["expiration_date"]; ?></td> -->
                            
                            <?php 

                              if($row["expiration_date"] == 0){
                                $days_left = "N/A";
                              }else{

                                $now = new DateTime(date("Y-m-d H:i:s"));
                                $expire_date = new DateTime(gmdate("Y-m-d H:i:s", $row["expiration_date"]));
                                $interval = $now->diff($expire_date);
                                if ($now > $expire_date){
                                  $days_left = "N/A";
                                }else{
                                  
                                  // days
                                  $ttl = $interval->format("%a");
                                  // if days = 0
                                  if($ttl == 0){
                                    // hours
                                    $ttl = $interval->format("%h");
                                    // if hours = 0
                                    if($ttl == 0){
                                      $days_left = $interval->format("%i minutes");
                                    }else{
                                      $days_left = $interval->format("%h hours");                                     
                                    }
                                  }else{
                                    $days_left = $interval->format("%a days");
                                  }
                                  //$days_left = $interval->format("%a days, %h hours, %i minutes");
                                }
                              }
                            ?>

                            <td><?php echo $days_left; ?></td>

                            <?php
                              $_status = "";
                              if ($row["status"] == "NOT USED"){
                                $_status = "badge-green";
                              }elseif($row["status"] == "USED"){
                                $_status = "badge-blue";
                              }elseif($row["status"] == "EXPIRED"){
                                $_status = "badge-red";
                              }
                            ?>

                            <td><label class="badge <?php echo $_status; ?>"><?php echo $row["status"]; ?></label></td>
                            <td><?php echo $row["description"]==""?"N/A":$row["description"]; ?>  </td>
                            <td CLASS="TODO">
                              <a class="dialogAnchor" onclick="openConfirmDelete(<?php echo $row["id"]; ?>, '<?php echo $product; ?>')">DELETE</a> |
                              <a class="dialogAnchor" href="keysaction.php?a=resethwid&id=<?php echo $row["id"]; ?>&p=<?php echo $product; ?>">RESET HWID</a></td>
                          </tr>

                        <?php } //end while fetch assoc ?>

                        </tbody>
                      </table>
                    </div>


            </div>

<script type="text/javascript">
  
  var default_settings = {
    "hide_expired_keys" : false
  }

  if (localStorage.hasOwnProperty("settings")){
    var settings = localStorage.getItem("settings");
    settings = JSON.parse(settings, true);
  }else{
    localStorage.setItem("settings", JSON.stringify(default_settings));
    settings = default_settings;
  }
  
  setHiddenExpired(settings["hide_expired_keys"]);

  let hide_expired = document.getElementById("hide_expired");
  hide_expired.addEventListener("click", toggleHiddenExpired);

  function toggleHiddenExpired() {
    settings["hide_expired_keys"] = !settings["hide_expired_keys"];
    setHiddenExpired(settings["hide_expired_keys"]);
    localStorage.setItem("settings", JSON.stringify(settings));
  }

  function setHiddenExpired(setDisplay){
    let expired = document.getElementsByClassName("expired");
    for(let i = 0; i < expired.length; i++){
        expired[i].style.display = setDisplay ? "none" : "table-row";
    }
    document.getElementById("hide_expired_txt").innerHTML = setDisplay ? "Unhide" : "Hide";
  }

let confirm_delete = document.querySelector('#confirmDelete');
confirm_delete.addEventListener('click', confirmDelete);
  
function confirmDelete(){
  event.preventDefault(); // We don't want to submit this fake form
  let dialog = document.querySelector("#deleteDialog");
  let idInput = dialog.querySelector("#idInput");
  let idProd = dialog.querySelector("#idProd");
  let id = idInput.value;
  let prod = idProd.value;
  window.location.href = './keysaction.php?a=delete&id='+id+'&p='+prod;
  // deleteDialog.close(selectEl.value);
}

function openConfirmDelete(id, prod){
  let dialog = document.querySelector("#deleteDialog");
  let idInput = dialog.querySelector("#idInput");
  let idProd = dialog.querySelector("#idProd");
  idInput.value = id;
  idProd.value = prod;
  dialog.showModal();
}

</script>
</body>
</html>