/*
main_inject.js - Javascript code injected to monitoring web pages.
*/

/* ==============================================================================
Toggle Debug Mode
==============================================================================*/

debug = true;
function l(msg){ if(debug) console.log(msg); }

/*==============================================================================
Code to inject to specific pages.
================================================================================
*/

/*
Extract value for a specific parameter from URL location of current page
*/
function getParam(param)
{
    var hashes;
    if(window.location.hash){ 	
    	//if Google uses hash 
    	//ex: http://www.google.com/search?sourceid=chrome&client=ubuntu&channel=cs&ie=UTF-8&q=google+ajax+search+event&complete=0#sclient=psy&hl=en&client=ubuntu&tbo=1&channel=cs&complete=0&tbs=qdr:m&source=hp&q=google+ajax++event&tbo=1&bav=on.2,or.r_gc.r_pw.&fp=67a9068d82e57da5&biw=1280&bih=688
    	hashes = window.location.hash.slice(1).split('&');
    }else{
    	//normal url
    	//ex: http://www.google.com/search?sourceid=chrome&client=ubuntu&channel=cs&ie=UTF-8&q=google+ajax+search+event&complete=0
    	hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    }
    l(hashes);
    for(var i = hashes.length-1; i >= 0 ; i--) {
        hash = hashes[i].split('=');
        if(hash[0] == param) return hash[1];
    }
    return null;
}

//fixed version of window.close()
function closeWindow(){
        window.open('','_self','');  //addition line to fix Chrome
        window.close();
}

/*
Initialize function for logger, parse the domain and execute responsible function.
*/
function initBinds(){
	l("Initialize logger");
	
	//with/without ParticipantID, when user requests our page, this script will be run.
	if(document.domain == "rad.ics.uci.edu" && location.href.match("chromelogger")){
		 monitor = subScript();
		return;
	}

	//show notification that this seach is being logged.
	chrome.extension.sendRequest({
		command: "getParticipantID"
		}, function(response){
			ParticipantID = response;
			addNotification(ParticipantID);

			//stops when no ParticipantID (or user wants to pause)
			if(ParticipantID==null || ParticipantID=="") return;

			//classify logging page
			if(document.domain == "google.com" || document.domain == "www.google.com"){
				monitor = googleScript();
				$(window).bind('hashchange', function() {
					setTimeout(googleScript,1000);//delay 1 sec, wait for ajax loaded
				});
			}
			if(document.domain.match("stackoverflow.com")) monitor = stackoverflowScript();
			if(document.domain == "www.bing.com") monitor = bingScript();
		}
	);		
	
	return;
}

