<?php

$pdo = new PDO('mysql:host=www.stockpiler.net;port=3306;dbname=db_name', 'user', 'pass');
$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );



?>