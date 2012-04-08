<?php
	require "config.php";
	if(!($_GET['ParticipantID'])){
		echo "no ParticipantID found!";
		return;
	}
	$ParticipantID = $_GET['ParticipantID'];
	$sql = "SELECT ".
				"q.queryid as qid, ".
				"q.timestamp as qtime, ".
				"q.site as site, ".
				"q.query as query, ".
				"c.clickid as cid, ".
				"c.timestamp as ctime, ".
				"c.page as page, ".
				"c.`index` as `index`, ".
				"c.title as title, ".
				"c.url as url ".
			" FROM ".$CONFIG['db_tb_logquery']." AS q ".
			" INNER JOIN ".$CONFIG['db_tb_logclick']." AS c ".
				" ON q.queryid = c.queryid ".
				" AND q.participantid = c.participantid ".
			" WHERE q.participantid='".$ParticipantID."' ".
			" ORDER BY qtime ASC,ctime ASC ;";
	$result = mysql_query($sql);
	if($result){
		header("Content-type: application/csv");
		header("Content-Disposition: attachment; filename=log_".$ParticipantID.".csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		//start looping to create table/or group of clicks
		while(($row = mysql_fetch_assoc($result))!=null){
			
			$qid = $row['qid'];
			$qtime = $row['qtime'];
			$site = $row['site'];
			$query = urldecode(str_replace("+"," ",$row['query']));
			$cid = $row['cid'];
			$ctime = $row['ctime'];
			$page = $row['page'];
			$index = $row['index'];
			$title = $row['title'];
			$url = urldecode($row['url']);
		
			echo "$qid,$qtime,$site,$query,$cid,$ctime,$page,$index,$title,$url\n";	
		}
	} else {
		echo "cannot get data from database. ".mysql_error();
	}
?>