/*
Interacting with controling site. (data collecting site)
Choices of interaction : 
 - Clear Local Storage
 - Set ParticipantID (or New User's ParticipantID)
 - Retrieving data from local storage and populate them in this page.
 - or, on submitted.php check if successful submitted then clear data.
*/
function subScript(){
	l("In one of logger admin sites...");

	//ignore pages in admin section
	// http://rad.ics.uci.edu/chromelogger/admin/*
	if(location.href.match("admin/")) {
		l("ignore admin folder.");
		return;
	}

	//new user login page, only check for previous ID for resuming
	//href=rad.ics.uci.edu/chromelogger/newuser.php
	if(location.href.match("newuser.php")){
		l("new user login page");
		//check previous id
		chrome.extension.sendRequest({command: "getOldParticipantID"}, 
			function(response){
				if(response!=null){
					oldParticipantID = response;
					$("#oldParticipantID").text(oldParticipantID+'');
					$("#resumebox").show();
					$("#startlogging").click(function(){
						$("#ParticipantID").val(oldParticipantID+'');
						$("#newuserform").submit();
					});
				} else {
					$("#resumebox").hide();
				}
			});
		return;
	}
	
	//clear logs on user's machine
	if(location.href.match("clear.php")){
		if(confirm("Do you want to clear all logged data?")){
			chrome.extension.sendRequest({command: "clearStorage"});
			alert("Logs cleared!");
		}
		history.back(1);
		//closeWindow();
		return;
	}

	//get ParticipantID from setParticipantID.php, then set it to this extension.
	if(location.href.match("setParticipantID.php")){
		l("In setParticipantID page");
		$("#status").text("This extension was successfully installed.");
		var ParticipantID = $("#ParticipantID").text();
			
		//if the page have new ParticipantID, 
		//try to override ParticipantID in current localStorage
		if(ParticipantID!="") {	
			chrome.extension.sendRequest({
				command: "setParticipantID",
				ParticipantID: $("#ParticipantID").text()
			}, function(response){
				//if something wrong, goes to login page
				if(response.error){
					alert(response.error);
					location.href="http://rad.ics.uci.edu/chromelogger/newuser.php";
					return;
				}
				l("ParticipantID is set");
				//close this page when successfully set "ParticipantID"
				closeWindow();
			});
			return;
		} else {
			return;
		}
	}
	
	//-------For other pages : current ParticipantID is required.--------//
	var currentParticipantID="";
	//load ParticipantID from the extension
	chrome.extension.sendRequest({
		command: "getParticipantID"
	}, function(response){
		var ParticipantID = response;
		if(ParticipantID==null) {
			alert("You don't have a valid Participant ID, Press OK, then enter Participant ID.");
			location.href="http://rad.ics.uci.edu/chromelogger/newuser.php";
			return;
		} else {
			l("Proceed with ParticipantID="+ParticipantID);
			currentParticipantID = ParticipantID;
			populateParticipantID(ParticipantID);		
			
			//if it's on review page
			if(location.href.match("^http://rad.ics.uci.edu/chromelogger/review.php")){
				l("in review page");
				//get queries&clicks, then print them out
				populateData();
				return;
			}

			//if it's on submitted page
			if(location.href.match("^http://rad.ics.uci.edu/chromelogger/submitted.php")){
				l("in submitted page");
				//fill in start_time and end_time
				chrome.extension.sendRequest({
					command: "getLastSubmit"
				}, function(response){
					l(response);
					$("#start_time").text(formatTime(response));
					$("#end_time").text(formatTime(new Date().getTime()));
				});

				//and if it's successfully insert all data into database
				if($("#submit_result").text().match("^submitted")){
					//update last submission time and clear logs
					chrome.extension.sendRequest({command: "submitComplete"});
					$("#clearlogs").text("Your logs on your computer have been cleared.");
					alert("Thank You!");
					//closeWindow();
				}
				return;
			}
		}
	});
	
	
}

//inject ParticipantID into any html element which has id="ParticipantID"
function populateParticipantID(ParticipantID){
	if(ParticipantID == null)
		$("#ParticipantID").text("Participant Unknown");
	else
		$("#ParticipantID").text(ParticipantID + '');
	
	$("#ParticipantID-json").text((ParticipantID?ParticipantID:0)+'');
}

//padding zero for time display
function zeroPad(num){
	return String('00'+num).slice(-2);
}

//convert timestamp(milliseconds from 1/1/1970) to human readable date string
function formatTime(timestamp){
	var dt = new Date(parseInt(timestamp));
	var datestr = zeroPad(dt.getMonth()+1) + "/" + zeroPad(dt.getDate()) + "/" + dt.getFullYear();
  	    datestr += " " + zeroPad(dt.getHours()) + ":" + zeroPad(dt.getMinutes()) + ":" + zeroPad(dt.getSeconds());
	return datestr;
}

//function to populate both Queries and Clicks into controlbox
function populateData(){
	l("Populate Data...");
	chrome.extension.sendRequest({
		command: "getQueries"
	}, function(response){
		populateQueries(response);	
	});
}

