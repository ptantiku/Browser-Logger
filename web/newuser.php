<html>
<head>
<meta http-equiv="pragma" content="no-cache">
<style type="text/css">
#oldParticipantID{
	color: red;
	font-width: bold;
	font-style: italic;
}
</style>
<link rel="stylesheet" type="text/css" href="style.css"/>
</head>
<body onload="window.resizeTo(820,700)">
	<center><span class="loggertitle">UCI Web-Search Logger : Login</span><span class="title"></span></center>
	<div class="infodoc">
	<p>We want to thank you for your participation in our research to better understand how developers look for source code on the Web.  </p>
	<p>Your participation is strictly voluntary; you may withdraw at any time, for any reason. All recorded information is confidential and anonymized, and will be used only for the purpose of the research. </p>
	<p>If you have any question contact any of the researchers: Rosalva Gallardo (rgallard@uci.edu), Phitchayaphong Tantikul (ptantiku@uci.edu) and Susan Sim (ses@ics.uci.edu).</p>
	</div>
	<center>
	<div id="resumebox" class="box" style="display:hidden;">
		Your Participant ID is: <span id="oldParticipantID"></span><br><br>
		If you prefer to use this ID and start logging the searches, please click this button.
		<button id="startlogging">Start Logging</button><br>
		** If this is not your ID, enter yours below and click "Submit".
	</div>
	<br>
	<div id="submitbox" class="box">
	To start using this Google Chrome extension, <br> please enter your participant ID here, then click "Submit".
		<center>
		<form id="newuserform" name="newuserform" method="post" action="setParticipantID.php">
			<input id="ParticipantID" name="ParticipantID" type="text" value="">
			<input id="submitbutton" name="submitbutton" type="submit" class="button">
		</form>
		</center>
	</div>
	</center>
</body>
</html>
