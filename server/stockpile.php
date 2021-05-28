<?php
if (session_id() == "") {
	session_start();
}

include_once('connect.php');
include_once("users.php");

$userid = $_SESSION['userid'];

//echo "<pre>" . print_r($_SESSION["steamdata"], true) . "</pre>";
$points = 0;
$extra_points = 0;

$output = array('Error'=>'', 'ErrorCode'=>'', 'StockpileStatus'=>0, 'Stockpile_Log'=>array(), 'Stockpile_data'=>array(), 'Access'=>0, 'Status'=>0);

if(isset($_GET['stockpile'])){
	$stockpile = $_GET['stockpile'];
} else{
	$output["Error"] = "No stockpile was provided.";
	echo json_encode($output);
	die;
}
$select = false;

if(isset($_POST['data']) && count($_POST['data']) > 0){
	$data = $_POST['data'];
	if(count($data) == 0){
		$select = true;
	}
} else{
	$data = '';
	$select = true;
}

if(isset($_POST['changetype']) && $_POST['changetype'] > 0){
	$changetype = $_POST['changetype'];
} else{
	$changetype = 0;
}

if(isset($_POST['notes'])){
	if(strlen($_POST['notes']) <= 0){
		$notes = "";
	} else {
		$notes = " <br><b>NOTES:</b> ".$_POST['notes'];
	}
} else{
	$notes = "";
}


$logtype = "";
if(isset($_POST['obtained']) and count($_POST['obtained']) == 5){
	$obtained = $_POST['obtained'];
	$aquired_text = "";
	if($obtained[0] == 1){ // Mining
		$aquired_text = " aquired from Mining: ";
	} elseif($obtained[1] == 1){ // Manufactured
		$aquired_text = " that they manufactured: ";
	} elseif($obtained[2] == 1){ // found
		$aquired_text = " that was found: ";
	} elseif($obtained[3] == 1){ // transfered
		$aquired_text = " that was moved from another stockpile: ";
	} elseif($obtained[4] == 1){ // transfered
		$aquired_text = ", to keep the stock numbers correct.";
	}
	
} else{
	$aquired_text = '';
}

$update_last_addition = 0; // In stockpiles_list specifies when an addition/correction is made.

try{
	
	$sql = "SELECT * FROM stock_users WHERE user_id = ? and stockpile_id = ?";
	$get_info = $pdo->prepare($sql);
	$get_info->execute([$userid, $stockpile]);
	$get_info->setFetchMode(PDO::FETCH_ASSOC);
	$get_info_rows = $get_info->rowCount();

	if($get_info_rows > 0){
		$get_info_data = $get_info->fetch();
	}
} catch(PDOException $e) {
 echo $e;
}

$log = '<b>'.$get_info_data["name"].'</b> ';
if($obtained[4] == 1){
	$update_last_addition = 2;
	$log .= ' made a <b><font color="Purple">CORRECTION</font></b> to the following crates';
	$logtype = "correction";
} else {
	if($changetype == 1){
		$update_last_addition = 1;
		$log .= '<b><font color="green">ADDED</font></b> the following crates to the stockpile';
	} elseif($changetype == 2){
		$log .= '<b><font color="red">REMOVED</font></b> the following crates from the stockpile';
	} elseif($changetype == 3){
		$update_last_addition = 1;
		$log .= '<b><font color="green">ADDED</font></b>/<b><font color="red">REMOVED</font></b> the following crates from the stockpile';
	} else {
		$log .= 'make changes to the stockpile';
	}
}


$log = $log.$aquired_text;


