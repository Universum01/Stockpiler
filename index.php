<html>
<head>
<link href='https://fonts.googleapis.com/css?family=Alegreya' rel='stylesheet'>
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script type="text/javascript" src="node_modules/crypto-js/crypto-js.js"></script>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1,minimum-scale=1">
<script>
var active_stockpile = "";
var pincode_entered = "";

</script>
</head>
<body>
<?php


if (session_id() == "") {
	session_start();
}

define('OAUTH2_CLIENT_ID', '123456789');
define('OAUTH2_CLIENT_SECRET', 'abcdefg');
//define('redirect', 'https://beta.stockpiler.net');
define('redirect', 'https://beta.stockpiler.net');

$authorizeURL = 'https://discord.com/api/oauth2/authorize';
$tokenURL = 'https://discord.com/api/oauth2/token';
$apiURLBase = 'https://discord.com/api/users/@me';
$apiURLGuilds = 'https://discordapp.com/api/users/@me/guilds';


function apiRequest($url, $post=FALSE, $headers=array()) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

  $response = curl_exec($ch);


  if($post)
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));

  $headers[] = 'Accept: application/json';

  if(isset($_SESSION["access_token"]))
    $headers[] = 'Authorization: Bearer ' . $_SESSION['access_token'];

  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  $response = curl_exec($ch);
  return json_decode($response);
}

if(isset($_GET['logout'])) {
	unset($_SESSION["access_token"]);
	if (!isset($_SESSION[0])) {
		session_destroy();
	}
}

if(isset($_GET['login'])) {

  $params = array(
    'client_id' => OAUTH2_CLIENT_ID,
    'redirect_uri' => redirect,
    'response_type' => 'code',
    'scope' => 'identify guilds'
  );

  // Redirect the user to Discord's authorization page
  header('Location: https://discordapp.com/api/oauth2/authorize' . '?' . http_build_query($params));
  die();
}


// When Discord redirects the user back here, there will be a "code" and "state" parameter in the query string
if(isset($_GET['code'])) {

  // Exchange the auth code for a token
  $token = apiRequest($tokenURL, array(
    "grant_type" => "authorization_code",
    'client_id' => OAUTH2_CLIENT_ID,
    'client_secret' => OAUTH2_CLIENT_SECRET,
    'redirect_uri' => redirect,
    'code' => $_GET['code']
  ));
  $logout_token = $token->access_token;
  $_SESSION['access_token'] = $token->access_token;
  $user = apiRequest($apiURLBase);
  $guilds = array();
  foreach(apiRequest($apiURLGuilds) as $key => $value){
	  array_push($guilds, $value->id);
  }
  $_SESSION['code'] = $_GET['code'];
  $_SESSION['guilds'] = $guilds;
  $_SESSION['userid'] = $user->id;
  $_SESSION['name'] = $user->username;
  $_SESSION['avatar'] = "https://cdn.discordapp.com/avatars/" . $user->id . "/" . $user->avatar . ".png";
  $_SESSION['username'] = $user->username;


  header('Location: ' . $_SERVER['PHP_SELF']);
}
//echo "<pre>" . print_r($_SESSION['guilds'], true) . "</pre>";

echo '<div class="page">';
if (isset($_SESSION['access_token'])) {
	echo "<center> ";
	echo $_SESSION['username'];
	echo " - <a href='index.php?logout'>Logout</a>";
	echo "<br><img src='".$_SESSION['avatar']."' height='50px' width='50px'></center>";
	//echo "<pre>" . print_r($steam, true) . "</pre>";
} else {
	
	die("<center><br>You must be logged in to view this page: <a href='?login'>Login</a><a href='?login'><img src='imgs/discord.png'></a>");
}

	
include_once('server/users.php');

?>

<script>
var base_url = "https://static.wikia.nocookie.net/foxhole_gamepedia_en/images/";
var all_items = [];
$.getJSON("server/item_list_rev2.json", function(json) {
    all_items = json;
});
var active_stockpile = [];
var list_stockpile = [];
var list_stockpile = [];
var active_stockpile_counts = [];
var changes = [];
var obtained_from = [0, 0, 0, 0, 0];
var change_type = 0;
var log_rev_number = 0;
var current_filter = ["", "", "", ""];
var refreshIntervalId;
var undo = false;
var useraccess = 0;
<?php
if(isset($_GET['multi'])){
	echo 'var usemulti = true;';
} else {
	echo 'var usemulti = false;';
}

?>
</script>
<style>

body {font-family: Arial, Helvetica, sans-serif; background-image: url('imgs/storage_depot.png'); background-repeat: no-repeat; background-size:cover; color: white;}
*::-webkit-scrollbar {
  width: 12px;               /* width of the entire scrollbar */
}
*::-webkit-scrollbar-track {
  background: #666666;        /* color of the tracking area */
}
*::-webkit-scrollbar-thumb {
  background-color: #4d4d4d;    /* color of the scroll thumb */
  border-radius: 20px;       /* roundness of the scroll thumb */
  border: 3px solid #404040;  /* creates padding around scroll thumb */
}

* {
  scrollbar-width: thin;
  scrollbar-color: #8c8c8c #4d4d4d;
}

input {
	color: black;
}

textarea {
	color: black;
}

#container {
	width: 70%;
	height: 80%;
	position: relative;
	margin: 1% auto 0 auto;
}

#topleft {
	background-color: black;
	z-index: 0;
	width: 49%;
	height: 50%;
	overflow: hidden;
	left: 0;
	top: 0;
	position: absolute;
}

.left_container {
	width: 100%;
	height: 100%;
	z-index: 0;
	position: relative;
}

.log_details {
	bottom: 0;
	height: 87%;
	width: 100%;
	z-index: 1;
	position: absolute;
	overflow: auto;
	font-size: medium;
}

@media screen and (max-width:900px) {
	.log_details {
	  font-size: large;
	}
}

@media screen and (max-width:500px) {
	.log_details {
	  font-size: small;
	}
}


.log_title {
	z-index: 1;
	position: absolute;
}




#bottomleft {
	background-color: black;
	z-index: 0;
	width: 49%;
	height: 50%;
	overflow: hidden;
	left: 0;
	bottom: 0;
	position: absolute;
}

.options_details {
	
	bottom: 0;
	height: 87%;
	width: 100%;
	z-index: 1;
	position: absolute;
	overflow: auto;
}

.submit_counts {
	height: 30%;
	//outline: 2px solid green;
}

#right {
	background-color: black;
	z-index: 0;
	width: 49%;
	height: 100%;
	overflow: hidden;
	right: 0;
	position: absolute;
}


.title {
	background-image: url('imgs/title_bar.jpg');
	background-repeat: no-repeat;
	background-size: cover;
	z-index: -2;
	width: 100%;
	height: 12%;
	overflow: hidden;
	top: 0;
	position: absolute;
}

.title_text {
	z-index: 1;
	width: 100%;
	height: 100%;
	margin: 0.1em 0 0 0.3em;
	font-weight: bold;
	letter-spacing: 1px;
	font-family: 'Alegreya';font-size: 22px;	
}

@media screen and (max-height:600px) {
	.title_text {
		margin: 0 0 0 0;
	}
}

@media screen and (max-height:530px) {
	.title_text {
		font-size: 18px;	
	}
}
@media screen and (max-height:420px) {
	.title_text {
		font-size: 12px;	
	}
}

.title_right {
	height: 6%;
	position: relative;
}

.sort_options {
	background-image: url('imgs/top_background_icons.jpg');
	background-repeat: repeat-x;
	background-size: cover;
	z-index: 2;
	width: 100%;
	height: 6%;
	overflow: hidden;
	//position: relative;	
}

