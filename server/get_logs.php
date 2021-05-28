<?php
if (session_id() == "") {
	session_start();
}
$userid = $_SESSION['userid'];

$output = array('Error'=>'', 'ErrorCode'=>'', 'StockpileStatus'=>0, 'Stockpile_Log'=>array(), 'Access'=>0, 'Status'=>0);
if(isset($_GET['stockpile'])){
	$stockpile = $_GET['stockpile'];
} else{
	$output["Error"] = "No pin code was provided.";
	echo json_encode($output);
	die;
}

if(isset($_GET['name'])){
	$name = $_GET['name'];
} else{
	$name = '';
}

if(isset($_GET['rev'])){
	$rev = $_GET['rev'];
} else{
	$rev = 0;
}

include_once('connect.php');

try{ // Check for status changes numbers that mean diffrent things.
	$sql = "SELECT `status`, `id` FROM stockpiles_list WHERE idhash=:internal_name";
	$check_update = $pdo->prepare($sql);
	$check_update->execute(['internal_name' => $stockpile]);
	$check_update->setFetchMode(PDO::FETCH_ASSOC);
	$check_update_rows = $check_update->rowCount();
	$statustext = "";
	if($check_update_rows > 0){
		$data_check_update = $check_update->fetch();
		
		if($data_check_update['status'] != null){
			$temp = explode("|", $data_check_update['status']);
			foreach($temp as $key => $value){
				if((int)$value == 35 || (int)$value == 8){
					$output['Status'] = $value;
				} else {
					if(strlen($value) > 0){
						$statustext .= $value."|";
					} else {
						$statustext = "0";
					}
					
				}
			}
		}
		
		
		$sql = "UPDATE stockpiles_list SET `status` = :statustext WHERE id=:id";
		$get_stock = $pdo->prepare($sql);
		$get_stock->execute(['statustext' => $statustext,'id' => $data_check_update['id']]);
	} else{
		if(strlen($name)>0){
			no_longer_exists($name, 'hJDE0gYU');
		} else {
			no_longer_exists('', 'vtkP5rPr');
		}
		exit;
	}
	/*
	Status codes:
	35 = Tells the client to reload Logs and stockpile
	8 = Tells the client there was access level change for it and it needs to see what its new level is.
	
	
	*/
} catch(PDOException $e){

}


try{
	$sql = "SELECT * FROM stock_log WHERE id > :idnum AND stockpile_id = :stockid ORDER BY id";
	$check_for_stockpile = $pdo->prepare($sql);
	$check_for_stockpile->execute(['idnum' => $rev, 'stockid' => $stockpile]);
	$check_for_stockpile->setFetchMode(PDO::FETCH_ASSOC);
	$check_for_stockpile_rows = $check_for_stockpile->rowCount();


	if($check_for_stockpile_rows > 0){
		$data = $check_for_stockpile->fetchAll();

		foreach ($data as $row) {
			$row["notes"] = $row["notes"]."<hr>";
			array_push($output["Stockpile_Log"], $row);
		}
	} else {
		$sql = "SELECT * FROM stock_log WHERE stockpile_id = :stockid ORDER BY id";
		$check_for_stockpile = $pdo->prepare($sql);
		$check_for_stockpile->execute(['stockid' => $stockpile]);
		$check_for_stockpile->setFetchMode(PDO::FETCH_ASSOC);
		$check_for_stockpile_rows = $check_for_stockpile->rowCount();
			if(strlen($check_for_stockpile_rows)==0){
				$output["Error"] = "Stockpile <b>".$name."</b> no longer exists! <br><br>Error Code: f2ydlYSy";
				$output['ErrorCode'] = 'f2ydlYSy';
			} else {
				$output['StockpileStatus'] = 3; # to indicate it exists there is just no new logs.
			}
		
	}
	
} catch(PDOException $e) {
	if(strlen($name)>0){
		no_longer_exists($name, "2GURAWzs");
	} else {
		no_longer_exists("", "MMTEtbJ7");
	}
	exit;
}

function no_longer_exists($name="", $error_code=""){
	if(strlen($name)>0){
		$output["Error"] = "Stockpile <b>".$name."</b> no longer exists! <br><br>Error Code: ".$error_code;
		$output['ErrorCode'] = $error_code;
	} else {
		$output["Error"] = "Your active stockpile no longer exists! <br><br>Error Code: ".$error_code;
		$output['ErrorCode'] = $error_code;
	}
	echo json_encode($output);
	return;
}

echo json_encode($output);



?>