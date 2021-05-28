<?php
if (session_id() == "") {
	session_start();
}
$userid = $_SESSION['userid'];


include_once('connect.php');
$sql = "SELECT name FROM users WHERE user_id = ?";
$query = $pdo->prepare($sql);
$query->execute([$userid]);
$query->setFetchMode(PDO::FETCH_ASSOC);
$rows = $query->rowCount();



if($rows == 0){
	$query = $pdo->prepare("INSERT INTO users (name, user_id, avatar) VALUES (?, ?, ?)");
	$query->bindParam(1, $_SESSION['name']);
	$query->bindParam(2, $_SESSION['userid']);
	$query->bindParam(3, $_SESSION['avatar']);
	$query->execute();
	
}


?>