.stockpile_dropdown {

	position: absolute;
	float: right;
	margin: 2% 0 0 0;
	right: 0em;
	z-index: 8;
}
/*
@media screen and (max-width:600px) {
	.sort_options {
		height: 12%;
	}
	.sort_icons {
		height: 50%;
	}
	.stockpile_dropdown {
		bottom: 0;
		position: sticky;
		float: left;
		left: inherit;
		margin: 0 0 0 0
		
	}
}
*/
.page {
	position: relative;
}

.icons {
	z-index: 3;
	width: 5%;
	height: 75%;
	overflow: hidden;
	margin: 5px 5px 5px 5px;
	
	position: relative;
	float: left;
}

@media screen and (max-width:950px) {
	.icons {
	  width: 6%;
	  margin: 5px 0 5px 0;
	}
}
@media screen and (max-width:800px) {
	.icons {
	  width: 8%;
	}
}


.icons img:hover {
    opacity: .5;
}

.icons_options {
	width: 15%;
	height: 80%;
	//outline: 2px solid red;
	position: relative;
	z-index: 5;
}

.icons_options_text {
	visibility: hidden;
	position: absolute;
	left: 0;
}

.icons_options :img{
	position: absolute;
}

.icons_options:hover span {
	visibility: visible;
}



@media screen and (max-width:1800px) {
	.stockpile_dropdown {
	  right: 8em;
	}
}
@media screen and (max-width:1630px) {
	.stockpile_dropdown {
	  right: 6em;
	}
}
@media screen and (max-width:1460px) {
	.stockpile_dropdown {
	  right: 4em;
	}
}
@media screen and (max-width:1300px) {
	.stockpile_dropdown {
	  right: 2em;
	}
	#container {
		width: 80%;
	}
}
@media screen and (max-width:1150px) {
	
	
	#container {
		width: 90%;
	}
}

@media screen and (max-width:1000px) {
	#container {
		width: 100%;
	}
	
}

@media screen and (max-width:800px) {
	.stockpile_dropdown {
	  right: 0em;
	  font-size: small;
	  
	  
	}
	#active_stockpile_btn {
		font-weight: normal;
	}
	
}
@media screen and (max-width:700px) {
	.stockpile_dropdown {
	  font-size: x-small;
	}
	#stockpile_dpdown_img {
		width: 0%;
	}
	#active_stockpile_btn {
		font-size: x-small;
	}
}
@media screen and (max-width:600px) {
	.stockpile_dropdown {
	  left: 2em;
	  font-size: small;
	}
}
/*

*/
.sort_icons {
	
}
.stockpile_dropbtn {
	background-color: #444444;
	color: white;
	font-weight: bold;
	cursor: pointer;
	z-index: 11;
	width: 100%;
	height: 35%;
}

.stockpile_dropdown_content {
	visibility: hidden;
	position: absolute;
	min-width: 160px;
	background-color: #444444;
	box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.8);
	z-index: 20;
}

.stockpile_dropdown_content div {
  color: white;
  padding: 12px 16px;
  text-decoration: none;
  display: block;
}

/* Change color of dropdown links on hover */
.stockpile_dropdown_content div:hover {opacity: .5;}


/* Change the background color of the dropdown button when the dropdown content is shown */
.stockpile_dropdown:hover .stockpile_dropbtn {
  font-weight: bold;
}


.right_content_window {
	display: grid;
	z-index: 3;
	width: 100%;
	height: 73%;
	grid-template-columns: minmax(100px, 20%) minmax(100px, 20%) minmax(100px, 20%) minmax(100px, 20%);
	grid-auto-rows: 20%;
	justify-content: space-evenly;
	grid-gap: 15px 0;
	position: relative;
	overflow: auto;
}

@media screen and (max-width:850px) {
	.right_content_window {
	  grid-template-columns: minmax(5em, 20%) minmax(5em, 20%) minmax(5em, 20%) minmax(5em, 20%);
	}
}

@media screen and (max-width:750px) {
	.right_content_window {
	  grid-template-columns: minmax(5em, 30%) minmax(5em, 30%) minmax(5em, 30%);
	}
}

@media screen and (max-width:530px) {
	.right_content_window {
	  grid-template-columns: minmax(0, 30%) minmax(0, 30%) minmax(0, 30%);
	}
}

@media screen and (max-width:475px) {
	.right_content_window {
	  grid-template-columns: minmax(0, 45%) minmax(0, 45%);
	}
}

@media screen and (max-width:330px) {
	.right_content_window {
	  grid-template-columns: minmax(0, 95%);
	}
}

.right_bottom_bar {
	height: 15%;
	z-index: 3;
	overflow: hidden;
	position: relative;
}




.right_bottom_bar .title {
	height:36%;
	z-index: 3;
	overflow: hidden;
	position: relative;
}

.right_bottom_bar_options {
	height: 45%;
	width: 6%;
	overflow: hidden;
	margin: 10 2 0 10;
}

@media screen and (max-width:1000px) {
	.right_bottom_bar_options {
	  width: 8%
	}
}

@media screen and (max-width:700px) {
	.right_bottom_bar_options {
	  width: 11%
	}
}

@media screen and (max-width:600px) {
	.right_bottom_bar_options {
	  width: 13%
	}
}

.content_tiles {
	background-color: #262626;
	
	z-index: 3;
	position: relative;
	margin: 2% 1% 2% 1%;
	outline: 2px solid #1a1a1a;
	overflow: hidden;
}
.content_tiles_hidden {
	opacity: 0.3;
}
.content_tiles_hidden:hover {
	opacity: 0.8;
}

.content_tiles:hover {
	background-color: #4d4d4d;
}

.content_tiles:hover span {
	visibility: visible;
}

.image_name {
	overflow: hidden;
	position: absolute;
	top: 1;
	color: white;
	font-weight: bold;
	letter-spacing: 1px;
	font-family: 'Alegreya';font-size: 12px;
}

.image_name span{
	overflow: hidden;
	visibility: hidden;
	position: relative;
}

.image_name_hidden {
	color: white;
	opacity: 1.0;
}

.content_tiles_image {
	top: 15%;
	height: 80%;

	position: relative;
}



.item_options {
	z-index: 6;
	bottom: 0;
	height: 25%;
	width: 100%;
	overflow: hidden;
	position: absolute;
}
.item_options_add {
	left: 0;
	bottom: 0;
	z-index: 8;
	width: 33%;
	margin: 0.2em 0 0 0;
	position: absolute;
	opacity: 1.0;
}



@media screen and (max-width:900px) {
	.item_additems img {
	  width: 1.5em;
	  height: 1.5em;
	}
	.item_removeitems img {
	  width: 1.5em;
	  height: 1.5em;
	}
}

@media screen and (max-width:750px) {
	.item_additems img {
	  width: 2.5em;
	  height: 2.5em;
	}
	.item_removeitems img {
	  width: 2.5em;
	  height: 2.5em;
	}
}

@media screen and (max-width:600px) {
	.item_additems img {
	  width: 2.2em;
	  height: 2.2em;
	}
	.item_removeitems img {
	  width: 2.2em;
	  height: 2.2em;
	}
}
@media screen and (max-width:500px) {
	.item_additems img {
	  width: 2em;
	  height: 2em;
	}
	.item_removeitems img {
	  width: 2em;
	  height: 2em;
	}
}