//Convert queries json format into jstree format
/*
"data" : [
		{ 
			"data" : "A node", 
			"children" : [ "Child 1", "Child 2" ]
		},
		{ 
			"attr" : { "id" : "li.node.id" }, 
			"data" : { 
				"title" : "Long format demo", 
				"attr" : { "href" : "#" } 
			} 
		}
	]
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
						title: queryclick.title,
						button: queryclick.button
					}			
				};
				query.children.push(click);	//push click in to query's children
			}
		}
		//push each query in Data
		data.push(query);
	}

	return data;
}

// Remove Log after delete it from JSTree
// only used in review page
function removeLogFromJSTree(id){
	l("Deleting ID : "+id);
	if(!id) return;
	
	//check whether it's query or click
	// query's format:  q123456789
	// click's format:  q123456789_c123456789
	idArr = id.split('_');
	if(idArr.length==1){	//it is query
		var querytimestamp = id.substr(1);
		chrome.extension.sendRequest({
			command: "deleteQuery",
			timestamp: querytimestamp   //timestamp of the query to be deleted.
			}, function(response){
				//if delete completed
				if(response){	
					//update Query
					l("re-populate queries");
					var newqueries = response;
					populateQueries(newqueries);	
				}
			});
	}else{	//it is click
		var querytimestamp = idArr[0].substr(1);
		var clicktimestamp = idArr[1].substr(1);
		chrome.extension.sendRequest({
			command: "deleteClick",
			querytimestamp: querytimestamp, // timestamp of the query to be deleted.
			timestamp: clicktimestamp	// click's timestamp
			}, function(response){
				//if delete completed
				if(response){	
					//update Query
					l("re-populate queries");	
					var newqueries = response;
					populateQueries(newqueries);	
				}
			});
	}
}

//inject queries into any html element which has id="queries-json"/"queries"
function populateQueries(queries){
	l("Populate Queries..");
	if(!queries) return;

	//show JSON type in #queries-json
	$("#queries-json").text(JSON.stringify(queries));

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
					icon:{ "image" : chrome.extension.getURL('js/include/jstree/images/module.png') }
				},
				"click":{
					valid_children:[ ],
					icon:{ "image" : chrome.extension.getURL('js/include/jstree/images/page.png') }
				}
			}
		},
		ui:{
			select_limit: 1,	//unlimited selection
			selected_parent_close: "select_parent",	//if it's closed parent, select it
			select_prev_on_delete: false,	//if deleted, select previous node
			disable_selecting_children: false		//disable select children of select node
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
		checkbox:{
			override_ui: true
		},
		plugins: [ "themes" , "json_data" , "cookies" , "types" , "ui" , "checkbox" , "contextmenu" , "crrm" , "search" ]
	};
	//set themes folder
	jQuery.jstree._themes = chrome.extension.getURL('js/include/jstree/themes/');

	$("#queries").jstree(tree_json)
			.bind("delete_node.jstree", function(e, data) {
				l("Start deleting nodes");
				for(var i=0;i<data.rslt.obj.length;i++){
					var obj = data.rslt.obj[i];
					l("Found : "+$(obj).attr("id")+" : "+$(obj).attr("title"));
					removeLogFromJSTree($(obj).attr("id"));
				}
				//e.stopPropagation();
				//return false;
			});

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
		
	$("#removebutton").click(function(){
		l("Remove Button Clicked.");
		$("#queries").jstree("remove");	
		//event.stopPropagation();
		//return false;
	});

}

/**
   Log a search query into localStorage
*/
function logQuery(site, query, sendResponse){
	if(query == "") return;
	l("Logging query: " + query);

	var timestamp = new Date().getTime();
	chrome.extension.sendRequest({
		command: "logQuery",
		timestamp: timestamp,
		site: site,
		query: query
	}, function(response){
		l("Query logged.");
		sendResponse(response);  //return timestamp as query ID
	});

	//if it's on review page
	if(location.href.match("^http://rad.ics.uci.edu/chromelogger/review.php")){
		l("populate new data, if in review page");
		//get queries&clicks, then print them out
		populateData();
	}
}

/**
   Log a click on search result into localStorage
*/
function logClick(querytimestamp, page, index, title, url, button){
	l("Logging click: " + querytimestamp + "," + page + "," + index + "," + title + "," + url + "," + button);
	var timestamp = new Date().getTime();

	chrome.extension.sendRequest({
		command: "logClick",
		timestamp: timestamp,
		querytimestamp: querytimestamp,
		page: page,
		index: index,
		title: title,
		url: url,
		button: button
	});
	
	l("Click logged. Directing to url...");
	//location.href = url;   //force to open one link at a time
}

