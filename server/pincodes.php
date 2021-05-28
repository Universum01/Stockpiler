<?php
if (session_id() == "") {
	session_start();
}
include_once("users.php");

$steam = $_SESSION['steamdata'];
$steamid = $steam['steamid'];

$sql = "SELECT * FROM system_vars WHERE id = 1";

$war_number = $pdo->prepare($sql);
$war_number->execute();
$war_number->setFetchMode(PDO::FETCH_ASSOC);
$war_number_Data = $war_number->fetch();
$war_number = $war_number_Data["int"];

$output = array('Error'=>'', 'ErrorCode'=>'', 'StockpileStatus'=>0);
if(isset($_GET['pincode'])){
	$pin_code = $_GET['pincode'];
} else{
	$output["Error"] = "No pin code was provided.";
	echo json_encode($output);
	die;
}

include_once('connect.php');

$sql = "SELECT * FROM stockpiles_list WHERE idhash = :pincode and for_war_number = :warnumber and active != 0";
$check_for_stockpile = $pdo->prepare($sql);
$check_for_stockpile->bindParam(':pincode', $pin_code);
$check_for_stockpile->bindParam(':warnumber', $war_number);
$check_for_stockpile->execute();
$check_for_stockpile->setFetchMode(PDO::FETCH_ASSOC);
$check_for_stockpile_rows = $check_for_stockpile->rowCount();