.item_options_center {
	z-index: 7;
	text-align: center;
	left: 50%;
	margin: 0 0 0 0;
	bottom: 0;
	transform: translate(-0.2em, 15%);
	position: absolute;
	font-family: 'Alegreya';
	font-size: 40px;
	color: orange;
	text-shadow: 1px 1px 1px #000000;
}

@media screen and (max-width:700px) {
	.item_options_center {
	  top: 0;
	  bottom: initial;
	  transform: translate(-0.2em, -30%);
	  font-size: 4em;
	}
}

.item_options_remove {
	right: 0;
	z-index: 8;
	bottom: 0;
	position: absolute;
	opacity: 1;
}


.popup_window {
	visibility: hidden;
	background-color: #262626;
	height: 30%;
	width: 70%;
	z-index: 5;
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	opacity: 0.7;
}

#new_stockpile {
	background-color: #800000;
}


.popup_window_inner {
	
	position: relative;
	margin: 10 10 10 10;
	//outline: 2px solid red;
	width: 95%;
	height: 60%;
	top: 50%;
	left: 50%;
	z-index: 10;
	transform: translate(-50%, -50%);
	text-align: center;
}

.create_stockpile_button {
	left: 0;
}

.tooltiptext {
  visibility: hidden;
  width: 120px;
  background-color: black;
  color: #fff;
  text-align: center;
  border-radius: 6px;
  padding: 5px 0;

  /* Position the tooltip */
  position: absolute;
  z-index: 10;
}


.change_stockpile_links {
	z-index: 15;
}


.footer {
	bottom: 0;
	position:relative;
	text-align: center;
	margin-left: auto;
	margin-right: auto;
}

#admin {
	opacity: .5;
}

#popup_window_admin {
	opacity: 0.9;
}
</style>

<div id="container">
	<div class="popup_window" id="pin_code_window">
		<div class="popup_window_inner">
			Please enter the pin code of a stockpile to view it, or enter a new code to create one. You can use the same code in game or make your own.<br>
			<input type="text" placeholder="Enter a 6-24 digit code..." size="100" id="pincode_inputbox"><br><br>
			<input type="button" value="Submit" id="pin_code_submit">  ---------- <input type="button" value="Cancel" id="pin_code_cancel"> 
		</div>
	</div>
	
	<div class="popup_window" id="popup_window_admin">
		<div class="popup_window_inner" id="popup_window_inner_admin">
			
		</div>
	</div>
	
	<div class="popup_window" id="error_screen">
		<div class="popup_window_inner">
			<span id="error_screen_message"></span><br><br>
			<input type="button" value="Okay" id="error_ok_button"> 
		</div>
	</div>
	<div class="popup_window" id="new_stockpile">
		<div class="popup_window_inner">
			<span id="new_stockpile_text"></span><br><br>
			<input type="text" placeholder="Enter a 5-30 character name for your stockpile..." size="100" id="new_stockpile_name_inputbox"><br><br>
			<input type="button" value="Create Stockpile" id="new_stockpile_name_submit" class="create_stockpile_button"> ---------- <input type="button" value="Cancel" id="new_stockpile_name_cancel"> 
		</div>
	</div>
	<div id="topleft">
		<div class="left_container">
			<div class="title log_title">
				<div class="title_text">
					Log
				</div>
			</div>
			<div class="log_details"></div>
		</div>
	</div>
	<div id="bottomleft">
		<div class="title">
			<div class="title_text">
				Options
			</div>
		</div>
		<div class="options_details">
			
		</div>
	</div>

	<div id="right">
		<div class="title title_right">
			<div class="title_text">
				Stockpile
			</div>
		</div>
		<div class="sort_options">
			<div class="icons">
				<img src="imgs/icons/nonselected/all_items_icon.jpg" alt="All Items" width="100%" height="100%" id="allitems" class="sort_icons">
			</div>
			<div class="icons">
				<img src="imgs/icons/nonselected/light_ammo_icon.jpg" alt="Light Ammo" width="100%" height="100%" id="lightammo" class="sort_icons">
			</div>
			<div class="icons">
				<img src="imgs/icons/nonselected/heavy_ammo_icon.jpg" alt="Heavy Ammo" width="100%" height="100%" id="heavyammo" class="sort_icons">
			</div>
			<div class="icons">
				<img src="imgs/icons/nonselected/tools_icon.jpg" alt="Tools" width="100%" height="100%" id="tools" class="sort_icons">
			</div>
			<div class="icons">
				<img src="imgs/icons/nonselected/shirts_icon.jpg" alt="Logistics Items" width="100%" height="100%" id="logiitems" class="sort_icons">
			</div>
			<div class="icons">
				<img src="imgs/icons/nonselected/medical_icon.jpg" alt="Medical Items" width="100%" height="100%" id="meditems" class="sort_icons">
			</div>
			<div class="icons">
				<img src="imgs/icons/nonselected/IconFilterVehicle.jpg" alt="Vehicals" width="100%" height="100%" id="vehicals" class="sort_icons">
			</div>
			<div class="icons">
				<img src="imgs/icons/nonselected/vehicals_icon.jpg" alt="Vehical Crates" width="100%" height="100%" id="vehical_crates" class="sort_icons">
			</div>
			<div class="icons">
				<img src="imgs/icons/nonselected/large_items_icon.jpg" alt="Large Items" width="100%" height="100%" id="largeitems" class="sort_icons">
			</div>
			<div class="stockpile_dropdown">
				<button class="stockpile_dropbtn" id="active_stockpile_btn" type="button">No Stockpiles found</button>
				<div class="stockpile_dropdown_content" id="stockpiles">
					<a href="#"></a>
				</div>
				
			</div>
			
			
		</div>
		<div class="right_content_window" id="contentarea">

		</div>
		<div class="right_bottom_bar">
			<div class="title title_right">
				<div class="title_text">
					Lower Options
				</div>
			</div>
			<!--
			<div class="icons right_bottom_bar_options">
				<img src="imgs/icons/nonselected/enter_code_icon.jpg" alt="Enter Code" width="100%" height="100%" id="stockpile_code" data-toggle="tooltip" title="Enter Pin Code"><span class="tooltiptext">Enter Stockpile Pin Code</span>
			</div>
			<div class="icons right_bottom_bar_options">
				<img src="imgs/icons/btLock.png" alt="Stockpile Admin" id="admin" width="40px" height="50px" data-html="true" data-toggle="tooltip" title="Admin Panel"><span class="tooltiptext">Admin Options</span>
			</div>
			-->
		</div>
	</div>
	
</div>
	<center><div class="footer"><center><font color="red"><b>BETA</font></b> Ver: 0.9 - Created by <a href="https://steamcommunity.com/id/Jaminb2030/" target="blank">Universum</a> with help from the <a href="http://www.joinsom.com" target="blank">SOM</a> community! Images may be subject to &copy; <a href="https://www.foxholegame.com/" target="blank">Clapfoot Inc</a></div>
	</div></center>

