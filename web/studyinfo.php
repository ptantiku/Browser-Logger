<?
//pre-defined html header & footer
$HTML_HEADER=<<<HTML_HEADER
<html>
<meta http-equiv="Pragma" content="no-cache">

<head>
	<title>Study Information Sheet</title>
</head>
<body>
	<script>
		function closeWindow(){
			window.open('','_self','');  //addition line to fix Chrome
			window.close();
		}
	</script>
HTML_HEADER;

$HTML_FOOTER=<<<HTML_FOOTER
	<center><button onclick="closeWindow()">Close</button></center>
</body>
</html>
HTML_FOOTER;

	//start processing
	require "admin/config.php";
	unset($ParticipantID);
	

	if(isset($_GET['ParticipantID']) && $_GET['ParticipantID']!=''):
		$ParticipantID=$_GET['ParticipantID'];
		//remove other character than alpha-num
		$ParticipantID=preg_replace('/[^a-zA-Z0-9]/', '', $ParticipantID);
		$ParticipantID=stripslashes($ParticipantID);
		$ParticipantID=mysql_real_escape_string($ParticipantID);
		//only allows 10 chars
		$ParticipantID=substr($ParticipantID,0,10);

		//retrieve participant info
		$result = mysql_query("SELECT id,company FROM ".$CONFIG['db_tb_participants']." WHERE ParticipantID='".$ParticipantID."' LIMIT 0,1;");
		if($result && ($row=mysql_fetch_assoc($result))!=null):
			$id = $row['id'];
			/*$fullname = $row['fullname'];
			$email = $row['email'];
			$ParticipantID = $row['participantid'];
			$comment = $row['comment'];*/
			$company = $row['company'];
			//$readstudyinfo=$row['readstudyinfo'];

			mysql_free_result($result);
		
			//start printing the page
			$referer = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'';
			//if the refered from setParticipantID.php , no need to print header & footer
			// vise versa
			if(!preg_match("/setParticipantID.php/i",$referer))
				echo $HTML_HEADER;

			//display study info sheet
			require "studyinfo/studyinfo_".(isset($company)?$company:'').".html"; 

			//check referer again for printing footer
			if(!preg_match("/setParticipantID.php/i",$referer))
				echo $HTML_FOOTER;

		endif; //end if has returned result from server
	endif;	//end if has $_GET['ParticipantID']
	return;	//end 
?>
