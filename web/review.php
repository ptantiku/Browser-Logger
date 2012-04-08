<?php
	session_start(); 
	
	//reset session's storage if it has one
	if(isset($_SESSION['ParticipantID'])||
		isset($_SESSION['queries'])){
		
		unset($_SESSION['ParticipantID']);
		unset($_SESSION['queries']);
	}
	
?>
<html>
<header>
	<title>Review Logs</title>
<script src="include/jquery-1.4.4.min.js"></script>
<script src="include/jquery.cookie.js"></script>
<script src="include/jquery.jstree.js"></script>
<script src="include/jquery-ui.min.js"></script>
<script src="include/urldecode.js"></script>
<script type="text/javascript">

	debug = true;
	function l(msg){ if(debug) console.log(msg); }

//padding zero for time display
function zeroPad(num){
	return String('00'+num).slice(-2);
}

//convert timestamp(milliseconds from 1/1/1970) to human readable date string
function formatTime(timestamp){
	var dt = new Date(parseInt(timestamp));
	var datestr = zeroPad(dt.getMonth()+1) + "/" 
						+ zeroPad(dt.getDate()) + "/" 
						+ dt.getFullYear();
  	datestr += " " + zeroPad(dt.getHours()) + ":" 
  	    				+ zeroPad(dt.getMinutes()) + ":"
  	    				+ zeroPad(dt.getSeconds());
	return datestr;
}

/**Convert queries json format into jstree format
	"data" : [{ 
				"data" : "A node", 
				"children" : [ "Child 1", "Child 2" ]	
			},{ 
				"attr" : { "id" : "li.node.id" }, 
				"data" : { 
					"title" : "Long format demo", 
					"attr" : { "href" : "#" } 
				}}]
	Example : https://github.com/tatemae/oerglue_chrome/tree/master/js/utils
*/
function convertQueryToJSTree(queries){
	data = [];
	for(var i=0;i<queries.length;i++){
		//generate query object
		var query = {
			//query title
			//in format of
			//time [Google] Search Query
			data:formatTime(queries[i].timestamp)+" ["+queries[i].site+"] "+Url.decode(queries[i].query).replace(/\+/gi, " "),
			//node attributes
			attr:{
				id:"Q"+queries[i].timestamp,
				rel:"query"
			},
			//default states
			state:"closed",
			//clicks (..blank for now..)
			children:[]
		};
		
		//generate click objects
		if(queries[i].clicks){	//if has clicks
			for(var j=0;j<queries[i].clicks.length;j++){
				queryclick = queries[i].clicks[j];
				click = {
					//click title
					// in format of 
					// time@page1#3:Search Query[http://example.com]
					data:queryclick.title,
					//attribute
					attr:{
						id:"Q"+queries[i].timestamp+"_C"+queryclick.timestamp,
						rel: "click",
						url: queryclick.url,
						timestamp: queryclick.timestamp,
						page: queryclick.page,
						index: queryclick.index,
						title: queryclick.title
					}			
				};
				query.children.push(click);	//push click in to query's children
			}
		}
		//push each query in Data
		data.push(query);
	}

	return data;
};