<script>
function populate_tiles(array, item, index){
	found = false;
	
	
	array[1].forEach(function(item_check, index_check){
		if(item_check["pcname"] == item["pcname"]){
			found = true;
		}
	});
	if(found){
		return array[1];
	}
	var img = "";
	if(item["icon"].length > 0){
		img = '/imgs/icons/items/' + item["pcname"] + '.png';
		
	} else {
		if(typeof item["img"] === 'undefined' || item["img"] == "" || item["img"].length < 5){
			if(item["pcname"] == "damsoulst" || item["pcname"] == "sinsoulst"){
				img = '/imgs/icons/' + item["pcname"] + '.png';
			} else {
				img = '/imgs/no_image.png';
			}
			
		}else if(item["img"].includes('R-5_"Atlas"_Hauler_Vehicle_Icon.png')){
			img = '/imgs/icons/R-5__Atlas__Hauler_Vehicle_Icon.png';
		} else{
			img = base_url + item["img"];
		}
	}
	
	var count_temp = 0;
	
	if(active_stockpile_counts.length > 0){
		active_stockpile_counts.forEach(function(stockpile_item, stockpile_index){
			if(stockpile_item["pcname"] == item["pcname"]){
				count_temp = stockpile_item["amount"];
			}
		});

	}
	var class_name = "content_tiles";
	var count_temp_string = "";
	if(count_temp > 0){
		count_temp_string = count_temp
	} else {
		class_name = "content_tiles content_tiles_hidden";
	}
	
	var content_html = '<div class="' + class_name + '" id="tilecontrol_' + item["pcname"] + '"><div class="content_tiles_image"><img src="' + img + '" height="100%" width="100%"></div><div class="image_name"><span>' + item["name"] + '</span></div>';
		content_html += '<div class="item_options_add">'; // Start Add buttons
		if(usemulti){
			content_html += '<div class="item_additems" id="add_' + item["pcname"] + 'x40"><img src="./imgs/icons/add_icon_x40.png" width="20px" height="20px"></div>';
			content_html += '<div class="item_additems" id="add_' + item["pcname"] + 'x15"><img src="./imgs/icons/add_icon_x15.png" width="20px" height="20px"></div>';
			content_html += '<div class="item_additems" id="add_' + item["pcname"] + 'x5"><img src="./imgs/icons/add_icon_x5.png" width="20px" height="20px"></div>';
		}
		content_html += '<div class="item_additems" id="add_' + item["pcname"] + '"><img src="./imgs/icons/add_icon.png" width="20px" height="20px"></div>';
		content_html += '</div>'; // End add buttons
		
		
		content_html += '<div class="item_options_remove">'; // Start remove buttons
		if(usemulti){
			content_html += '<div class="item_removeitems" id="remove_' + item["pcname"] + 'x40"><img src="./imgs/icons/remove_icon_x40.png" width="20px" height="20px"></div>';
			content_html += '<div class="item_removeitems" id="remove_' + item["pcname"] + 'x15"><img src="./imgs/icons/remove_icon_x15.png" width="20px" height="20px"></div>';
			content_html += '<div class="item_removeitems" id="remove_' + item["pcname"] + 'x5"><img src="./imgs/icons/remove_icon_x5.png" width="20px" height="20px"></div>';
		}
		content_html += '<div class="item_removeitems" id="remove_' + item["pcname"] + '"><img src="./imgs/icons/remove_icon.png" width="20px" height="20px"></div>';
		content_html += '</div>'; // End remove buttons
		
		content_html += '<div class="item_options_center" id="count_' + item["pcname"] + '">' + count_temp_string + '</div>'; // Center counter
		content_html += '</div>';
	
	if(array[0].length>0){
		if(item["type"] == array[0]){
			$("#contentarea").append(content_html);
			//console.log('Set item: ' + item["name"] + ' with image: ' + img);
		}
	} else{
		$("#contentarea").append(content_html);
		//console.log('Set item: ' + item["name"] + ' with image: ' + img);
	}
	array[1].push(item);
	
	return array[1]
}

function populate_tiles_run(type=""){
	var processed = [];
	var array = [type, processed]
	console.log(typeof active_stockpile_counts)
	console.log(active_stockpile_counts)
	active_stockpile_counts.forEach(function(item, index){
		if(item["amount"] > 0){
			array[1] = populate_tiles(array, item, index)
		}
	});
	
	all_items.forEach(function(item, index){
		
		array[1] = populate_tiles(array, item, index)
	});
	
}


function filter_list(name="", id="", selected_img_filename="", type=""){
	reset_all_icons()
	current_filter = [name, id, selected_img_filename, type];
	$("#" + id).attr("src","imgs/icons/selected/" + selected_img_filename);
	$("#contentarea").html("");
	if(type != "options"){
		populate_tiles_run(type)
	}
	
	makechange()
}

$(document).ready(function(){
	$('#allitems').click(function() {
		filter_list("ALL Items", "allitems", "all_items_icon.jpg", "");
	});

	$('#lightammo').click(function() {
		filter_list("Light Ammo", "lightammo", "small_ammo_selected.jpg", "light");
	});
	
	$('#heavyammo').click(function() {
		filter_list("Heavy Ammo", "heavyammo", "heavy_ammo_selected.jpg", "heavy");
	});
	
	$('#tools').click(function() {
		filter_list("Tools", "tools", "tools_icon_selected.jpg", "tools");
	});
	
	$('#logiitems').click(function() {
		filter_list("Logistics Items", "logiitems", "shirts_icon_selected.jpg", "logi");
	});
	
	$('#meditems').click(function() {
		filter_list("Medical Items", "meditems", "med_icon_selected.jpg", "med");
	});
	
	$('#vehicals').click(function() {
		filter_list("Vehicals", "vehicals", "vehical_icon_selected.jpg", "vehicals");
	});
	$('#vehical_crates').click(function() {
		filter_list("Vehical Crates", "vehical_crates", "vehical_crate_icon_selected.jpg", "vehical_crates");
	});
	
	$('#largeitems').click(function() {
		filter_list("Large Items", "largeitems", "large_items_selected.jpg", "large");
	});
	
	
	$('#stockpile_code').click(function() {
		$('#pin_code_window').visibilityToggle();
	});
	
	$('#admin').click(function() {
		if(useraccess>=6){
			$('#popup_window_admin').visibilityToggle();
			admin_panel()
		}
	});
	
	$('#pin_code_cancel').click(function() {
		$('#pin_code_window').css('visibility', 'hidden');
	});
	$('#pin_code_submit').click(function() {
		$('#pin_code_window').css('visibility', 'hidden');
		pincode_entered = $('#pincode_inputbox').val();
		console.log("Code: " + pincode_entered + " entered.");
		enter_pin_code(pincode_entered)
		
		
	})
	$('#new_stockpile_name_cancel').click(function() {
		$('#new_stockpile').css('visibility', 'hidden');
	});
	
	$('#error_ok_button').click(function() {
		$('#error_screen').css('visibility', 'hidden');
	});
	
	$('#new_stockpile_name_submit').click(function() {
		$('#new_stockpile').css('visibility', 'hidden');
		var name = $('#new_stockpile_name_inputbox').val();
		console.log("Name: " + name + " entered.");
		enter_pin_code(pincode_entered, name)
	})
	
	$('#active_stockpile_btn').click(function() {
		$('#stockpiles').visibilityToggle();
		
	})
	
	$(document).on("click", ".item_additems", function() {
		var id = $(this).attr('id').split("add_")[1];
		var amt = 1;
		if(id.includes("x5")){
			id = id.split("x5")[0];
			amt = 5;
		} else if(id.includes("x15")){
			id = id.split("x15")[0];
			amt = 15;
		} else if(id.includes("x40")){
			id = id.split("x40")[0];
			amt = 40;
		}
		makechange(1, id, amt)
		
	})
	
	$(document).on("click", ".item_removeitems", function() {
		var id = $(this).attr('id').split("remove_")[1];
		var amt = 1;
		if(id.includes("x5")){
			id = id.split("x5")[0];
			amt = 5;
		} else if(id.includes("x15")){
			id = id.split("x15")[0];
			amt = 15;
		} else if(id.includes("x40")){
			id = id.split("x40")[0];
			amt = 40;
		}
		makechange(0, id, amt)
		
	})
	
	$(document).on("click", "#obtained_mining", function() {
		toggle_choose(0, "mining")
		
	})
	$(document).on("click", "#obtained_manufactured", function() {
		toggle_choose(1, "manufactured")
		
	})
	$(document).on("click", "#obtained_found", function() {
		toggle_choose(2, "found")
		
	})
	$(document).on("click", "#obtained_moved", function() {
		toggle_choose(3, "moved")
		
	})
	$(document).on("click", "#obtained_correction", function() {
		toggle_choose(4, "correction")
		
	})
	
	$(document).on("click", "#changes_submit", function() {
		document.getElementById("changes_submit").disabled = true;
		submit_stockpile_changes(active_stockpile)
		
	})
	
	$(document).on("click", "#changes_cancel", function() {
		$.getJSON("server/item_list_rev2.json", function(json) { // I do not understand why this is needed, NOTHING changed all_items and yet it seems to be picking up an amount Var
			all_items = json;
		});
		changes = [];
		obtained_from = [0, 0, 0, 0, 0];
		change_type = 0;
		$('.options_details').html("");
		filter_list(current_filter[0], current_filter[1], current_filter[2], current_filter[3]);
	})
	
	
	
	$(document).on('click', 'div[id^="stockpile_change_button"]', function() {
		$('#stockpiles').html('');
		var one = $(this).attr('href');
		var two = $(this).text();
		clearInterval(refreshIntervalId);
		$('#stockpiles').visibilityToggle();
		changes = [];
		setup_stockpiles(one, two);
		makechange()
		populate_tiles_run()
	});	
	
	/*
	$('body').click(function(e) {
		var target = $(e.target), article;
		console.log('You clicked:');
		console.log(e);
	});
	*/
	
	// check for active stockpiles in cookies
	
	get_stockpiles()
	populate_tiles_run()
});

