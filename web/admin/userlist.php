<?php
	require "config.php";
?>
<html>
<header>
	<title>list participants</title>
	<style type="text/css">
		#header,#successheader {
			color:#7777FF;
			font-weight:bold;
		}

		table{
			border: 1px solid black;
			color:black;
		}
	
		th{ 
			background-color:#333333;
			color:white;
		}

		td{ padding: 0px 15px 0px 15px; }
		
		tr:nth-child(odd){ background-color:#FFFFFF; }
		tr:nth-child(even){ background-color:#DDDDFF; }
	</style>
</header>
<body>
	<div id="header">List of Participants</div>
	<div id="data">
<?php
	$sql = "SELECT * FROM ".$CONFIG['db_tb_participants'].";";
	$result = mysql_query($sql);
	if ($result){
		echo <<<TABLEHEAD
		<table>
		<thead>
			<tr>
			<th>ID</th>
			<th>Full Name</th>
			<th>E-mail Address</th>
			<th>Participant ID</th>
			<th>Comment</th>
			<th>See Logs</th>
			<th>Download Logs(.csv)</th>
			</tr>
		</thead>
		<tbody>
TABLEHEAD;
		while(($row = mysql_fetch_assoc($result))!=null){
			$id = $row['id'];
			$fullname = $row['fullname'];
			$email = $row['email'];
			$ParticipantID = $row['participantid'];
			$comment = $row['comment'];
			echo "\t\t\t<tr>";
			echo "<td>".$id."</td>";
			echo "<td>".$fullname."</td>";
			echo "<td>".$email."</td>";
			echo "<td>".$ParticipantID."</td>";
			echo "<td>".$comment."</td>";
			echo "<td><a href='listlog.php?ParticipantID=".$ParticipantID."'>see logs</a></td>";
			echo "<td><a href='downloadlog.php?ParticipantID=".$ParticipantID."'>download</a></td>";
			echo "</tr>\n";
		}	
		echo <<<TABLETAIL
		</tbody>
		</table>
TABLETAIL;
		mysql_free_result($result);
	} else {
		echo "Cannot perform sql query.";
	}
?>	
	</div>
</body>
</html>
