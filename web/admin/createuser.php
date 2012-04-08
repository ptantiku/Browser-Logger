<?php
	require "config.php";
	unset($displayresult);

	//if submit data
	if(isset($_POST['submit'])){
		//convert to local vars.
		$fullname = mysql_real_escape_string($_POST['fullname']);
		$email = mysql_real_escape_string($_POST['email']);
		$comment = mysql_real_escape_string($_POST['comment']);
		$company = mysql_real_escape_string($_POST['company']);

		//validation..
		
		//generate ParticipantID (participant ID)
		$randomstr=sha1(rand().":".$email);
		$randomstr=substr($randomstr,0,10);	
		$ParticipantID = mysql_real_escape_string($randomstr);

		//test if this person in database already.
		$check=false;
		$result = mysql_query("SELECT id,participantid FROM ".$CONFIG['db_tb_participants']." WHERE fullname LIKE '".$fullname."' OR email LIKE '".$email."' LIMIT 0,1;");
		if(isset($result) && ($row = mysql_fetch_assoc($result))!=null){
			$check=true;
			$id = $row['id'];
			$ParticipantID = $row['participantid'];
			mysql_free_result($result);

			die ("Already have this user in database. Check ID=".$id." & participantid=".$ParticipantID."<BR>\n Wait 5 seconds to refresh. <script> setTimeout('location.reload(true);',5*1000); </script>");
		} 

		//if this person is new to the system, add this person
		mysql_query("INSERT INTO ".$CONFIG['db_tb_participants']." ".
				"(fullname,email,participantid,comment,company) ".
				"VALUES ('".$fullname."','".$email."','".$ParticipantID."','".$comment."','".$company."');");
		$id = mysql_insert_id();
		$displayresult = true;
	}
	

?>

<html>
<header>
	<title>Creating User Page</title>
<style type="text/css">
#header,#successheader {
	color:#7777FF;
	font-weight:bold;
}

#success {
	border-style: dashed;
	border-color: #FF0000;
	border-width: medium;
}

</style>
</header>
<body>
	<div id="header">Creating New User</div>
	<form name="creatuser" method="POST" action="createuser.php">
		<table>
		<tr><td>Full Name:</td><td><input name="fullname" type="text"></td></tr>
		<tr><td>E-mail Address:</td><td><input name="email" type="text"></td></tr>
		<tr><td>Company Name(lowercase, no spaces):</td><td><input name="company" type="text"></td></tr>
		<tr><td>Comment:</td><td><textarea name="comment"></textarea></td></tr>
		</table>
		<input name="submit" type="submit">
	</form>
<?php
	if(isset($displayresult)) {  //if successful create user, display result
?>
	<div id="success">
		<div id="successheader">Successful Create User</div>
		<table>
		<tr>
			<td>ID</td>
			<td><?=$id?></td>
		</tr>
		<tr>
			<td>Full Name</td>
			<td><?=$fullname?></td>
		</tr>
		<tr>
			<td>E-mail Address</td>
			<td><?=$email?></td>
		</tr>
		<tr>
			<td>Participant ID</td>
			<td><?=$ParticipantID?></td>
		</tr>
		<tr>
			<td>Comment</td>
			<td><?=$comment?></td>
		</tr>
		</table>
	</div>
<?php
	} //end displaying successfully added data
?>
</body>
</html>
