<?php
if (session_id() == "") {
	session_start();
}
$steam = $_SESSION['steamdata'];
$steamid = $steam['steamid'];

$output = array('Error'=>'', 'ErrorCode'=>'', 'StockpileStatus'=>0, 'Stockpile_Log'=>array());

include_once('connect.php');

// TODO this is still has not 5 minute time checks in case of hacking.

if(isset($_GET['log'])){
	$logum = $_GET['log'];
} else{
	$output["Error"] = "Error undoing, please refresh the page and try again.";
	$output['ErrorCode'] = 'bRry26ow';  // logid not included, or hacking attempt.
	echo json_encode($output);
	die;
}

if(isset($_GET['stockpile'])){
	$stockpile = $_GET['stockpile'];
} else{
	$output["Error"] = "Error undoing, please refresh the page and try again.";
	$output['ErrorCode'] = 'syKbAC3F'; // Stockpile not included, stockpile ID must be messed up or hacking attempt.
}

if(stripos($stockpile, 'select ') !== False && stripos($logum, 'select ') !== False){ // A simple check for SQL injection. stockpile ID and log ID should never have the word SELECT in them.
	$output["Error"] = "Error undoing.";
	$output['ErrorCode'] = 'ujArcvAp'; // 'select ' detect, possible SQL injection, check stockpile name.
}


$sql = "SELECT * FROM stock_log WHERE id = :id and stockpile_id = :stockid";
$check_for_stockpile = $pdo->prepare($sql);
$check_for_stockpile->execute(['id' => $logum, 'stockid' => $stockpile]);
$check_for_stockpile->setFetchMode(PDO::FETCH_ASSOC);
$check_for_stockpile_rows = $check_for_stockpile->rowCount();


if($check_for_stockpile_rows > 0){
	$data = $check_for_stockpile->fetch();
	if($data['steamid'] != $steam['steamid']){
		$output["Error"] = "Error undoing. Please refresh the page and try again.";
		$output['ErrorCode'] = 'fuhscJOv';  // Steam id for this log does not match the ID from $steam
	} else {
		// Get a list of the current stockpile
		$sql = "SELECT * FROM stock_stock WHERE stockpile_id = :stockid";
		$get_stock = $pdo->prepare($sql);
		$get_stock->execute(['stockid' => $stockpile]);
		$get_stock->setFetchMode(PDO::FETCH_ASSOC);
		$get_stock_rows = $get_stock->rowCount();
		if($get_stock_rows > 0){
			$data_stock = $get_stock->fetchall();
			$change = "";
			$items = explode("|", $data['item']);
			foreach($items as $key => $value){
				$temp = explode('=', $value);
				$item = $temp[0];
				$number = $temp[1];
				$db_item = array();
				foreach($data_stock as $key2 => $value2){
					if($value2['item'] == $item){
						$db_item = $value2;
					}
				}
				if(count($db_item)<=0){
					$output["Error"] = "Error undoing. Unable to undo this item, please refresh the page.";
					$output['ErrorCode'] = 'dYLVWu5K'; // Cound not match the item in the logs to an item in the stockpile.
				} else {
					if($number > 0){
						$countchange = $db_item['count'] - $number;
					} elseif($number < 0){
						$countchange = $db_item['count'] + abs($number);
					} else {
						$countchange = $db_item['count'];
					}
					$sql = "UPDATE stock_stock SET count=:count WHERE id=:id";
					
					$get_stock = $pdo->prepare($sql);
					$get_stock->execute(['count' => $countchange, 'id' => $db_item['id']]);
					
					// Mark any discord messages associated with this log for deletion.
					
					$sql = "SELECT * FROM recent_discord_messages WHERE stockpile=:stockpile and logid=:logid and user=:user";
					$get_discord_message = $pdo->prepare($sql);
					$get_discord_message->execute(['stockpile' => $stockpile, 'logid' => $logum, 'user' => $steam['steamid']]);
					$get_discord_message->setFetchMode(PDO::FETCH_ASSOC);
					$get_discord_message_rows = $get_discord_message->rowCount();
					if($get_discord_message_rows > 0){
						$data_discord = $get_discord_message->fetchAll();
						$sql = "UPDATE recent_discord_messages SET status=66 WHERE id=:id";
						$get_stock = $pdo->prepare($sql);
						$get_stock->execute(['id' => $data_discord['id']]);
					}
					
					
					// Now delete the log files
					$sql = "DELETE FROM stock_log WHERE id=:id and stockpile_id = :stockid";
					$delete_log = $pdo->prepare($sql);
					$delete_log->execute(['id' => $logum, 'stockid' => $stockpile]);
					
					// Lastly set the update flag in the stockpiles list so the client does a log and stock update.
					$sql = "UPDATE stockpiles_list SET `status` = 35 WHERE internal_name=:name";
					$get_stock = $pdo->prepare($sql);
					$get_stock->execute(['name' => $stockpile]);
					}
			}
		} else {
			$output["Error"] = "Error undoing. Unable to undo.";
			$output['ErrorCode'] = 'zoEuu0Nx';  // There is no stock in the stockpile, how did this happen?
		}
		
		
		
	}
} else {
	$output["Error"] = "Error undoing. Please refresh the page and try again.";
	$output['ErrorCode'] = 'GliBPX6D';  // ID not found in logs
}





echo json_encode($output);

?>