<?php
	session_start();
	 
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
	
	
	//start php script
	if(isset($_POST["submit"])) { 
		$ParticipantID = $_POST["ParticipantID-json"];
		$queries = json_decode($_POST["queries-json"],true);

		//sanitize data
		$ParticipantID = preg_replace('/[^a-zA-Z0-9]/', '', $ParticipantID);
		$queries = sanitizeData($queries);
		
		//temporary store data in session
		$_SESSION['ParticipantID'] = $ParticipantID;
		$_SESSION['queries'] = $queries;
	}else{
		die('Direct access is not allowed. Click <a href="review.php">Here</a> to go to review page.');
	}

?>
<heml>
<dead>
<title>Review Step 2</title>
<script src="include/jquery-1.4.4.min.js"></script>
<script src="include/jquery-ui.min.js"></script>
<script src="include/urldecode.js"></script>
<link rel="stylesheet" href="include/jquery-ui.css" id="theme">
<link rel="stylesheet" type="text/css" href="style.css"/>
<style type="text/css">
	.header {
		color:#7777FF;
		font-weight:bold;
		font-size: 30px;
	}

	.remark{
		font-weight:bold;
	}
/*
	.box {
		border-style: dashed;
		border-width: 1px;
		border-color: red;
		background-color: #FFFFDD;
		padding: 15px;
		overflow: auto;
		max-height: 30%;
	}*/

	table{
		table-layout:fixed;
		border: 1px solid black;
		color:black;
		text-align:center;
		width: 100%;
	}

	th{
		background-color:#333333;
		color:white;
	}

	td{ 
		padding: 0px 15px 0px 15px;
		white-space: normal; 
	}

	tr:nth-child(odd){ background-color:#FFFFFF; }
	tr:nth-child(even){ background-color:#DDDDFF; }

/*
	th.opr 	{ width: 75px; }
	th.time	{ width: 100px; }
	th.site	{ width: 150px; }
	th.page { width: 50px; }
	th.index{ width: 50px; }
*/

	td.query, td.title, td.url { text-align: left; }

	.submit {
		font-size: 1.5em;
		width: 30%;
		margin-top: 15px;
		text-align: center;
	}
	
	.textbox{
		width: 100%;
	}
	
</style>
<script>
$(document).ready(function(){
	$('.button').button().css('font-size','0.75em');

function validate(){
	return false;
}
	
/*	$('postlog2').submit(function(){
		var valid=true;
		$('form input:text').each(function(){
			
			alert(JQuery.trim($(this).val());
			if(JQuery.trim($(tbox).val())==''){
				alert("Please fill this information.");
				tbox.focus();
				return false;
			}
		});
		return false;//valid;
	});*/
});
</script>
</head>
<body>
	<div class="header">Review Logs</div>
	<div class="remark">
		*** Data is not fully submitted yet until finish this page ***
	</div>
	<div style="background:#FCC">ParticipantID : <?php echo $_SESSION['ParticipantID']?></div>
	<div><?php echo "Submitting ".count($_SESSION['queries'])." queries."?></div>
	
	<!-- Survey Part -->
	<form name="postlog2" id="postlog2" method="POST" action="submitted.php">
	<div class="remark">
		Please enter the following information for each of your searches.
	</div>
	<table>
		<tr>
			<th>Time</th>
			<th>Site</th>
			<th>Query</th>
			<th>Was this search successful?</th>
			<th>What was the goal of this search?</th>
			<th>Did this search help you to achieve your goal?</th>
			<th>How long did it take you to complete this search?(hh:mm:ss)</th>
		</tr>
<?php
	$qNo = 0;
	//start going through each query
	foreach($queries as $query):
		$timestamp = $query['timestamp'];
		$queryword = str_replace('+',' ',$query['query']);
		//if system use nano sec, convert to micro sec
		if($timestamp>1000000000000) $timestamp/=1000;
	?>
		<tr>
			<td><?php echo date('m/d/Y H:i:s',$timestamp); ?></td>
			<td class="site"><?php echo $query['site'];	 ?></td>
			<td class="query"><?php echo $queryword; ?></td>
			<td><input name="<?php echo "q${qNo}_1"?>" type="checkbox" checked="yes" value="yes"/></td>
			<td><input name="<?php echo "q${qNo}_2"?>" type="text" class="textbox"/></td>
			<td><input name="<?php echo "q${qNo}_3"?>" type="checkbox" checked="yes" value="yes"/></td>
			<td><input name="<?php echo "q${qNo}_4"?>" type="text" class="textbox"/></td>
		</tr>
	<?php 	
		$qNo++;	
	endforeach;
?>	
	</table>
	<center>
	<input type="submit" name="submit_step2" class="button submit" value="Submit" onclick="return validate();"/>
	</center>
	</form>
	
</body>
</html>