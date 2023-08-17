<?php

require("includes/config.php");

session_start();

if(!isset($_SESSION["username"])){
    header("Location: login.php");
    die();
}

require("includes/functions.php");

$mass_export = "";

$key_pattern = "/\w{6}-\w{6}-\w{6}-\w{6}-\w{6}-\w{6}/";

function generate_key($mask){
	$chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$key = $mask;
	for ($i=0; $i < strlen($key); $i++) { 
		if($key[$i] == "X"){
			$key[$i] = $chars[rand(0, strlen($chars) - 1)];
		}
	}
	return $key;
}

if(isset($_POST['key_time'], $_POST['key_amount'], $_POST['key_mask'], $_POST['key_description'], $_POST['key_product'])){

	$key_time = $_POST['key_time'];
	if($key_time == -1){
		$key_time = 9999999999;
	}
	$key_amount = $_POST['key_amount'];
	$key_mask = $_POST['key_mask'];
	$key_description = $_POST['key_description'];
	$key_product = $_POST['key_product'];

	if (!in_array($key_product, $allowed_products)){
		echo "error: unknown product: (".$key_product.")";
		die();
	}

	if (!preg_match($key_pattern, $key_mask)){	
		echo "error: invalid mask (".$key_mask.")";
		die();
	}

	$multi_sql = "";
	for ($i=0; $i < $key_amount; $i++) { 
		$key = generate_key($key_mask);
		$multi_sql .= "('".$key."', '".$key_product."' ,'N/A', NOW(), ".$key_time.", 0, 0, 'NOT USED', '".$key_description."'),";
		$mass_export .= $key."<br>";
	}

	//trim last comma
	$multi_sql = substr($multi_sql, 0, -1);

	$sql = "INSERT INTO `licenses` (`pkey`, `product`, `hwid`, `creation_date`, `duration_days`, `activation_date`, `expiration_date`, `status`, `description`) VALUES ".$multi_sql."";
	$result = $mysqli->query($sql);
	
	if($result){
		event_log($mysqli, $_SESSION["username"], "CREATED (".$key_amount.") KEY/s FOR (".$key_product.")");
	
		if(isset($_POST["export"]) && $_POST["export"] == "true"){
			echo $mass_export;
		}else{
			header("Location: keys.php?p=".$key_product);
			die();
		}
	
	}else{
		echo "error: something went wrong generating the key";
		die();
	}

}

?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>nimrodcore.net</title>
	<link rel="icon" type="image/png" sizes="16x16" href="images/icon.png">

	<style type="text/css">
		body{
			background-color: #000000;
    		color: white;
		}
	</style>

</head>
<body>

	<br><br>
	<a href="keys.php?p=<?php echo $key_product; ?>">< RETURN</a>

</body>
</html>