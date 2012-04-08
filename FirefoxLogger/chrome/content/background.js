//declare this variable global
var UCIWebSearchLoggerBackground;

function runBackground(){
	//var $ = function(selector,context){ return new jQuery.fn.init(selector,context||window._content.document); };
	//debug message to print on console
	var debug = false;
	function l(msg){ if(debug) console.log(msg); }
	
	var firstRun;
	
	//Storage for logs and data
	var storage = globalStorage['rad.ics.uci.edu'];
	//storage:localStorage,
	
	//remind user to submit every 1 day
	var submitInterval = 1000*60*60*24*1;
		
	//initialized
	function onLoad(e){
		//run this on first run when a new window open
		if(firstRun===undefined){
			l("onload: submitInterval="+submitInterval);
			UCIWebSearchLoggerBackground.timeCheck();
			setInterval(UCIWebSearchLoggerBackground.timeCheck, submitInterval);
			firstRun=true;
		}
	}
		
	/**
	 * Remove all keys from storage 
	 * (only for Firefox's globalStorage which does not have .clear() function)
	 * */
	function storageClear(){
		storage.removeItem('ParticipantID');
		storage.removeItem('oldParticipantID');
		storage.removeItem('lastSubmit');
		storage.removeItem('queries');
		storage.removeItem('clicks');
	}

	/**
	* Background running javascript code
	*/
	UCIWebSearchLoggerBackground = {
		
		//main function: classifies input requests, 
		//then send them out to an appropriated function.
		sendRequest:function(request, sendResponse) {
			l("in Background.sendRequest(): "+request.command);
			
			//if there's no callback function, create a dummy one
			if(sendResponse===undefined || sendResponse==null)
				sendResponse = function(a){};	
				
			//classify request
			if (request.command == "logQuery")
				sendResponse(UCIWebSearchLoggerBackground.logQuery(request));
			else if (request.command == "logClick")
				UCIWebSearchLoggerBackground.logClick(request);
			else if (request.command == "setParticipantID")
				sendResponse(UCIWebSearchLoggerBackground.setParticipantID(request.ParticipantID));
			else if (request.command == "getQueries")
				sendResponse(UCIWebSearchLoggerBackground.getQueries());
			else if (request.command == "getClicks")
				sendResponse(UCIWebSearchLoggerBackground.getClicks());
			else if (request.command == "getParticipantID")
				sendResponse(UCIWebSearchLoggerBackground.getParticipantID());
			else if (request.command == "getOldParticipantID")
				sendResponse(UCIWebSearchLoggerBackground.getOldParticipantID());		
			else if (request.command == "deleteQuery")
				sendResponse(UCIWebSearchLoggerBackground.deleteQuery(request));
			else if (request.command == "deleteClick")
				sendResponse(UCIWebSearchLoggerBackground.deleteClick(request));
			else if (request.command == "clearStorage")
				UCIWebSearchLoggerBackground.clearStorage();
			else if (request.command == "removeParticipantID")
				sendResponse(UCIWebSearchLoggerBackground.removeParticipantID());
			else if (request.command == "getLastSubmit")
				sendResponse(UCIWebSearchLoggerBackground.getLastSubmit());
			else if (request.command == "submitComplete")
				UCIWebSearchLoggerBackground.submitComplete();
		},
	
		//get all queries from LocalStorage
		getQueries:function(){
			l("get queries from storage");
			var queries = storage.getItem("queries");
			if(queries && queries!=null) queries = JSON.parse(queries);
			else queries = JSON.parse("[]");
		
			return queries;	
		},
	
		//save change
		saveChanges:function(queries){
			l("save changes into storage");
			storage.setItem("queries", JSON.stringify(queries));
		},
	
		//get all clicks from a specific query (JSON object)
		getClicks:function(query){
			l("get clicks from storage");
			if(query  && query!=null && query.clicks) {
				return query.clicks;
			} else if(query && query!=null){
				query.clicks = JSON.parse("[]");
				return query.clicks;
			} else
				return null; //error (input query is null)
		},
	
		//get all clicks from queries (JSON object array) giving a specific timestamp of a query
		getClicksByTimestamp:function(queries, timestamp){
			query = UCIWebSearchLoggerBackground.findQueryByTimestamp(queries, timestamp);
			return UCIWebSearchLoggerBackground.getClicks(query);
		},
	
		//delete query by query's timestamp (only use in review page)
		deleteQuery:function(request){
			l("delete a query from storage");
			var queries = UCIWebSearchLoggerBackground.getQueries();
			if(!queries || queries.length==0) return;
			//iterate from current to oldest to find the id 
			for(var i=queries.length-1;i>=0;i--){
				if(queries[i].timestamp==request.timestamp){
					queries.splice(i,1);
					storage.setItem("queries",JSON.stringify(queries));
					return queries;
				}
			} 
		},
	
		//delete click by its timestamp and its query's timestamp (only use in review page)
		deleteClick:function(request){
			l("delete a click from storage");
			var queries = UCIWebSearchLoggerBackground.getQueries();
			var clicks = UCIWebSearchLoggerBackground.getClicksByTimestamp(queries, request.querytimestamp);
			if(!clicks || clicks.length==0) return null;
			
			//iterate from current to oldest to find the id 
			for(var i=clicks.length-1;i>=0;i--){
				if(clicks[i].timestamp==request.timestamp){
					clicks.splice(i,1);
					storage.setItem("queries",JSON.stringify(queries));
					return queries;
				}
			} 
		},
	
		//search input "queries" (JSON object array) for a query (JSON object)
		//that matches timestamp
		findQueryByTimestamp:function(queries, timestamp) {
			l("finding a query from input timestamp");
			for(var i=queries.length-1;i>=0;i--){
				if(queries[i].timestamp==timestamp){
					return queries[i];
				}
			}
			return null;
		},
	
		//search input "queries" ( object array) for a query (JSON object)
		//that matches "query" (string of search keywords)
		findPreviousSameQuery:function(queries,timestamp,site,query) {
			l("finding same query from previous searches logged");
			//search for previous search on the same query
			for(var i=queries.length-1;i>=0;i--){
				if(queries[i].site == site 
					&& queries[i].query == query) {
					return queries[i];
				}
			}
			return null;
		},
	
		//Log Query into LocalStorage
		// if there is previous query with 
		// same query string, use that query.
		logQuery:function(request){	
			l("Log Query to LocalStorage");
			//get queries
			queries = UCIWebSearchLoggerBackground.getQueries();
			//finding previous query
			previousQuery = UCIWebSearchLoggerBackground.findPreviousSameQuery(queries, request.timestamp, request.site, request.query);
			//if found a previous session
			if(previousQuery!=null) {
				return previousQuery.timestamp;
			} else {
				//put new query in and store the changes
				queries.push({timestamp: request.timestamp, site: request.site, query: request.query});
				UCIWebSearchLoggerBackground.saveChanges(queries);
				return request.timestamp;
			}
		},
	
		//Log Click into LocalStorage
		logClick:function(request){
			l("Log Click to LocalStorage");
			//get queries
			queries = UCIWebSearchLoggerBackground.getQueries();
			var clicks = UCIWebSearchLoggerBackground.getClicksByTimestamp(queries,request.querytimestamp);
			if(clicks){
				clicks.push({timestamp: request.timestamp, querytimestamp: request.querytimestamp, page: request.page, index: request.index, title: request.title, url: request.url});
	
				UCIWebSearchLoggerBackground.saveChanges(queries);
			}
		},
	
		//get ParticipantID from localStorage
		getParticipantID:function(){
			l("getParticipantID ACK");
			return storage.getItem("ParticipantID");
		},
	
		//get previous participant's ID	
		getOldParticipantID:function(){
			l("get old ParticipantID");
			return storage.getItem("oldParticipantID");
		},
	
		//set ParticipantID to localStorage
		//If user is changed, delete logs of previous user.
		setParticipantID:function(ParticipantID){
			l("set ParticipantID");
			storage.setItem("ParticipantID", ParticipantID);	
			//retrieve previous ID
			var oldParticipantID = UCIWebSearchLoggerBackground.getOldParticipantID(); 
			//if change user, delete previous user's data
			if(oldParticipantID==null || oldParticipantID!=ParticipantID){	
				UCIWebSearchLoggerBackground.clearStorage();
				storage.setItem("oldParticipantID", ParticipantID);
			}
			//else resume the old data
			
			//set lastTimeSubmit (if never submit before, as first time baseline)
			if(UCIWebSearchLoggerBackground.getLastSubmit()==null || UCIWebSearchLoggerBackground.getLastSubmit()=="null"){
				UCIWebSearchLoggerBackground.setLastSubmit();
			}
			
			return { error: false };
		},
	
		//remove local participantID
		removeParticipantID:function(){
			l("remove ParticipantID");
			var ParticipantID = UCIWebSearchLoggerBackground.getParticipantID();
			if(ParticipantID==null || ParticipantID=="")	//if not getting ParticipantID
				return {error: true};
		
			storage.setItem("oldParticipantID", ParticipantID); 	//for later resume
			storage.removeItem("ParticipantID");
	
			return { error: false };
		},
	
		//get last submit time
		getLastSubmit:function(){
			l("get last submitted time");
			return storage.getItem("lastSubmit");
		},
	
		//set last submit time to current time
		setLastSubmit:function(){
			l("set last submitted time to now");
			var timestamp = new Date().getTime();
			return storage.setItem("lastSubmit", timestamp);
		},
	
		//Clear all logged data from computer
		// still stores "ParticipantID", "oldParticipantID", "lastSubmit" ,if has any.
		clearStorage:function(){
			l("clear log data from user's computer");
			var ParticipantID = storage.getItem("ParticipantID");
			var oldParticipantID = storage.getItem("oldParticipantID");
			var lastSubmit = UCIWebSearchLoggerBackground.getLastSubmit();
			storageClear();
			//keep current participant id and last submission time.
			if(ParticipantID!=null && ParticipantID!=""){
				storage.setItem("ParticipantID", ParticipantID);
				storage.setItem("lastSubmit", lastSubmit);
			}
			//keep old participant id
			if(oldParticipantID!=null && oldParticipantID!=""){
				storage.setItem("oldParticipantID", oldParticipantID);
			}
		},
	
		//Finishing logs submission by set "lastSubmit" time and clear logs
		submitComplete:function(){
			l("submit complete: perform post processing.");
			UCIWebSearchLoggerBackground.setLastSubmit();
			UCIWebSearchLoggerBackground.clearStorage();
		},
		
		//Periodically reminds user to submit
		timeCheck:function(){
			l("timeCheck for reminding user to submit");
			if(storage.getItem("ParticipantID")){
				var cTimestamp = new Date().getTime();
				var lTimestamp = storage.getItem("lastSubmit");
				if(!lTimestamp) 
					storage.setItem("lastSubmit", cTimestamp);
	
				if(cTimestamp - lTimestamp > submitInterval)
					gBrowser.selectedTab = gBrowser.addTab("http://rad.ics.uci.edu/chromelogger/pleasesubmit.php");
	
			} else {
				gBrowser.selectedTab = gBrowser.addTab(
					"http://rad.ics.uci.edu/chromelogger/newuser.php");
				/* cannot use window.open in onLoad function, it'll cause neverending recursive
				   window.open(
						"http://rad.ics.uci.edu/chromelogger/newuser.php",
						"NewUserPopup",
						"width=600,height=400"
					);*/
			}
		}
	};
	
	
	/*------------------START WORKING---------------*/
	/*Add script when firefox loaded*/
	window.addEventListener("load", onLoad, false);
}

runBackground();