function initializeJSTree(removeNode_callbackfunction){
	jQuery.noConflict();
	var $ = function(selector,context){ return new jQuery.fn.init(selector,context||window._content.document); };
	var queries = JSON.parse($("#queries-json").text());

	var tree_json = {
				core:{
					html_titles: true  //allows Title to contain HTML
				},
				json_data:{
					data: convertQueryToJSTree(queries)
				},
				themes:{
					theme : "default",
					dots : true,
					icons : true
				},
				types:{
					types:{
						"query":{
							valid_children:[ "click" ],
							icon:{ "image" :  "include/jstree/images/module.png" }
						},
						"click":{
							valid_children:[ ],
							icon:{ "image" :  "include/jstree/images/page.png" }
						}
					}
				},
				ui:{
					select_limit: 1,	//unlimited selection
					selected_parent_close: "select_parent",	//if it's closed parent, select it
					select_prev_on_delete: false,	//if deleted, select previous node
					disable_selecting_children: false 	//disable select children of select node
				},
				contextmenu:{
					select_node:true,
					items:function(node,treeobj) {
						var menu={};
						//disable by-default menu
						menu["create"] = false;
						menu["ccp"] = false;
						menu["rename"] = false;
						//only "click"-type node is allowed to have this menu
						if("click"==node.attr("rel")) {
							menu["gourl"] = {
								separator_before: false,
								icon: false,
								separator_after: false,
								label: "Go to this page",
								
								action:function(obj) { window.open(obj.attr("url"),"popuphistoryweb"); }
							};
						}
						//remove menu
						menu["remove"] = {
							separator_before: true,
							icon: false,
							separator_after: false,
							label: "Remove",
							action:function (obj) { this.remove(obj); }
						};
						return menu;
					}
				},
				search:{
					case_insensitive:true
				},
				plugins: [ "themes" , "json_data" , "cookies" , "types" , "ui" , "checkbox" , "contextmenu" , "crrm" , "search" ]
			};
			//set themes folder
			jQuery.jstree._themes = "include/jstree/themes/";
		
			$("#queries").jstree(tree_json);

			$("#searchbutton").click(function(event){
				l("start search with keyword : " + $("#searchtext").val());
				$("#queries").jstree("search",$("#searchtext").val(),false);
				return false;
			});
		
			$("#clearsearchbutton").click(function(event){
				l("clear search");
				$("#queries").jstree("clear_search");
				$("#searchtext").val('');
				return false;
			});
				
			$("#removebutton").click(function(event){
				$("#queries").jstree("remove");	
				event.stopPropagation();
				return false;
			});
			
			$("#queries").bind("delete_node.jstree", function(e, data) {
				l("Start deleting nodes");
				for(var i=0;i<data.rslt.obj.length;i++){
					var obj = data.rslt.obj[i];
					l("Found : "+$(obj).attr("id")+" : "+$(obj).attr("title"));
					alert("Found : "+$(obj).attr("id")+" : "+$(obj).attr("title"));
					removeNode_callbackfunction($(obj).attr("id"));	//call callback function
				}
				e.stopPropagation();
				return false;
			});
			
}

//use Jquery-UI for every button, runs at page loaded
$(document).ready(function(){
	$('.button').button().css('font-size','0.75em');
});
</script>
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

	td{ padding: 0px 15px 0px 15px; }

	tr:nth-child(odd){ background-color:#FFFFFF; }
	tr:nth-child(even){ background-color:#DDDDFF; }

	th.opr 	{ width: 75px; }
	th.time	{ width: 100px; }
	th.site	{ width: 150px; }
	th.page { width: 50px; }
	th.index{ width: 50px; }

	td.query, td.title, td.url { text-align: left; }

	textarea{ width: 100%; }

	#clearall{
		width: 20%;
		font-size: 1.5em;
		float: left;
		margin-top: 15px;
		background: red;
	}

	#submit {
		font-size: 1.5em;
		width: 50%;
		margin-top: 15px;
		float: right;
		background: #cfc;
	}
</style>
<link rel="stylesheet" type="text/css" href="style.css"/>
<link rel="stylesheet" href="include/jquery-ui.css" id="theme">
</header>
<body>
	<div class="header">Review Logs</div>
	<div class="remark">
		*** Data displayed in this page is rendered from your machine. None of these data submitted to us yet. *** 
	</div>
	<br/>
	In this page, you can select the searches that you want to submit to the researchers.<br/>
	<br>
	<!--Participant ID-->	
	Participant ID : 
	<span id="ParticipantID" style="padding:5px;">
		<?=isset($ParticipantID)?$ParticipantID:'' ?>
	</span>
	<br>

	<!--Showing Logged Data-->
	Queries:<br>
	<div id="searchbox" style="width:350px;background-color:white;" >
		<input id="searchtext" type="text">
		<button id="searchbutton">Search</button>
		<button id="clearsearchbutton">Clear Search</button>
	</div>

	<!-- Queries in Tree -->
	<div id="queries" class="box" style="width:98%;background-color: #FFFFEE;padding:10px;border-style:solid;border-width:1px;"></div>

	<!-- Operation buttons -->
	<div id="operationbox">
		<button id="removebutton">Remove Selected Log Entries</button>
	</div>

	<div>Warning: any delete operation will be done instantly and there is no undo.<br>If you prefer to do nothing with data, you can simply close this page.</div>
	<button id="clearall" class="button" onclick="document.location.href='clear.php';return false;">Clear All Data</button>
	<form name="postlog" method="post" action="review2.php">
	<!-- Hidden Data -->
	<div class="hidden">
		Participant ID json:<a href="#" onclick="$('#ParticipantID-json').slideToggle(500);return false;">[Toggle]</a><br>
		<textarea id="ParticipantID-json" name="ParticipantID-json" class="box" readonly="true"></textarea>
		queries-json:<a href="#" onclick="$('#queries-json').slideToggle(500);return false;">[Toggle]</a><br>
		<textarea id="queries-json" name="queries-json" class="box" readonly="true" ></textarea>
	</div>
	<input id="submit" name="submit" type="submit" class="button" value="Next Step" >
	</form>
</body>
</html>