function pin_codes_in_url(){
	console.log("Pin codes: ")
	url = Object.values(getUrlVars("stockpile"));
	if(url.length==1){
		if(url[0].length > 0){
			pincodes = url[0];
			if(pincodes.includes(",")){
				pincodes.split(",").forEach(function(codes, index){
					enter_pin_code(codes);
				});
			} else {
				enter_pin_code(pincodes);
			}
			window.location.href = <?php echo '"'.$host.'"'; ?>;
		}
	}
	
}

function admin_panel(){
	html = "";
	
	if(useraccess>=8){
		html += '<b>Stockpile Name: </b><input id="admin_stockpilename" value="' + active_stockpile[0] + '"><br><br>'
	}
	html += '<div id="admin_discord_intagration"></div>'
	$('#popup_window_inner_admin').html(html);
	if(useraccess>=6){
		discord_admin_text = '1.) Discord Name (EXACT)<input name="admin_discordname_1"> <img src="imgs/icons/question.png" width="25px" height="25px" data-html="true" data-toggle="tooltip" title="Your discord/Community name. Example: Swords of Maro for SOM. Whatever shows when you hover over your discord community icon on the left of Discord."><br>'
		discord_admin_text += '1.) Stockpiler channel: <input name="admin_discordchannel_1"> <img src="imgs/icons/question.png" width="25px" height="25px" data-html="true" data-toggle="tooltip" title="The name of the text channel in your discord community that Stockpiler will report stock changes to. (EXACT)"><br>'
		discord_admin_text += '1.) Commands channel(Optional): <input name="admin_discordcommands_1"> <img src="imgs/icons/question.png" width="25px" height="25px" data-html="true" data-toggle="tooltip" title="The name of the text channel in your discord community that replies to commands will to posted in. You can use commands for Stockpiler in ANY channel however if this box is filled it in will only reply to the person that used the command in the channel listed in this box. Otherwise it will reply in whatever channel the command was used in. Commands like !Stock (EXACT)"><br>'
		discord_admin_text += '<img src="imgs/icons/add_icon.png" width="25px" height="25px" data-html="true" data-toggle="tooltip" title="Add another discord/Channel for this Stockpile."><br>'
		$('#admin_discord_intagration').append('<b>Discord Intagration</b>: <br>' + discord_admin_text);
		console.log("ADMIN: ")
	}
	
	
	
}


function submit_stockpile_changes(active_stockpile){
	change_needed = [];
	changes.forEach(function(change, index){
		change_needed.push([change["pcname"], change["amount"]]);
	})
	
	url = './server/stockpile.php?stockpile=' + active_stockpile[1];
	if($('#submit_notes') !== undefined){
		var notes = $('#submit_notes').val();
	} else {
		var notes = "";
	}
	$.post(url, {data: change_needed, obtained: obtained_from, changetype: change_type, notes: notes}).then(function(server) {
		console.log("Server Stockpile RAW: '" + server + "'");
		server = JSON.parse(server);
		
		//console.log(server);
		if(server["Error"].length>0){
			console.log("Server ERROR: '" + server["Error"] + "'");
			show_error(server["Error"], server['ErrorCode']);
			
		}
		if(server['Access'] !== null){
			useraccess = server['Access'];
			check_access()
		}
		
		active_stockpile_counts = server["Stockpile_data"];
		//console.log(active_stockpile_counts);
		changes = [];
		$.getJSON("server/item_list.json", function(json) { // I do not understand why this is needed, NOTHING changed all_items and yet it seems to be picking up an amount Var
			all_items = json;
		});
		obtained_from = [0, 0, 0, 0, 0];
		makechange(66)
		$('#submit_notes').val("");
		
		if(current_filter[1] != ""){
			filter_list(current_filter[0], current_filter[1], current_filter[2], current_filter[3]);
		} else {
			filter_list("ALL Items", "allitems", "all_items_icon.jpg", "");
		}
		
		get_log(active_stockpile)
	})
	
}

function toggle_choose(num, name){
	if(num == 66){
		
	}
	if(obtained_from[num] == 0){
		obtained_from[num] = 1;
		if(name == "mining"){
			$("#obtained_mining_img").attr('src', './imgs/icons/selected/Harvester.png');
		} else if(name == "manufactured"){
			$("#obtained_manufactured_img").attr('src', './imgs/icons/selected/MapIconFactory.png');
		} else if(name == "found"){
			$("#obtained_found_img").attr('src', './imgs/icons/selected/BicycleVehicleIcon.png');
		} else if(name == "moved"){
			$("#obtained_transfer_img").attr('src', './imgs/icons/selected/MapIconStorageFacility.png');
		} else if(name == "correction"){
			$("#obtained_correction_img").attr('src', './imgs/icons/selected/correction.png');
		}
		
		
		
	} else {
		obtained_from[num] = 0;
		if(name == "mining"){
			$("#obtained_mining_img").attr('src', './imgs/icons/nonselected/Harvester.png');
		} else if(name == "manufactured"){
			$("#obtained_manufactured_img").attr('src', './imgs/icons/nonselected/MapIconFactory.png');
		} else if(name == "found"){
			$("#obtained_found_img").attr('src', './imgs/icons/nonselected/BicycleVehicleIcon.png');
		} else if(name == "moved"){
			$("#obtained_transfer_img").attr('src', './imgs/icons/nonselected/MapIconStorageFacility.png');
		} else if(name == "correction"){
			$("#obtained_correction_img").attr('src', './imgs/icons/selected/correction.png');
		}
	}
}

function show_error(message, code=''){
	console.log('ERROR');
	$('#error_screen_message').html(message);
	$('#error_screen').css('visibility', 'visible');
	
}

