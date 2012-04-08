<?php

session_start(); 

require "admin/config.php";

function sanitizeData($queries){
	if($queries==null)
		return null;
	foreach($queries as $query){
		foreach($query as $k=>$v){
			if(is_string($v)){
				$query[$k] = mysql_real_escape_string($v);
			}
		}
		if(isset($query['clicks'])){
			foreach($query['clicks'] as $click){
				foreach($click as $k=>$v){
					if(is_string($v)){
						$click[$k] = mysql_real_escape_string($v);
					}
				}
			}
		}
	}

	return $queries;
}

//----------------------BEGIN-----------------------///

	$queries_submit_status = "Submitting ... ";
	$clicks_submit_status = "Submitting ... ";

	//start php script
	if(isset($_SESSION['ParticipantID']) &&
		isset($_SESSION['queries']) && 
		isset($_POST["submit_step2"])) : 
		/*  comment out: move this procedure to review2.php
		 * 
		$ParticipantID = $_POST["ParticipantID-json"];
		$queries = json_decode($_POST["queries-json"],true);

		//sanitize data
		$ParticipantID = preg_replace('/[^a-zA-Z0-9]/', '', $ParticipantID);
		$queries = sanitizeData($queries);
		*/
		//retrieve ParticipantID , queries from $_SESSION
		$ParticipantID = $_SESSION['ParticipantID'];
		$queries = $_SESSION['queries'];
		
		//Starting Transaction
		mysql_query("SET AUTOCOMMIT=0;");
		mysql_query("START TRANSACTION;");
		$transaction_result = true;		

		//---------------------QUERIES SUBMIT----------------------
		if($queries!=null) {
			$qNo=0;
			//start processing queries
			foreach($queries as $query){
				//fixed for rad.ics.uci.edu that use timestamp without microseconds
				if(intval($query["timestamp"]) > 1293868800 * 1000){  //1293868800 is 2011-01-01 00:00:00 in second only
					$query["timestamp"] = intval($query["timestamp"])/1000.0;
				}
						
				$sql = sprintf(" (%s,'%s','%s','%s') ",
						"FROM_UNIXTIME('".$query["timestamp"]."')",
						$ParticipantID,
						mysql_real_escape_string($query["site"]),
						mysql_real_escape_string($query["query"])
				);
	
				$sqlQuery = "INSERT INTO ".$CONFIG['db_tb_logquery']." ".
						"(timestamp,participantid,site,query) VALUES ".$sql;
				$result = mysql_query($sqlQuery);
				if(!$result){
					$queries_submit_status = "Cannot store queries into database.".mysql_error();
					$transaction_result = false; //fail
					break;
				}
			
				//if successfully insert a query, then insert the clicks
				$queryid = mysql_insert_id();	
				if(isset($query["clicks"])){
					$clicks = $query["clicks"];
					foreach($clicks as $click){
						//fixed for rad.ics.uci.edu that use timestamp without microseconds
						if(intval($click["timestamp"]) > 1293868800 * 1000){  //1293868800 is 2011-01-01 00:00:00 in second only
							$click["timestamp"] = intval($click["timestamp"])/1000.0;
						}
						$sql = sprintf(" (%s,'%s',%d,%d,%d,'%s','%s') ",
							"FROM_UNIXTIME('".$click["timestamp"]."')",
							$ParticipantID,
							$queryid,
							$click["page"],
							$click["index"],
							mysql_real_escape_string($click["title"]),
							mysql_real_escape_string($click["url"])
						);
						$sqlClick = "INSERT INTO ".$CONFIG['db_tb_logclick']." ".
								"(timestamp,participantid,".
								"queryid,page,`index`,title,url) VALUES ". $sql;
						$result = mysql_query($sqlClick);
						if(!$result){
							$clicks_submit_status = "Cannot store clicks into database.".mysql_error();
							$transaction_result = false; //fail
							break;
						}
					}
				}
				
				//------INSERTING QUESTIONAIRE
				//sanitize questionaire
				$answer1 = strtolower(trim(mysql_real_escape_string($_POST["q${qNo}_1"])))=="yes"?true:false;
				$answer2 = mysql_real_escape_string($_POST["q${qNo}_2"]);
				$answer3 = strtolower(trim(mysql_real_escape_string($_POST["q${qNo}_3"])))=="yes"?true:false;
				$answer4 = mysql_real_escape_string($_POST["q${qNo}_4"]);
								
				$sql = sprintf(" (%d,'%s','%s','%s','%s') ",
						$queryid,
						$answer1,
						$answer2,
						$answer3,
						$answer4
				);
	
				$sqlQuery = "INSERT INTO ".$CONFIG['db_tb_questionaire']." ".
						"(queryid,question1,question2,question3,question4) VALUES ".$sql;
				$result = mysql_query($sqlQuery);
				if(!$result){
					$queries_submit_status = "Cannot store questionaires into database.".mysql_error();
					$transaction_result = false; //fail
					break;
				}
								
				//if any INSERT command fails, break
				if($transaction_result == false)
					break;
					
				$qNo++;
			}
		}
		
		//Complete the transaction to database
		if($transaction_result){
			mysql_query("COMMIT;");
			$submit_result = "submitted successfully";
			unset($_SESSION['ParticipantID']);
			unset($_SESSION['queries']);
			session_destroy();
		} else {
			mysql_query("ROLLBACK;");
			$submit_result = "failed to submit, please try again";
		}

		$num_sessions = count($queries);
?>
<html>
<style>
span {
	font-weight: bold;
}

</style>
<script>
function closeWindow(){
	window.open('','_self','');  //addition line to fix Chrome
	window.close();
}
</script>
<body>
	<h1 style="text-align:center;">UCI Web-Search Logger Submission</h1>
	<div style="text-align:center;">
	Thank you very much for submitting your search logs. <br>

	You submitted your logs 
	from <span id="start_time">start_time</span> to <span id="end_time">end_time</span> 
	for participant ID <span id="ParticipantID"></span>. 
	<br>
	
	Logs for <span id="num_sessions"><?=$num_sessions?></span> sessions  
	were <span id="submit_result" style="text-decoration:underline;"><?=$submit_result?></span>.
	<br><br>

	<span id="clearlogs" style="font-size:larger;"></span>
	
	<? 
	if (!$transaction_result) {
		echo "<div style='text-color:red'>\n";
		echo "Error occurred during submitting data to database: <br>\n";
		echo "Queries Submitting Status: ".$queries_submit_status."<br>\n";
		echo "Clicks Submitting Status: ".$clicks_submit_status."<br>\n";
		echo "</div>";
	}	
	?>
	</div>
	<div style="text-align:center; margin-top:30px; ">
		<button id="closebutton" onclick="closeWindow()">Close</button>
	</div>
</body>
</html>
<?	
	//end if there is logs data presents		
	//if there is no data submitted here, processing else cause.
	else:

		echo "There is no data submit to this page. Please go to <a href='review.php'>review page</a> to submit log data.";
	endif;
?>
