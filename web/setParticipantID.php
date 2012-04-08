<?php
	require "admin/config.php";

	unset($ParticipantID);

	if(isset($_POST['ParticipantID']) && $_POST['ParticipantID']!=''){
		$ParticipantID=$_POST['ParticipantID'];
		//remove other character than alpha-num
		$ParticipantID=preg_replace('/[^a-zA-Z0-9]/', '', $ParticipantID);
		$ParticipantID=stripslashes($ParticipantID);
		$ParticipantID=mysql_real_escape_string($ParticipantID);
		//only allows 10 chars
		$ParticipantID=substr($ParticipantID,0,10);

		//if just read disclaimer, update db
		if(isset($_POST['readstudyinfo'])){
			mysql_query("UPDATE ".$CONFIG['db_tb_participants']." SET readstudyinfo=1 WHERE ParticipantID='".$ParticipantID."';");
		}


		//retrieve participant info
		$result = mysql_query("SELECT id,readstudyinfo FROM ".$CONFIG['db_tb_participants']." WHERE ParticipantID='".$ParticipantID."' LIMIT 0,1;");
		if($result && ($row=mysql_fetch_assoc($result))!=null){
			$id = $row['id'];
			/*$fullname = $row['fullname'];
			$email = $row['email'];
			$ParticipantID = $row['participantid'];
			$comment = $row['comment'];
			$company = $row['company'];*/
			$readstudyinfo=$row['readstudyinfo'];

			mysql_free_result($result);
			//successful retrieve participant info
		} else {
			//if no data on this user
			die ("We have no information associate with this Participant ID. Please make sure you enter the correct ParticipantID. <BR>\n Wait 5 seconds to refresh. <script>setTimeout('location.href=\'newuser.php\'',5*1000);</script>");
		}
	}
?>
<html>
<meta http-equiv="Pragma" content="no-cache">
<script>
function closeWindow(){
	window.open('','_self','');  //addition line to fix Chrome
	window.close();
}
</script>

<? //for people haven't read the study info sheet
	if(!isset($readstudyinfo) || !$readstudyinfo): 
?>
<head>
	<title>Study Information Sheet</title>
</head>
<body>
	<form name="agreement" method="POST" action="#">
		<? 	//display study info sheet
			require "studyinfo/studyinfo_".(isset($company)?$company:'').".html";
		?>
		<input type="hidden" name="ParticipantID" value="<?=isset($ParticipantID)?$ParticipantID:'' ?>">
		<input type="submit" name="readstudyinfo" value="Agree">
		<button id="close" onclick="closeWindow();">Close</button>
</body>
	</form>
</body>
<? //for people have already read the study info sheet
	else: 
?>
<head>
	<title>Setting Participant ID</title>
</head>
<body>
	<div id="welcomebox">
		Welcome, 
	</div>
		Your Participant ID : <span id="ParticipantID"><?=isset($ParticipantID)?$ParticipantID:'' ?></span>
	<div id="status">Status</div>
	
	<button id="close" onclick="closeWindow();">Close</button>
</body>
<? endif; ?>
</html>