function makechange(type=1, name="", amt=1){
	if(type == 66){
		$('.options_details').html("");
	}

	item_template = {};
	if(name.length > 0){
		for(let i = 0; i < all_items.length; i++){ 
			if(all_items[i]["pcname"] == name){
				item_template = all_items[i];
			}
		};
		if(item_template["amount"] === undefined){
			item_template["amount"] = 0;
		}
		var display_count = 0;
		//console.log(active_stockpile_counts);
		
		var change_index = 0;
		changes.forEach(function(changes_item, changes_index){
			if(changes_item["pcname"] == item_template["pcname"]){
				display_count += Number(changes_item["amount"]);
				change_index = changes_index;
			}
		});
		active_stockpile_counts.forEach(function(stockpile_item, stockpile_index){
			if(stockpile_item["pcname"] == item_template["pcname"]){
				display_count += Number(stockpile_item["amount"]);
			}
		});
		//console.log("Changes1:")
		//console.log(changes);
		if(display_count == 0 && type == 0){
			console.log("return fired")
			return
		}
		
		var key = 0;
		var found = false;
		changes.forEach(function(changes_item, index){
			//console.log("Changes ran");
			//console.log(item_template);
			if(changes_item["pcname"] == item_template["pcname"]){
				found = true;
				//console.log("Changes Amt: " +changes_item["amount"]);
				if(changes_item["amount"] !== undefined){
					var stockpile_found = false;
					active_stockpile_counts.forEach(function(stockpile_item, stockpile_index){
						if(stockpile_item["pcname"] == item_template["pcname"]){
							stockpile_found = true;
							//console.log(changes_item["amount"] + " - " + stockpile_item["amount"]);
							
							display_count = Number(changes_item["amount"]) + Number(stockpile_item["amount"]);
							//console.log(display_count);
						}
					});
					if(!stockpile_found){
						display_count = Number(changes_item["amount"]);
					}
					//console.log("Display Count: " + display_count);
					if(type == 1){
						var count = Number(changes_item["amount"]) + amt;
						display_count += amt;
					} else {
						if(display_count > 0){
							var count = Number(changes_item["amount"]) - amt;
							display_count -= amt;
						} else {
							var count = Number(changes_item["amount"]);
						}
					}
					changes[index]["amount"] = count;
					key = index;
					
				} else {
					if(type == 1){
						display_count += amt;
						item_template["amount"] = amt;
						changes[index]["amount"] = amt;
					} else {
						if(display_count > 0){
							display_count += amt
							item_template["amount"] = -amt
							changes[index]["amount"] = amt;
						}
					}
					
					
				}
				
			}
			if(Number(changes[index]["amount"]) == 0){
				delete(changes[index]);
			}
		});
		
		
		if(found == false){
			if(type == 1){
				if(item_template["amount"] === undefined){
					item_template["amount"] = amt;
				} else {
					item_template["amount"] += amt;
				}
				display_count += amt;
				
			} else {
				if(item_template["amount"] === undefined){
					item_template["amount"] = -amt;
				} else {
					item_template["amount"] -= amt;
				}
				display_count -= amt;
			}
			
		}
		console.log("Display Count: " + display_count);
		
		if(display_count > 0){
			if(found == false){
				changes.push(item_template);
			}
			
		} else if (display_count == 0) {
			if(found == false){
				changes.push(item_template);
			}
		} else {
			display_count = 0;
		}
		
		
	}
	
	
	if(changes.length > 0){
		var change_string = "";
		var addition = false;
		var type_mined = false;
		var type_other = false;
		var type_any = false;
		
		changes.forEach(function(item, index){
			
			if(change_string.length == 0){
				firstone = '<div class="pending_top"><center><b>Pending changes...</b></center><br>';
			} else {
				firstone = "<br> ";
			}
			
			
			//console.log("found: " + found);
			
			//console.log(display_count);
			if(item["amount"] != 0){
				type_any = true;
				if(item["amount"] > 0){
					if(change_type == 2){ // 1 = Add, 2 = subtract, 3 = both, tells the server if your adding or removing stock.
						change_type = 3;
					} else{
						change_type = 1;
					}
				} else {
					if(change_type == 1){
						change_type = 3;
					} else{
						change_type = 2;
					}
				}
			}
			if(item["amount"] > 0){

				addition = true;
				if(item['pcname'] == "bmats" || item['pcname'] == "rmats" || item['pcname'] == "emats" || item['pcname'] == "hemats"){
					type_mined = true;
				} else {
					type_other = true;
				}
				change_string += firstone + '<b><font color="green">+' + item["amount"] + '</font> ' + item["name"] + '</b>';
				
			} else if(item["amount"] != 0) {
				var check = false;
				changes.forEach(function(item, index){
					if(item['pcname'] == "bmats" || item['pcname'] == "rmats" || item['pcname'] == "emats" || item['pcname'] == "hemats"){
						check = true;
					}
				});
				if(!check){
					type_mined = false;
				}
				change_string += firstone + '<b><font color="red">' + item["amount"] + '</font> ' + item["name"] + '</b>';
			}
			
			var current_counter = 0;
			var matched = false;
			active_stockpile_counts.forEach(function(stockpile_item, stockpile_index){
				if(stockpile_item["pcname"] == item["pcname"]){
					current_counter = Number(stockpile_item["amount"]) + Number(item["amount"]);
					matched = true;
				}
			});
			if(!matched){
				current_counter = Number(item["amount"]);
			}
			
			if(current_counter > 0){
				$('#count_' + item["pcname"]).html(current_counter);
				$('#tilecontrol_' + item["pcname"]).removeClass("content_tiles_hidden");
			} else {
				$('#count_' + item["pcname"]).html("");
				$('#tilecontrol_' + item["pcname"]).addClass("content_tiles_hidden");
			}
		});
		if(addition){
			change_string += '<div class="submit_counts"><center>Obtained from: (Choose) </center>';
		}
		
		if(type_mined && type_other){
			change_string += '<div class="icons icons_options"  id="obtained_mining"><span class="icons_options_text"><b><center>Mining</center></b></span><img src="imgs/icons/nonselected/Harvester.png" alt="Mining" width="100%" height="100%" id="obtained_mining_img"></div>';
			change_string += '<div class="icons icons_options"  id="obtained_manufactured"><span class="icons_options_text"><b><center>Produced</center></b></span><img src="imgs/icons/nonselected/MapIconFactory.png" alt="Produced" width="100%" height="100%" id="obtained_manufactured_img"></div>';
			
			
		} else if(type_other) {
			change_string += '<div class="icons icons_options"  id="obtained_manufactured"><span class="icons_options_text"><b><center>Produced</center></b></span><img src="imgs/icons/nonselected/MapIconFactory.png" alt="Produced" width="100%" height="100%" id="obtained_manufactured_img"></div>';
		} else if(type_mined){
			change_string += '<div class="icons icons_options"  id="obtained_mining"><span class="icons_options_text"><b><center>Mining</center></b></span><img src="imgs/icons/nonselected/Harvester.png" alt="Mining" width="100%" height="100%" id="obtained_mining_img"></div>';
		}
		if(addition){
			change_string += '<div class="icons icons_options"  id="obtained_found"><span class="icons_options_text"><b><center>Found it</center></b></span><img src="imgs/icons/nonselected/BicycleVehicleIcon.png" alt="Found it" width="100%" height="100%" id="obtained_found_img"></div>';
			change_string += '<div class="icons icons_options"  id="obtained_moved"><span class="icons_options_text"><b><center>Stockpile Transfer</center></b></span><img src="imgs/icons/nonselected/MapIconStorageFacility.png" alt="Moved from another stockpile" width="100%" height="100%" id="obtained_transfer_img"></div>';
			
		}
		if(type_any){
			change_string += '<div class="icons icons_options"  id="obtained_correction"><span class="icons_options_text"><b><center>Stockpile Correction</center></b></span><img src="imgs/icons/nonselected/correction.png" alt="Correcting Stockpile Counts" width="100%" height="100%" id="obtained_correction_img"></div>';
			change_string += '</div><br><br><center><input type="button" value="Submit Changes" id="changes_submit">  ---------- <input type="button" value="Clear Pending Changes" id="changes_cancel"> <textarea id="submit_notes" rows="3" cols="50" maxlength="2000" placeholder="Notes for this change.."></textarea></center>';
		}
		
		
		
		$('.options_details').html(change_string);
		//console.log(item_template['name'] + "'s stock changed to " + display_count);
	}
	
	
	item_template = null;
}

