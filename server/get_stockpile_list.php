<?php
if (session_id() == "") {
	session_start();
}
include_once('connect.php');
include_once("users.php");

$userid = $_SESSION['userid'];

$sql = "SELECT * FROM system_vars WHERE id = 1";

$war_number = $pdo->prepare($sql);
$war_number->execute();
$war_number->setFetchMode(PDO::FETCH_ASSOC);
$war_number_Data = $war_number->fetch();
$war_number = $war_number_Data["int"];

$output = array('Error'=>'', 'ErrorCode'=>'', 'Stockpiles'=>array());
$all = false;
$code = '';
if(!isset($_GET['code'])){
	$all = True;
} else {
	$code = $_GET['code'];
}
if($all){
	$sql = "SELECT * FROM stock_users WHERE user_id = :userid";
	$check_for_user = $pdo->prepare($sql);
	$check_for_user->bindParam(':userid', $userid);
} else {
	$sql = "SELECT * FROM stock_users WHERE user_id = :userid and stockpile_id = :stockid";
	$check_for_user = $pdo->prepare($sql);
	$check_for_user->bindParam(':userid', $userid);
	$check_for_user->bindParam(':stockid', $code);
}


$check_for_user->execute();
$check_for_user->setFetchMode(PDO::FETCH_ASSOC);
$check_for_user_rows = $check_for_user->rowCount();

if($check_for_user_rows){
	$check_for_user = $check_for_user->fetchall();
	if(count($check_for_user) > 1){
		foreach($check_for_user as $key => $data){
			$temp = array();
			$authorized = $data['authorized'];
			if($authorized == 1){
				$stockpile_id = $data['stockpile_id'];
				$sql = "SELECT * FROM stockpiles_list WHERE idhash = :idcode";
				$get_stockpile_name_prep = $pdo->prepare($sql);
				$get_stockpile_name_prep->bindParam(':idcode', $stockpile_id);
				$get_stockpile_name_prep->execute();
				$get_stockpile_name_prep->setFetchMode(PDO::FETCH_ASSOC);
				$get_stockpile_name_rows = $get_stockpile_name_prep->rowCount();
				if($get_stockpile_name_rows > 0){
					$get_stockpile_name = $get_stockpile_name_prep->fetch();
					$descriptor = "";
					$id = $get_stockpile_name['discordid'];
					$sql = "SELECT * FROM discords_list WHERE id = :idcode";
					$get_stockpile_dec = $pdo->prepare($sql);
					$get_stockpile_dec->bindParam(':idcode', $id);
					$get_stockpile_dec->execute();
					$get_stockpile_dec->setFetchMode(PDO::FETCH_ASSOC);
					$get_stockpile_dec_rows = $get_stockpile_dec->rowCount();
					if($get_stockpile_dec_rows > 0){
						$get_stockpile_d = $get_stockpile_dec->fetch();
						$descriptor = $get_stockpile_d['descriptor'];
					}
					$name = $get_stockpile_name['name'];
					$temp = array("ID"=>$stockpile_id, "NAME"=>$name, "DESC"=>$descriptor);
					array_push($output['Stockpiles'], $temp);
				}
				
			}
			if($authorized == 66){
				$temp['banned'] = True;
			} else {
				$temp['banned'] = False;
			}
		}
	} else{
		$temp = array();
		$authorized = $check_for_user['authorized'];
		if($authorized == 1){
			$stockpile_id = $check_for_user['stockpile_id'];
			$sql = "SELECT * FROM stockpiles_list WHERE idhash = :idcode";
			$get_stockpile_name_prep = $pdo->prepare($sql);
			$get_stockpile_name_prep->bindParam(':idcode', $stockpile_id);
			$get_stockpile_name_prep->execute();
			$get_stockpile_name_prep->setFetchMode(PDO::FETCH_ASSOC);
			$get_stockpile_name = $get_stockpile_name_prep->fetch();
			$descriptor = "";
			$id = $get_stockpile_name['discordid'];
			$sql = "SELECT * FROM discords_list WHERE id = :idcode";
			$get_stockpile_dec = $pdo->prepare($sql);
			$get_stockpile_dec->bindParam(':idcode', $id);
			$get_stockpile_dec->execute();
			$get_stockpile_dec->setFetchMode(PDO::FETCH_ASSOC);
			$get_stockpile_dec_rows = $get_stockpile_dec->rowCount();
			if($get_stockpile_dec_rows > 0){
				$get_stockpile_d = $get_stockpile_dec->fetch();
				$descriptor = $get_stockpile_d['descriptor'];
			}
			$name = $get_stockpile_name['name'];
			$temp = array("ID"=>$stockpile_id, "NAME"=>$name, "DESC"=>$descriptor);
			array_push($output['Stockpiles'], $temp);
		}
		if($authorized == 66){
			$temp['banned'] = True;
		} else {
			$temp['banned'] = False;
		}
	}
	
}

echo json_encode($output);



?>