if($check_for_stockpile_rows == 1){
	$output["StockpileStatus"] = 1;
	$stockpile_list_data = $check_for_stockpile->fetch();
	$stockpile_name = $stockpile_list_data['internal_name'];
	//try{
		$sql = "SELECT * FROM stock_users WHERE `steamid` = :steamid and `stockpile_id` = :stockpilename";
		$query = $pdo->prepare($sql);
		$query->bindParam(':steamid', $steamid);
		$query->bindParam(':stockpilename', $stockpile_name);
		$query->execute();
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$rows = $query->rowCount();

		if($rows == 0){ // If they are new to the stockpile
			try {
				// Add them to the stockpiles user list.
				$accesslevel = '2';
				$system_id = '0';
				$current_datetime = date('Y-m-d H:i:s');
				$insert = $pdo->prepare("INSERT INTO stock_users (accesslevel, steamid, lastaccessed, stockpile_id) VALUES (?, ?, ?, ?)");
				$insert->bindParam(1, $accesslevel);
				$insert->bindParam(2, $steamid);
				$insert->bindParam(3, $current_datetime);
				$insert->bindParam(4, $stockpile_name);
				
				$insert->execute();
			} catch(PDOException $e) {
				$output["Error"] = "There was an unknown error with the server when trying to access this stockpile. Please try again. <br><br>Error Code: KxqZZ9kA";
				$output['ErrorCode'] = 'KxqZZ9kA';
				echo json_encode($output);
				die;
			}
			
			try {
				$notes = '<font color="red"><b>'.$steam['personaname'].'</b></font> has joined this stockpile.';
				$system_id = '0';
				$type = 'notice';
				$current_datetime = date('Y-m-d H:i:s');
				$insert = $pdo->prepare("INSERT INTO stock_log (notes, steamid, log_type, stockpile_id) VALUES (?, ?, ?, ?)");
				$insert->bindParam(1, $notes);
				$insert->bindParam(2, $steamid);
				$insert->bindParam(3, $type);
				$insert->bindParam(4, $stockpile_name);
				
				$insert->execute();
			} catch(PDOException $e) {
				$output["Error"] = "There was an unknown error with the server when trying to access this stockpile. Please try again. <br><br>Error Code: d15UwMBS";
				$output['ErrorCode'] = 'KxqZZ9kA';
				echo json_encode($output);
				die;
			}
			
			
		} else { // If they are already added to the stockpile
			$user_data = $query->fetch();
			// check if banned
			if($user_data['accesslevel'] == 0){
				$output["Error"] = "Im sorry, you have been BANNED from this stockpile and are unable to access it.";
				echo json_encode($output);
				die;
			}
			
			// It exists and they are not banned so nothing else is needed, the client will get a 1 for stockpile status so it will request the stock from stockpile.php
			
			
		}
	/*
	} catch(PDOException $e) {
		if(isset($_GET['name'])){
			$name = $_GET['name'];
		} else {
			$name = "";
		}
		$one = 0;
		$id = $stockpile_list_data["id"];
		$set_inactive = $pdo->prepare("UPDATE `stockpiles_list` SET `active` = :active WHERE id = :id");
		$set_inactive->bindParam(':id', $id);
		$set_inactive->bindParam(':active', $one);
		
		$update = $set_inactive->execute();
		
		if(strlen($name)>0){
			$output["Error"] = "Stockpile ".$name." no longer exists!<br><br>Error Code: 1QaIVgui";
			$output['ErrorCode'] = '1QaIVgui';
		} else {
			$output["Error"] = "That stockpile has been deleted from Stockpiler and no longer exists!<br><br>Error Code: Lsa5GAeh";
			$output['ErrorCode'] = 'Lsa5GAeh';
		}
	}
	*/
	
	$output["StockpileName"] = array("Internal"=>$stockpile_name, "External"=>$stockpile_list_data['name']);
} elseif($check_for_stockpile_rows > 1){
	// TODO duplicate stockpiles??
	
} else{
	if(isset($_GET['name'])){
		$name = $_GET['name'];
		if(strlen($name) > 20){
			$output["Error"] = "Max stockpile name is 20 Characters, please try a shorter name.";
			$output['ErrorCode'] = 'Ew0Ujrcz';
			echo json_encode($output);
			die;
		}
		$random_number = rand(1111,9999);
		$nameshort = preg_replace('/[^a-z1-9]/i', '', $name);
		$nameshort = substr($nameshort, -8);
		$stockpile_name = $random_number."_".$nameshort;
		
		
		try {
			$discord_id = dechex(mt_rand(1111111111, 9999999999));
			$add_stock = $pdo->prepare("INSERT INTO stockpiles_list (name, idhash, owner_steam_id, internal_name, for_war_number, discord_add_id) VALUES (?, ?, ?, ?, ?, ?)");
			$add_stock->bindParam(1, $name);
			$add_stock->bindParam(2, $pin_code);
			$add_stock->bindParam(3, $steamid);
			$add_stock->bindParam(4, $stockpile_name);
			$add_stock->bindParam(5, $war_number);
			$add_stock->bindParam(6, $discord_id);
			
			
			$add_stock->execute();
		} catch(PDOException $e) {
			$output["Error"] = "There was an unknown error with the server when trying to create your stockpile. Please try again / try another name. <br><br>Error Code: R1AyRQ4g";
			$output['ErrorCode'] = 'R1AyRQ4g';
			echo json_encode($output);
			die;
		}
		
		try {
			$notes = 'Stockpile '.$name.' created!';
			$system_id = '0';
			$type = 'notice';
			
			$add_stock = $pdo->prepare("INSERT INTO stock_log (notes, steamid, log_type, stockpile_id) VALUES (?, ?, ?, ?)");
			$add_stock->bindParam(1, $notes);
			$add_stock->bindParam(2, $system_id);
			$add_stock->bindParam(3, $type);
			$add_stock->bindParam(4, $stockpile_name);
			$add_stock->execute();
			
			$notes = '<font color="red"><b>'.$steam['personaname'].'</b></font> has joined this stockpile.';
			$system_id = '0';
			$type = 'notice';
			$add_stock->execute();
			
			$notes = 'Add Stockpiler to your discord, then use the command <b>!stock add '.$discord_id.'</b> You can also enable low timer alerts with <b>!stock setup alert</b> <a href="https://discord.com/api/oauth2/authorize?client_id=776281678494040085&permissions=403520&scope=bot" target="blank">Stockpiler Discord Bot</a>';
			$system_id = '0';
			$type = 'DiscordCode';
			$add_stock->execute();
		} catch(PDOException $e) {
			$output["Error"] = "There was an unknown error with the server when trying to create your stockpile. Please try again / try another name. <br><br>Error Code: 0BI72ial";
			$output['ErrorCode'] = '0BI72ial';
			echo json_encode($output);
			die;
		}
		
		try {
			$accesslevel = '8';
			$notes = 'Stockpile <b>'.$name.'</b> created created by <font color="blue"><b>'.$steam['personaname'].'</b></font>';
			$system_id = '0';
			$type = 'notice';
			$add_stock = $pdo->prepare("INSERT INTO stock_users (accesslevel, steamid, stockpile_id) VALUES (?, ?, ?)");
			$add_stock->bindParam(1, $accesslevel);
			$add_stock->bindParam(2, $steamid);
			$add_stock->bindParam(3, $stockpile_name);
			
			
			$add_stock->execute();
		} catch(PDOException $e) {
			$output["Error"] = "There was an unknown error with the server when trying to create your stockpile. Please try again / try another name. <br><br>Error Code: H90LwU23";
			$output['ErrorCode'] = 'H90LwU23';
			echo json_encode($output);
			die;
		}

		$output["StockpileStatus"] = 2;
		$output["StockpileName"] = array("Internal"=>$stockpile_name, "External"=>$name);
		include_once('users.php');
	} else{
		$output["StockpileStatus"] = 0;
	}
}

echo json_encode($output);



?>