/**
   Google Search Handler
   - disable instant search, by redirect it back to normal URL
   - capture when "q" parameter presents.
*/
function googleScript(){
	l("Google Search monitoring script installed.");
	l("location.href="+location.href);
	l("Disabling Instant Search (if present)");
	if((location.href.match("/search?") || location.href.match("/webhp?")) && !location.href.match("complete"))
		return (location.href = location.href + "&complete=0");

	//--When no query presence, disable instant search and stop processing.--//
	if(location.href == "http://google.com/" || location.href == "http://www.google.com/")
		return (location.href = location.href + "webhp?complete=0");

	/*----If there's query, start processing----*/
	var query = getParam("q");
	var page = getParam("start");
	page = (page)?(page/10)+1:1;
	l("extract q="+query+" & page="+page);	
	if(query) {
		//Log query
		logQuery("Google", query, function(querytimestamp){
			//get querytimestamp from background.html
			//whether it's previous timestamp or current timestamp
			l("logQuery@"+querytimestamp);
			//Inject Code to each search result
			$("a.l").each(function(index){
				//show all links on this page (index starts at 1)
				l((index+1)+":"+$(this).text());

				//add click handler to log result when user click
				$(this).bind('mousedown',function(event){
					//Log click
					logClick(querytimestamp, page, index+1, $(this).text(), this.href, event.button);
					l('click logged: with button='+event.button);
					return true;
				});
				
				//disable showing context menu
				/*$(this).live('contextmenu',function(event){
					logClick(querytimestamp, page, index+1, $(this).text(), this.href);
					return true;
				});*/
			});	
		});	
	}

}

/**
  StackOverflow Handler
  - capture only when search query or tagged possible in the URL
  - no capturing for page that lists of all questions without query/tag
*/
function stackoverflowScript(){
	l("Stackoverflow monitoring script installed");
	var query;
	var page;
	
	if(location.href.match("/search?")){
		//possible url : http://stackoverflow.com/search?page=3&tab=relevance&q=jquery%20onfocus
		query = getParam("q");
		page = getParam("page");
	} else if(location.href.match("/questions/tagged/")){
		//possible url : http://stackoverflow.com/questions/tagged/java?page=4&sort=newest&pagesize=15
		query = location.href.substring(location.href.indexOf("/tagged/")+8);
		if(query.indexOf("?")>=0) 
			query = query.substring(0,query.indexOf("?"));

		page = getParam("page");
	} else	//no matching URL
		return;
	
	//if query presents, inject script to capture clicks
	if(query){
		page=page?page:1;
		l("Query="+query+"\nPage="+page);
		
		//Log query
		logQuery("Stackoverflow", query, function(querytimestamp){
			//Inject code to each search result
			$("a.question-hyperlink").each(function(index){
				//show all links on this page (index starts at 1)
				l((index+1)+":"+$(this).text());
				//add click handler to log result when user click
				$(this).bind('mousedown',function(event){
					//Log click
					logClick(querytimestamp, page, index+1, $(this).text(), this.href, event.button);
					return true;
				});
				
				//disable showing context menu
				/*$(this).live('contextmenu',function(event){
					logClick(querytimestamp, page, index+1, $(this).text(), this.href);
					return true;
				});*/
			});	
		});
	}
	return;
}

/**
   Bing Handler
   - capture bing searches
*/
function bingScript(){
	l("Bing monitoring script installed.");

	//if no searching in progress.
	if(!(location.href.match("/search?")))
		return;
	
	/*----If there's query, start processing----*/
	var query = getParam("q");
	var page = getParam("first");
	page = (page)?Math.floor((page/10)+1):1;
	l("extract q="+query+" & page="+page);	
	if(query) {
		//Log query
		queryID = logQuery("Bing", query, page);
		//Log query
		logQuery("Bing", query, function(querytimestamp){
			//Inject Code to each search result
			$(".sb_tlst>h3>a").each(function(index){
				//show all links on this page (index starts at 1)
				l((index+1)+":"+$(this).text());
				//add click handler to log result when user click
				$(this).bind('mousedown',function(event){
					//Log click
					logClick(querytimestamp, page, index+1, $(this).text(), this.href, event.button);
					return true;
				});
				
				//disable showing context menu
				/*$(this).live('contextmenu',function(event){
					logClick(querytimestamp, page, index+1, $(this).text(), this.href);
					return true;
				});*/
			});
		});
	}

}

/**
   Show small notification on top-right of the page to indicate logger's status
*/
function addNotification(ParticipantID){
	l("Adding logger notification");
	var status="";
	if(ParticipantID) 
		status="Logger is currently monitoring this search";
	else 
		status="Logger is not running. To enable logger, click on the logger's icon above then click \"Start Logging\".";

	var code = "<div id='loggernotification' style='color:#000000; background-color:#FFFFCC; font-size: 12px; float:right; position:fixed; top:20px; right:10px; z-index:1001; padding:5px;'>"+status+"</div>";
	$("body").append(code);
}


/**** Start all these scripts *****/
$(document).ready(function(){
	initBinds();
});

