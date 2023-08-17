<?php

require("includes/config.php");
session_start();
if(isset($_SESSION["username"])){
    header("Location: keys.php");
    die();
}

require("includes/functions.php");

// Check connection
if ($mysqli->connect_error) {
  die("Connection failed: " . $mysqli->connect_error);
}

if (!empty($_SERVER['HTTP_CLIENT_IP'])){
      $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
}elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
      $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
}else{
      $ipaddress = $_SERVER['REMOTE_ADDR'];
}

$error_message = "PLEASE LOGIN TO CONTINUE";

if (isset($_POST['username']) AND isset($_POST['password'])) {

    $username = $_POST['username'];
    $password = hash('sha256',$_POST['password']);

    //prepare bind
    $stmt = $mysqli->prepare("SELECT username, password, failed_logins FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    //check valid login
    if($result->num_rows > 0){
        $data = mysqli_fetch_assoc($result);

        if($data["failed_logins"] >= 5){
            header("Location: login.php?failed");
            die();
        }

        if($data["password"] === $password){
            //session_destroy();
            //session_unset();
            session_regenerate_id();
            $_SESSION['username'] = $data["username"];
            event_log($mysqli, "SYSTEM", $username." logged in (".$ipaddress.")");
            $fail = $mysqli->query("UPDATE admins SET failed_logins = 0 WHERE username = '".$username."'");
            header("Location: keys.php");
        
        }else{
            $failed_plus_one = $data["failed_logins"] + 1;
            $fail = $mysqli->query("UPDATE admins SET failed_logins = ".$failed_plus_one." WHERE username = '".$username."'");
            event_log($mysqli, "SYSTEM", "FAILED LOGIN ATTEMPT(".$failed_plus_one.") FROM: ".$username." (".$ipaddress.")");
            header("Location: login.php?error");
            die();
        }

    }else{
        event_log($mysqli, "SYSTEM", "FAILED LOGIN ATTEMPT FROM UNKNOWN USER: ".$username." (".$ipaddress.")");
        header("Location: login.php?error");
        die();
    }

}

if(isset($_GET["error"])){
    $error_message = "ACCESS DENIED - INVALID USERNAME OR PASSWORD";
}

if(isset($_GET["failed"])){
    $error_message = "ACCESS DENIED - TOO MANY FAILED ATTEMPTS";
}


?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>nimrodcore panel</title>

    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link href="assets/css/fontawesome.css" rel="stylesheet">
    <link href="assets/css/all.css" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/icon.png">

    <style>

        .font-big{
            font-size: 14px !important;
        }

        .input-group-text{
            font-size: 12px !important;
            padding: 10px;
        }

        .error-msg{
            color: red;
            font-size: 18px;
            text-align: center;
            font-style: italic;
        }

        .btn-purple{
            background-color: rgb(53, 39, 145);
            border-color: rgb(53, 39, 145);
            color: white !important;
        }

        .btn-purple:hover{
            background-color: rgb(43 31 122);
            border-color: rgb(43 31 122);
            color: white !important;
        }

        .btn-primary:hover {
          color: #fff;
          background-color: #ac1010;
          border-color: #ac1010;
          border-width: 2px;
        }
        .btn-primary:hover {
          color: #fff;
          background-color: #000;
          border-color: #ac1010;
          color: #ac1010 !important;
        }

        body{
            background-color: #000000;
            background-image: url("/images/dragon.jpg");
        }
        
        .form-image{
            max-width: 100%;
        }
        .main-card{
            /*border-style: solid;
            border-color: white;
            border-width: 2px;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px*/

        }
        .form-black{
            background-color: black;
            color: white;
        }
        .form-control:focus{
            background-color: black;
            color: white;
        }
        .input-group-text{
            background-color: black;
            color: white;
        }
    </style>

</head>

<body>

    <div class="d-flex justify-content-md-center align-items-center vh-100">
 

            <div class="container-flex col-md-3">

                <img src="/images/ncore9.png" class="form-image"/>

                <p class="text-center text-white"></p>
                
                <div class="container-flex main-card pt-2 pr-3 pl-3 mt-2 pb-2">


                    <div class="container-flex">
                        <form role="form" method="POST">

                            <div class="row mb-3 align-items-center">
                                <div class="col-12">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fa fa-user"></i></span>
                                        </div>
                                        <input class="form-control form-black font-big" name="username" type="text" placeholder="USERNAME" autofocus>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3 align-items-center">
                                <div class="col-12">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fa fa-lock"></i></span>
                                        </div>
                                        <input class="form-control form-black font-big" name="password" type="password" placeholder="PASSWORD">
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3 align-items-center">
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="submit" class="form-control font-big btn btn-purple" value="LOGIN">
                                    </div>
                                </div>
                            </div>

                            

                        </form>
                    </div>    

                </div> 

                <div class="mt-2">
                    <p class="error-msg"><?php echo $error_message; ?></p>                      
                </div>        
                        
            </div>
       
    </div>

    <!-- jQuery -->
    <script src="bower_components/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap Core JavaScript -->
    <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- Metis Menu Plugin JavaScript -->
    <script src="bower_components/metisMenu/dist/metisMenu.min.js"></script>
    <!-- Custom Theme JavaScript -->
    <script src="dist/js/sb-admin-2.js"></script>

</body>

</html>