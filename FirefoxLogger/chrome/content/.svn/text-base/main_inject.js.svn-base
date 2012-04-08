/*
main_inject.js - Javascript code injected to monitoring web pages.
*/

/* ==============================================================================
Toggle Debug Mode
==============================================================================*/

function runInject(e){
	debug = false;
	function l(msg){ if(debug) alert(msg); }
	
	jQuery.noConflict();
	var $;
	var doc;
	var view;
	var document;
	var ParticipantID;
	var monitor;
	
	/**
	 * fixed version of window.close()
	 */
	function closeWindow(){
	        //window.open('','_self','');  //addition line to fix Chrome
	        //window.close();
			gBrowser.removeCurrentTab();  //Firefox's close-tab function
	}
	
	/**
	 * 	Extract value for a specific parameter from URL location of current page
	*/
	function getParam(param)	{
	    var hash;
	    var hashes = doc.location.href.slice(doc.location.href.indexOf('?') + 1).split('&');
	    for(var i = 0; i < hashes.length; i++) {
	        hash = hashes[i].split('=');
	        if(hash[0] == param) return hash[1];
	    }
	    return null;
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
	
	/**==============================================================================
	Code to inject to specific pages.
	================================================================================
	*/
	var UCIWebSearchLoggerInject = {
		/**
		 * Initialize function for logger, parse the domain and execute responsible function.
		*/
		initBinds:function(){
			l("Initialize logger");

			//if it's not a web page, ignore
			if(doc.domain==null)
				return;
			
			//with or without ParticipantID, when user requests our page, this script will be run.
			if(doc.domain == "rad.ics.uci.edu" && doc.location.href.match("chromelogger")){
				 monitor = UCIWebSearchLoggerInject.subScript();
				return;
			}

			//show notification that this seach is being logged.
			UCIWebSearchLoggerBackground.sendRequest({
				command: "getParticipantID"
				}, function(response){
					ParticipantID = response;
						
					//stops when no ParticipantID (or user wants to pause)
					if(ParticipantID==null || ParticipantID=="") return;
										
					//classify logging page
					if(doc.domain == "google.com") 			monitor = UCIWebSearchLoggerInject.googleScript();
					if(doc.domain == "www.google.com") 	monitor = UCIWebSearchLoggerInject.googleScript();
					if(doc.domain.match("stackoverflow.com")) monitor = UCIWebSearchLoggerInject.stackoverflowScript();
					if(doc.domain == "www.bing.com") 		monitor = UCIWebSearchLoggerInject.bingScript();
				}
			);		
		},
		/**
		Interacting with controling site. (data collecting site)
		Choices of interaction : 
		 - Clear Local Storage
		 - Set ParticipantID (or New User's ParticipantID)
		 - Retrieving data from local storage and populate them in this page.
		 - or, on submitted.php check if successful submitted then clear data.
		*/
		subScript:function(){
			l("In one of logger admin sites...");
			//ignore pages in admin section
			// http://rad.ics.uci.edu/chromelogger/admin/*
			if(doc.location.href.match("admin/")) {
				l("ignore admin folder.");
				return;
			}
		
			//new user login page, only check for previous ID for resuming
			//href=rad.ics.uci.edu/chromelogger/newuser.php
			if(doc.location.href.match("newuser.php")){
				l("new user login page");
				//check previous id
				UCIWebSearchLoggerBackground.sendRequest({command: "getOldParticipantID"}, 
					function(response){
						if(response!=null){
							l("has previous participant id");
							oldParticipantID = response;
							$("#oldParticipantID").text(oldParticipantID+'');
							$("#resumebox").show();
							$("#startlogging").click(function(){
								$("#ParticipantID").val(oldParticipantID+'');
								doc.forms[0].submit();	//submit the form
							});
						} else {
							l("no previous participant id");
							$("#resumebox").hide();
						}
					});
				return;
			}
			
			//clear logs on user's machine
			if(doc.location.href.match("clear.php")){
				l("in clear page");
				if(confirm("Do you want to clear all logged data?")){
					UCIWebSearchLoggerBackground.sendRequest({command: "clearStorage"});
					alert("Logs cleared!");
				}
				history.back();
				//closeWindow();
				return;
			}
			
			//about page
			if(doc.location.href.match("about.php$")){
				//do nothing for about page
				return;
			}
			
			//studyinfo page
			if(doc.location.href.match("studyinfo.php")){
				//do nothing for studyinfo page
				return;
			}

		
			//get ParticipantID from setParticipantID.php, then set it to this extension.
			if(doc.location.href.match("setParticipantID.php")){
				l("In setParticipantID page");
				$("#status").text("This extension was successfully installed.");
				ParticipantID = $("#ParticipantID").text();
					
				//if the page have new ParticipantID, 
				//try to override ParticipantID in current localStorage
				if(ParticipantID!="") {	
					UCIWebSearchLoggerBackground.sendRequest({
						command: "setParticipantID",
						ParticipantID: $("#ParticipantID").text()
					}, function(response){
						//if something wrong, goes to login page
						if(response.error){
							doc.location.href="http://rad.ics.uci.edu/chromelogger/newuser.php";
							return;
						}
						l("ParticipantID is set");
						alert("ParticipantID is set!");
						UCIWebSearchLoggerMenu.updateMenu();	//update toolbar popup menu
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
			UCIWebSearchLoggerBackground.sendRequest({
				command: "getParticipantID"
			}, function(response){
				ParticipantID = response;
				if(ParticipantID==null) {
					alert("You don't have a valid Participant ID, Press OK, then enter Participant ID.");
					doc.location.href="http://rad.ics.uci.edu/chromelogger/newuser.php";
					return;
				} else {
					l("Proceed with ParticipantID="+ParticipantID);
					currentParticipantID = ParticipantID;
					UCIWebSearchLoggerInject.populateParticipantID(ParticipantID);		
					
					//if it's on review page
					if(doc.location.href.match("^http://rad.ics.uci.edu/chromelogger/review.php")){
						l("in review page");
						//get queries&clicks, then print them out
						UCIWebSearchLoggerInject.populateData();
						UCIWebSearchLoggerInject.initializeJSTree();
						//view.onDataChange();
						return;
					}
		
					//if it's on submitted page
					if(doc.location.href.match("^http://rad.ics.uci.edu/chromelogger/submitted.php")){
						l("in submitted page");
						//fill in start_time and end_time
						UCIWebSearchLoggerBackground.sendRequest({
							command: "getLastSubmit"
						}, function(response){
							l(response);
							$("#start_time").text(formatTime(response));
							$("#end_time").text(formatTime(new Date().getTime()));
						});
		
						//and if it's successfully insert all data into database
						if($("#submit_result").text().match("^submitted")){
							//update last submission time and clear logs
							UCIWebSearchLoggerBackground.sendRequest({command: "submitComplete"});
							$("#clearlogs").text("Your logs on your computer have been cleared.");
							alert("Thank You!");
							//closeWindow();
						}
						return;
					}
				}
			});
			
		},
		
		//inject ParticipantID into any html element which has id="ParticipantID"
		populateParticipantID:function(ParticipantID){
			l("populating participantID into current page");
			if(ParticipantID == null)
				$("#ParticipantID").text("Participant Unknown");
			else
				$("#ParticipantID").text(ParticipantID + '');
			
			$("#ParticipantID-json").text((ParticipantID?ParticipantID:0)+'');
		},
	
		//function to populate both Queries and Clicks into controlbox
		populateData:function(){
			l("Populate Data into current page...");
			UCIWebSearchLoggerBackground.sendRequest({
				command: "getQueries"
			}, function(response){
				UCIWebSearchLoggerInject.populateQueries(response);	
			});
		},
		
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
		convertQueryToJSTree:function(queries){
			l("converting queries into JSTree format for display");
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
		},
		
		/**
		 *  Remove Log after delete it from JSTree
		 *  only used in review page
		 */
		removeLogFromJSTree:function(id){
			l("Deleting ID : "+id);
			if(!id) return;
			
			//check whether it's query or click
			// query's format:  q123456789
			// click's format:  q123456789_c123456789
			idArr = id.split('_');
			if(idArr.length==1){	//it is query
				var querytimestamp = id.substr(1);
				UCIWebSearchLoggerBackground.sendRequest({
					command: "deleteQuery",
					timestamp: querytimestamp   //timestamp of the query to be deleted.
					}, function(response){
						//if delete completed
						if(response){	
							//update Query
							l("re-populate queries");
							var newqueries = response;
							UCIWebSearchLoggerInject.populateQueries(newqueries);	
						}
					});
			}else{	//it is click
				var querytimestamp = idArr[0].substr(1);
				var clicktimestamp = idArr[1].substr(1);
				UCIWebSearchLoggerBackground.sendRequest({
					command: "deleteClick",
					querytimestamp: querytimestamp, // timestamp of the query to be deleted.
					timestamp: clicktimestamp	// click's timestamp
					}, function(response){
						//if delete completed
						if(response){	
							//update Query
							l("re-populate queries");	
							var newqueries = response;
							UCIWebSearchLoggerInject.populateQueries(newqueries);	
						}
					});
			}
		},
		
		/**inject queries into any html element 
		 * which has id="queries-json"/"queries"
		 */
		populateQueries:function(queries){
			l("Populate Queries..");
			if(!queries) return;
		
			//show JSON type in #queries-json
			$("#queries-json").text(JSON.stringify(queries));
		},
		
		/**
		 * Initialize jsTree library on review.php page
		 */
		initializeJSTree:function(){
			l("Initializing JSTree");
			//initialization of JSTree will actually be done by javascript on the page
			//here is trigger the initialization (initializeJSTree)
			//and passing a callback function for events when removing log from JSTree
			doc.defaultView.wrappedJSObject.initializeJSTree(UCIWebSearchLoggerInject.removeLogFromJSTree);
		},
		
		/**
		   Log a search query into localStorage
		*/
		logQuery:function(site, query, sendResponse){
			if(query == "") return;
			l("Logging query: " + query);
			var timestamp = new Date().getTime();
			UCIWebSearchLoggerBackground.sendRequest({
				command: "logQuery",
				timestamp: timestamp,
				site: site,
				query: query
			}, function(response){
				l("Query logged.");
				sendResponse(response);  //return timestamp as query ID
			});
		
			//if it's on review page
			if(doc.location.href.match("^http://rad.ics.uci.edu/chromelogger/review.php")){
				l("populate new data, if in review page");
				//get queries&clicks, then print them out
				UCIWebSearchLoggerInject.populateData();
			}
		},
		 
		/**
		   Log a click on search result into localStorage
		*/
		logClick:function(querytimestamp, page, index, title, url){
			l("Logging click: " + querytimestamp + "," + page + "," + index + "," + title + "," + url);
			var timestamp = new Date().getTime();
		
			UCIWebSearchLoggerBackground.sendRequest({
				command: "logClick",
				timestamp: timestamp,
				querytimestamp: querytimestamp,
				page: page,
				index: index,
				title: title,
				url: url
			});
			
			l("Click logged. Directing to url...");
			//location.href = url;   //force to open one link at a time
		},
		
		/**
		   Google Search Handler
		   - disable instant search, by redirect it back to normal URL
		   - capture when "q" parameter presents.
		*/
		googleScript:function(){
			l("Google Search monitoring script installed.");
			
			//--Disabling Instant Search (if present)"
			if((doc.location.href.match("/search?") || doc.location.href.match("/webhp?")) && !doc.location.href.match("complete"))
				return (doc.location.href = doc.location.href + "&complete=0");
		
			//--When no query presence, disable instant search and stop processing.--//
			if(doc.location.href == "http://google.com/" || doc.location.href == "http://www.google.com/")
				return (doc.location.href = doc.location.href + "webhp?complete=0");
		
			/*----If there's query, start processing----*/
			var query = getParam("q");
			var page = getParam("start");
			page = (page)?(page/10)+1:1;
			l("extract q="+query+" & page="+page);
			
			UCIWebSearchLoggerInject.addNotification(ParticipantID);
			if(query) {
				//Log query
				UCIWebSearchLoggerInject.logQuery("Google", query, function(querytimestamp){
					//get querytimestamp from background.html
					//whether it's previous timestamp or current timestamp
		
					//Inject Code to each search result
					$("a.l").each(function(index){
						//show all links on this page (index starts at 1)
						l((index+1)+":"+$(this).text());
		
						//add click handler to log result when user click
						$(this).mouseup(function(event){
							//if using either left-click or middle-click
							if(event.which==1 || event.which==2){
								//Log click
								UCIWebSearchLoggerInject.logClick(querytimestamp, page, index+1, $(this).text(), this.href);
								return true;
							}
						});
					});	
				});	
			}
			
		},
		
		/**
		  StackOverflow Handler
		  - capture only when search query or tagged possible in the URL
		  - no capturing for page that lists of all questions without query/tag
		*/
		stackoverflowScript:function(){
			l("Stackoverflow monitoring script installed");
			
			if(doc.location.href.match("/search?")){
				//possible url : http://stackoverflow.com/search?page=3&tab=relevance&q=jquery%20onfocus
				var query = getParam("q");
				var page = getParam("page");
			} else if(doc.location.href.match("/questions/tagged/")){
				//possible url : http://stackoverflow.com/questions/tagged/java?page=4&sort=newest&pagesize=15
				var query = doc.location.href.substring(doc.location.href.indexOf("/tagged/")+8);
				if(query.indexOf("?")>=0) 
					query = query.substring(0,query.indexOf("?"));
		
				var page = getParam("page");
			} else	//no matching URL
				return;
			
			UCIWebSearchLoggerInject.addNotification(ParticipantID);
			//if query presents, inject script to capture clicks
			if(query){
				page=page?page:1;
				l("Query="+query+"\nPage="+page);
				
				//Log query
				UCIWebSearchLoggerInject.logQuery("Stackoverflow", query, function(querytimestamp){
					//Inject code to each search result
					$("a.question-hyperlink").each(function(index){
						//show all links on this page (index starts at 1)
						l((index+1)+":"+$(this).text());
						//add click handler to log result when user click
						$(this).mouseup(function(event){
							//if using either left-click or middle-click
							if(event.which==1 || event.which==2){
								//Log click
								UCIWebSearchLoggerInject.logClick(querytimestamp, page, index+1, $(this).text(), this.href);
								return true;
							}
						});
					});	
				});
			}
		},
		
		/**
		   Bing Handler
		   - capture bing searches
		*/
		bingScript:function(){
			l("Bing monitoring script installed.");
		
			//if no searching in progress.
			if(!(doc.location.href.match("/search?")))
				return;
			
			/*----If there's query, start processing----*/
			var query = getParam("q");
			var page = getParam("first");
			page = (page)?Math.floor((page/10)+1):1;
			l("extract q="+query+" & page="+page);	
			UCIWebSearchLoggerInject.addNotification(ParticipantID);
			if(query) {
				//Log query
				queryID = UCIWebSearchLoggerInject.logQuery("Bing", query, page);
				//Log query
				UCIWebSearchLoggerInject.logQuery("Bing", query, function(querytimestamp){
					//Inject Code to each search result
					$(".sb_tlst>h3>a").each(function(index){
						//show all links on this page (index starts at 1)
						l((index+1)+":"+$(this).text());
						//add click handler to log result when user click
						$(this).mouseup(function(event){
							//if using either left-click or middle-click
							if(event.which==1 || event.which==2){
								//Log click
								UCIWebSearchLoggerInject.logClick(querytimestamp, page, index+1, $(this).text(), this.href);
								return true;
							}
						});
					});
				});
			}
		},
		
		/**
		   Show small notification on top-right of the page to indicate logger's status
		*/
		addNotification:function(ParticipantID){
			l("Adding logger notification");
			var status="";
			if(ParticipantID) 
				status="Logger is currently monitoring this search";
			else 
				status="Logger is not running. To enable logger, click on the logger's icon above then click \"connect\".";
		
			var code = "<div id='loggernotification' style='color:#000000; background-color:#FFFFCC; font-size: 12px; float:right; position:fixed; top:20px; right:10px; z-index:1001; padding:5px;'>"+status+"</div>";
			$("body").append(code);
		}
	};
	
	
	/*-----------START SCRIPT for runInject()-----------*/

	l("on page loaded");
	doc = e.originalTarget;
	//view = window._content.document;
	view = window._content;
				
	/*alert("onPageLoad \n"+
			"nodeName="+e.target.nodeName+"\n"+
			"ownerDocument="+e.target.ownerDocument+"\n"+
			"parentNode="+e.target.parentNode+"\n"+
			"target="+e.target.location.href+"\n"+
			"originalTarget(doc)="+doc.location.href+"\n"+
			"window._content.document(view)="+view.location.href+"\n " );
	*/
	jQuery.noConflict();
	$ = function(selector,context){
			return new jQuery.fn.init(selector,context||doc); 
	};
	
	UCIWebSearchLoggerInject.initBinds();
}

/**
 * On a window loaded, add new EventListener 
 * for inject code every time a new page opened
 * @param e
 */
function onWindowLoad(e){
	var appcontent = window.document.getElementById("appcontent");   // browser only
	if(appcontent){
		//everytime a page loaded, call "runInject(e)"
		appcontent.addEventListener("load", runInject, true);
	}
}

/**** Start all these scripts *****/
/*------------------START WORKING---------------*/
/*Add script when firefox loaded*/
window.addEventListener("load", onWindowLoad, false);