function setup_stockpiles(internal_name="", external_name="", descriptor="", del=false){
	active_stockpile_counts = [];
	console.log(internal_name + " - [" + descriptor + "]" + external_name)
	active_stockpile = []; // Current stockpile on screen
	active_stockpile_counts.forEach(function(item, index){
		if(active_stockpile_counts[index]["amount"] === undefined){
			active_stockpile_counts[index]["amount"] = 0;
		}
	})
	
	
		
		
	if(del){
		// Delete the stockpile in internal_name from active_stockpiles and list_stockpiles
		if(list_stockpile.length > 0){
			if(active_stockpile.length > 0 && active_stockpile['ID'] == internal_name){
				active_stockpile = []
			}
			var temp = []
			list_stockpile.forEach(function(item, index){
				if(item['ID'] != internal_name){
					if(active_stockpile.length == 0){
						active_stockpile = item
					}
					temp.push([item['id'], item['NAME'], item['NAME']]);
				}
			});
			list_stockpile = temp;
		}
	} else{
		if(external_name.length > 0 && internal_name.length > 0){
			active_stockpile = [external_name, internal_name, descriptor]
		} else {
			if(list_stockpile.length > 0){
				active_stockpile = [list_stockpile[0][0], list_stockpile[0][1], list_stockpile[0][2]]
			}
			
		}
			
	}

	stockpile_counter = list_stockpile.length;
	
	console.log("Stockpiles: ");
	console.log(list_stockpile);

	console.log("Active stockpile set to: ");
	console.log(active_stockpile);
	
	

	reset_selectstockpile_dropdown()
	
	
	
	get_log(active_stockpile)
	if(!del){
		//console.log(active_stockpile);
		refreshIntervalId = setInterval(function() {
			get_log(active_stockpile, log_rev_number)
		}, 5000);
	}

	
}

function reset_selectstockpile_dropdown(){
	var temp_list = "";
	list_stockpile.forEach(function(item, index){
		if(item[1] != active_stockpile[1]){
			if(item.length > 2){
				temp_list += '<div id="stockpile_change_button" href="' + item[1] + '" class="change_stockpile_links">[' + item[2] + ']' + item[0] + '</div>';
			} else {
				temp_list += '<div id="stockpile_change_button" href="' + item[1] + '" class="change_stockpile_links">' + item[0] + '</div>';
			}
			
		}
	})
	$('#stockpiles').html(temp_list);
	name_string = active_stockpile[0];
	if(name_string.length > 25){
		name_string = name_string.substr(0, 25) + "...";
	}
	$('#active_stockpile_btn').html('[' + active_stockpile[2] + ']' + name_string + '<img src="imgs/dropdown.jpg" id="stockpile_dpdown_img">');
	log_rev_number = 0;
}


function get_stockpiles(code=""){
	$('.log_details').html('<b><font color="white">SYSTEM:</font> </b> <br>Loading stockpiles you have access to, one moment<span id="wait">.</span>')
			
	window.dotsGoingUp = true;
	var dots = window.setInterval( function() {
		var wait = document.getElementById("wait");
		if ( window.dotsGoingUp ) 
			wait.innerHTML += ".";
		else {
			wait.innerHTML = wait.innerHTML.substring(1, wait.innerHTML.length);
			if ( wait.innerHTML === "")
				window.dotsGoingUp = true;
		}
		if ( wait.innerHTML.length > 6 )
			window.dotsGoingUp = false;



		}, 100);

	var active_stockpile = [];
	var url = "";
	if(code.length == 0){
		url = './server/get_stockpile_list.php';
	} else {
		url = './server/get_stockpile_list.php?code=' + code;
	}
	
	$.get(url).then(function(server) {
		console.log("Server: '" + server + "'");
		server = JSON.parse(server);
		if(server["Error"].length>0){
			console.log("Server ERROR: '" + server["Error"] + "'");
			show_error(server["Error"], server['ErrorCode']);
		}
		
		if(server["Stockpiles"].length == 0){
			// Stockpile does not exist
			$('.log_details').html('<b><font color="white">SYSTEM:</font> </b> No stockpiles exist for you. If you are a Discord Guild admin add the bot to your server: <a href="https://discord.com/api/oauth2/authorize?client_id=776281678494040085&permissions=141312&redirect_uri=https%3A%2F%2Fwww.stockpiler.net&scope=bot">Discord BOT</a></span>')
		} else {
			active_stockpile_counts = [];
			changes = [];
			obtained_from = [0, 0, 0, 0, 0];
			change_type = 0;
			log_rev_number = 0;
			$('#new_stockpile').css('visibility', 'hidden');
			list_stockpile = [];
			
			first = []
			for (var i = 0; i < server["Stockpiles"].length; i++) {
				if(first.length == 0){
					first = [server["Stockpiles"][i]['NAME'], server["Stockpiles"][i]['ID'], server["Stockpiles"][i]['DESC']];
				}
				//console.log(server["Stockpiles"][i]['ID'] + " - " + server["Stockpiles"][i]['NAME']);
				list_stockpile.push([server["Stockpiles"][i]['NAME'], server["Stockpiles"][i]['ID'], server["Stockpiles"][i]['DESC']])
			};
			//console.log(first);
			clearInterval(dots)
			setup_stockpiles(first[1], first[0], first[2])
		}
		
	}).catch(function(err) {
		// Instead, this happens:
		console.log("Failed to get stockpile list. Error code: F0VgLWZx", err);
	})
	
}

