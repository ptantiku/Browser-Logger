<?php
	//$CONFIG['db_host'] = "sylvester-mccoy-v2.ics.uci.edu";
	$CONFIG['db_host'] = "XXXXXXXXXX";
	$CONFIG['db_port'] = "3306";
	$CONFIG['db_name'] = "XXXXXXXXXX";
	$CONFIG['db_user'] = "XXXXXXXXXX";
	$CONFIG['db_pass'] = "XXXXXXXXXX";
	$CONFIG['db_tb_participants'] = "logger_participants";
	$CONFIG['db_tb_logquery'] = "logger_logquery";
	$CONFIG['db_tb_logclick'] = "logger_logclick";
	$CONFIG['db_tb_questionaire']="logger_questinaire";

	$conn = mysql_pconnect($CONFIG['db_host'].':'.$CONFIG['db_port'],$CONFIG['db_user'],$CONFIG['db_pass']) or die("Cannot connect to database : ".mysql_error());
	//$conn = mysql_pconnect('localhost',$CONFIG['db_user'],$CONFIG['db_pass']) or die("Cannot connect to database : ".mysql_error());
	mysql_select_db($CONFIG['db_name'], $conn) or die("Cannot change database : ".mysql_error());
	mysql_query("SET SESSION character_set_results = 'UTF8'") or die("Cannot change encoding : ".mysql_error());

?>