try{

	if($get_info_rows > 0){
		
		$output['Access'] = $get_info_data["accesslevel"];
		
		if($select){
			selectstock($stockpile);
			
		} elseif(count($data) > 0) {
			$itemnames = "";
			$item_data = array();
			$item_list_string = "";
			//Discord message formatting
			
			$discord_string = $log." ";
			$discord_string_added = "```CSS\n+ ADDED \n```";
			
			$discord_string_removed = "```CSS\n [Removed] \n```";
			
			$discord_string_correction = "```CSS\n .CORRECTION \n```";
			foreach($data as $key => $value){
				$string = file_get_contents("item_list.json");
				$item_data_array = json_decode($string, true);
				foreach($item_data_array as $key2 => $items){
					if($items['pcname'] == $value[0]){
						$item_data = $items;
					}
					
				}
				$firstone = "";
				if($key != 0){
					$firstone = ', ';
				}

				
				$change_string = "";
				if($value[1] > 0){
					$change_string = ' <b><font color="green">+'.$value[1]."</font></b>";
					$item_list_string .= $item_data["pcname"]."=".$value[1]."|";
					$discord_string_added .= "+".$value[1]." ".$item_data["name"];
				} else {
					$change_string = ' <b><font color="red">'.$value[1]."</font></b>";
					$item_list_string .= $item_data["pcname"]."=".$value[1]."|";
					$discord_string_removed .= $value[1]." ".$item_data["name"];
				}
				$discord_string_correction .= $value[1]." ".$item_data["name"];
				$crate = "";
				if(stripos($item_data["pcname"], "crate") !== False){
					$crate = ' <font color="orange">Crates</font>';
				}
				$itemnames .= $firstone.$change_string." <b>".$item_data["name"].$crate."</b>";
				$sql = "SELECT * FROM stock_stock WHERE item = :item AND stockpile_id = :stockpile";
				$get_item_stock = $pdo->prepare($sql);
				$get_item_stock->execute(['item' => $value[0], 
				'stockpile' => $stockpile]);
				$get_item_stock->setFetchMode(PDO::FETCH_ASSOC);
				$get_item_stock_rows = $get_item_stock->rowCount();
				
				if($get_item_stock_rows > 0){ // greater then 0 check?
					$get_item_stock_data = $get_item_stock->fetch();
					$count = $get_item_stock_data["count"] + $value[1];
					
					$insert = $pdo->prepare("UPDATE stock_stock SET item=?, count=?, user_id=? WHERE id=?");
					
					$insert->execute([$value[0], $count, $user_id, $get_item_stock_data["id"]]);
					$output['StockpileStatus'] = 1;
					
					if($value[1] > 0){
						$discord_string_added .= "(**".$count."**)\n";
					} else {
						$discord_string_removed .= "(**".$count."**)\n";
					}
					$discord_string_correction .= "(**".$count."**)\n";
					
					selectstock($stockpile);
				} else {
					$count = $value[1];
					$insert = $pdo->prepare("INSERT INTO stock_stock (item, count, user_id, stockpile_id) VALUES (?, ?, ?, ?)");
					$insert->bindParam(1, $value[0]);
					$insert->bindParam(2, $count);
					$insert->bindParam(3, $user_id);
					$insert->bindParam(4, $stockpile);
					
					$insert->execute();
					$output['StockpileStatus'] = 1;
					
					if($value[1] > 0){
						$discord_string_added .= "(**".$count."**)\n";
					} else {
						$discord_string_removed .= "(**".$count."**)\n";
					}
					$discord_string_correction .= "(**".$count."**)\n";
					
					selectstock($stockpile);
				}
			}

			
			try {
				$discord_string = strip_tags($discord_string);
				if($obtained[4] == 1){
					$discord_string = $discord_string.$discord_string_correction;
				} else {
					if($changetype == 1){
						$discord_string = $discord_string.$discord_string_added;
					} elseif($changetype == 2){
						$discord_string = $discord_string.$discord_string_removed;
					} elseif($changetype == 3){
						$discord_string = $discord_string.$discord_string_added.$discord_string_removed;
					} else {
						$log .= ' make changes to the stockpile ';
					}
				}
				if(strlen($notes) > 0){
					$discord_string .= "\n".$notes;
				}

				$discord_string .= "\n ---------------------------------";
				$log .= $itemnames.$notes;
				if($logtype == ""){
					$logtype = 'change';
				}
				
				$item_list_string = substr($item_list_string, 0, -1);
				$sql = "INSERT INTO stock_log (add_or_remove, user_id, log_type, notes, item, discord_text, stockpile_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
				//echo $sql.'<br>';
				$changetype = $changetype;
				//echo $changetype." - ".$user_id." - ".$logtype." - ".$log."  ";
				$add_stock = $pdo->prepare($sql);
				$add_stock->bindParam(1, $changetype);
				$add_stock->bindParam(2, $user_id);
				$add_stock->bindParam(3, $logtype);
				$add_stock->bindParam(4, $log);
				$add_stock->bindParam(5, $item_list_string);
				$add_stock->bindParam(6, $discord_string);
				$add_stock->bindParam(7, $stockpile);
				
				
				$add_stock->execute();
			} catch(PDOException $e) {
				//var_dump($e);
				$output["Error"] = "There was an unknown error with the server when trying to add the items to your stockpile. Please reload the page and try again. <br><br>Error Code: ZtBMmJUa";
				$output['ErrorCode'] = 'ZtBMmJUa';
				echo json_encode($output);
				die;
			}
			
			if($update_last_addition == 1){
				$update = $pdo->prepare("UPDATE stockpiles_list SET last_addition=? WHERE internal_name=?");
				$update->execute([date('Y-m-d H:i:s'), $stockpile]);
			} elseif($update_last_addition == 2){
				$update = $pdo->prepare("UPDATE stockpiles_list SET last_addition=? WHERE internal_name=?");
				$update->execute([date('Y-m-d H:i:s', strtotime('-1 hours')), $stockpile]);
			}
			
		}
		
		
	} 
	// add check for user not added to stockpile
	
} catch(PDOException $e) {
 echo $e;
}

function selectstock($stockpile){
	global $pdo, $output;
	$string = file_get_contents("item_list.json");
	$item_data = json_decode($string, true);
	$send_array = array();
	
	$sql = "SELECT * FROM stock_stock WHERE stockpile_id = :stockid";
	$check_access = $pdo->prepare($sql);
	
	$check_access->execute(['stockid' => $stockpile]);
	
	$check_access->setFetchMode(PDO::FETCH_ASSOC);
	$check_access_rows = $check_access->rowCount(); // row count issue check
	
	if($check_access_rows>0){
		$get_info_data = $check_access->fetchAll();
		
		foreach($get_info_data as $key => $value){
			foreach($item_data as $key2 => $items){
				
				if($items["pcname"] == $value["item"]){
					$items["amount"] = $value["count"];
					array_push($send_array, $items);
				}
			}
		}
	}
	$output["Stockpile_data"] = $send_array;
}
echo json_encode($output);



?>