function get_log(active_stockpile, rev=""){
	//console.log(useraccess)
	if(active_stockpile[1].length == 0){
		reset_selectstockpile_dropdown()
		$('.log_details').html('<b><font color="white">System:</font><font color="red"> ERROR </b></font> getting logs for stockpile, please try refreshing the page.')
		return 0
	}
	var url = "";
	if(rev.length == 0){
		url = './server/get_logs.php?stockpile=' + active_stockpile[1] + '&name=' + active_stockpile[0];
	} else {
		url = './server/get_logs.php?stockpile=' + active_stockpile[1] + '&name=' + active_stockpile[0] + '&rev=' + rev;
	}
	
	$.get(url).then(function(server) {
		//console.log(server);
		server = JSON.parse(server);
		
		if(server["Error"].length>0){
			console.log("Server ERROR: '" + server["Error"] + "'");
			show_error(server["Error"], server['ErrorCode']);
			if(server['ErrorCode'] == 'QaIVgui' || 'Lsa5GAeh' || 'f2ydlYSy' || '2GURAWzs' || 'MMTEtbJ7'){
				setup_stockpiles(active_stockpile[1], active_stockpile[0], active_stockpile[2], true);
				return
			}
		}
		
		if(server["StockpileType"] == 0){
			$('#new_stockpile').css('visibility', 'visible');
			$('#new_stockpile_text').html('No stockpile with code <b>' + pincode_entered + '</b> found, if you would like to create this stockpile, enter a name for this stockpile below and hit Create Stockpile. Otherwise hit Cancel.');
			$('#new_stockpile_name_inputbox').focus();
		}
		if(server["StockpileStatus"] != 3 && server["status"] != 35){ // #3 = No new logs but the log does exist. #35 = A request to fully update all logs.
			$('.log_details').html("");
			server["Stockpile_Log"].reverse();
			console.log(server)
			server["Stockpile_Log"].forEach(function(item, index){
				var from_now = 30000;
				var user = "";
				var timestamp = "";
				if(!!item["timestamp"] && item["timestamp"].length > 0){
					var datestring = item["timestamp"].replace(" ", "T") + "-08:00";
					var d = new Date(datestring);
					from_now = Math.abs(new Date() - d) / 1000;
					
					let formatted_date = appendLeadingZeroes(d.getMonth() + 1) + "-" + appendLeadingZeroes(d.getDate()) + " " + appendLeadingZeroes(d.getHours()) + ":" + appendLeadingZeroes(d.getMinutes()) + ": ";

					timestamp = formatted_date;
				}
				if(item["steamid"] == "0"){
					user = '<b><font color="white">System: </b></font>'
				}
				var undo_option = "";
				
				if(item["log_type"] == "change" && from_now < 300 && item["steamid"] == steamid){
					undo_option = '<div id="' + item["id"] + '_timerclass" class="undo_button"><a href="javascript:function no() { return false; }" onclick="undo_last(' + item["id"] + ')">Undo - <span id="' + item["id"] + '_timer">5:00</span></a></div>';
					var endDate = new Date(d.getTime() + 300000).getTime(); // 5 Minutes in Mili-Seconds
					var timer = setInterval(function() {
						if(document.getElementById(item["id"] + "_timer") === null){
							clearInterval(timer);
						} else {
							let now = new Date().getTime(); 
							let t = endDate - now;
							if (t >= 0) {
								let mins = Math.floor((t % (1000 * 60 * 60)) / (1000 * 60));
								let secs = Math.floor((t % (1000 * 60)) / 1000);
								document.getElementById(item["id"] + "_timer").innerHTML = mins + ":" + secs;
							} else {
								document.getElementById(item["id"] + "_timerclass").innerHTML = "";
								clearInterval(timer);
							}
						}
					}, 1000);
					item["notes"] = item["notes"].replace("<hr>", "") + undo_option + "<hr>";
					
				}
				
				$('.log_details').append(timestamp + user + item["notes"] + "<br>");
				
			});
			
			log_rev_number_temp = log_rev_number;
			log_rev_number = server["Stockpile_Log"][0]["id"];
			if(log_rev_number_temp < log_rev_number){
				submit_stockpile_changes(active_stockpile)
			}
		}
		if(server['Status'] !== null){
			if(server['Status'] == 35){// a request to update both logs and stockpile data
				if(!undo){ // If your not the one that trigger the update.
		
					log_rev_number = 0; // Tells it to pull all logs from 0
					setup_stockpiles(server["StockpileName"]["Internal"], server["StockpileName"]["External"]) // reload current stockpile
				} else {
					undo = false;
				}
			} else if(server['Status'] == 8){
				if(server['Access'] !== null){
					useraccess = server['Access'];
					check_access()
				} else {
					show_error('Error: Looks like you had a access change but i was unable to load your new abilities live, try reloading the page.', 'zaTukqNK');
				}
			}
		}
		
		if(server["StockpileStatus"] == 3 && log_rev_number == 0){
			$('.log_details').html('<b><font color="white">System:</font></b> No logs yet for this stockpile. Make a change to create a log.')
			return
		}
		
	}).catch(function(err) {
		// Instead, this happens:
		console.log("Failed to load log data.", err);
	})
	
}

function check_access(){
	
	if(useraccess >= 6){
		$('#admin').css("opacity", "1");
	} else {
		$('#admin').css("opacity", "0.5");
		
		var text = "Admin Panel - You are NOT and Admin. Ask the stockpile owner for admin access."
		$('#admin').attr("title",text);
	}
}
//Admin Panel - You are <b><font color="red">NOT</font><b> and Admin. Ask the stockpile owner for admin access.
function undo_last(id){
	var name = '#' + id + '_timerclass';
	refresh_logs("<b>System: </b>Undoing stock request... one moment..")
	$(name).html("");
	
	url = './server/undo.php?stockpile=' + active_stockpile[1] + '&log=' + id;
	$.get(url).then(function(server) {
		console.log("Server: '" + server + "'");
		server = JSON.parse(server);
		if(server["Error"].length>0){
			
			console.log("Server ERROR: '" + server["Error"] + "'");
			show_error(server["Error"], server['ErrorCode']);
			if(server['ErrorCode'] == 'QaIVgui' || 'Lsa5GAeh' || 'f2ydlYSy' || '2GURAWzs' || 'MMTEtbJ7'){
				setup_stockpiles(active_stockpile[1], active_stockpile[0], active_stockpile[2], true);
				return
			}
			
		}
		if(log_rev_number == id){
			log_rev_number -= 1 // If your trying to undo the last item entered lower the ID number or else the client and server will get confused over the missing log.
		}
		undo = true  // Let get_logs know you just triggered and update.
		
		refresh_stock()
		
	}).catch(function(err) {
		// Instead, this happens:
		console.log("Failed to undo.", err);
	})
}

function appendLeadingZeroes(n){
  if(n <= 9){
    return "0" + n;
  }
  return n
}

jQuery.fn.visibilityToggle = function() {
    this.css('visibility', function(i, visibility) {
        return (visibility == 'visible') ? 'hidden' : 'visible';
    });
	$('#pincode_inputbox').focus();
	return
}

function refresh_logs(log_message=""){
	log_rev_number = 0; // Tells it to pull all logs from 0
	$('.log_details').html(log_message);
}

function refresh_stock(){
	active_stockpile_counts = [];
	setup_stockpiles()
}

function reset_all_icons(){
	$("#allitems").attr("src","imgs/icons/nonselected/all_items_icon.jpg");
	$("#lightammo").attr("src","imgs/icons/nonselected/light_ammo_icon.jpg");
	$("#heavyammo").attr("src","imgs/icons/nonselected/heavy_ammo_icon.jpg");
	$("#tools").attr("src","imgs/icons/nonselected/tools_icon.jpg");
	$("#logiitems").attr("src","imgs/icons/nonselected/shirts_icon.jpg");
	$("#meditems").attr("src","imgs/icons/nonselected/medical_icon.jpg");
	$("#vehicals").attr("src","imgs/icons/nonselected/IconFilterVehicle.jpg");
	$("#vehical_crates").attr("src","imgs/icons/nonselected/vehicals_icon.jpg");
	$("#largeitems").attr("src","imgs/icons/nonselected/large_items_icon.jpg");
	
}

// cookie stuff

function setCookie(cname, cvalue, exdays) {
  var d = new Date();
  d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
  var expires = "expires="+d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
  var name = cname + "=";
  var ca = document.cookie.split(';');
  for(var i = 0; i < ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}

function checkCookie(cname) {
  var cookie = getCookie(cname);
  if (cookie != "") {
    return 1
  } else {
    return 0
  }
}

function getUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}

</script>

</html>