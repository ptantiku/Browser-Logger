/*
* JAVASCRIPT for Menu (popup icon)
*/
debug = false;
function l(msg){ if(debug) alert(msg); }

var UCIWebSearchLoggerMenu = {

		
	openNewUser:function(){
		gBrowser.selectedTab = gBrowser.addTab("http://rad.ics.uci.edu/chromelogger/newuser.php");
	},
	
	openSubmitPage:function(){
		// Add tab, then make active
		gBrowser.selectedTab = gBrowser.addTab("http://rad.ics.uci.edu/chromelogger/review.php");
	},

	startLogging:function(){
		this.openNewUser();
	},
	
	stopLogging:function(){
		l("stopLogging clicked");
		UCIWebSearchLoggerBackground.sendRequest({ 
					command: "removeParticipantID" 
				},	function(response){
					if(response && response.error==true)
						alert("Error: cannot remove participant ID from storage.");
				});
		document.location.reload(true);
	},

	gotoAbout:function(){
		l("gotoAbout clicked");
		gBrowser.selectedTab = gBrowser.addTab("http://rad.ics.uci.edu/chromelogger/about.php");
	},

	gotoStudyInfo:function(){
		l("gotoStudyInfo clicked");
		gBrowser.selectedTab = gBrowser.addTab("http://rad.ics.uci.edu/chromelogger/studyinfo.php?"+
									"ParticipantID="+UCIWebSearchLoggerMenu.$("#ParticipantID").text());
	},

	updateMenu:function(){
		l("toolbar:updateMenu:"+UCIWebSearchLoggerBackground);
		
		//load ParticipantID from the extension
		UCIWebSearchLoggerBackground.sendRequest({
			command: "getParticipantID"
		}, function(response){
			l("Response: "+response);
			ParticipantID = response;
			if(ParticipantID==null) {
				alert("You don't have a valid Participant ID, Press OK, then enter Participant ID.");
				UCIWebSearchLoggerMenu.$("#UCIWebSearchLoggerMenu-nolog").show();
				UCIWebSearchLoggerMenu.$("#UCIWebSearchLoggerMenu-logging").hide();	
			} else {
				l("ParticipantID="+ParticipantID);
				UCIWebSearchLoggerMenu.$("#UCIWebSearchLoggerMenu-nolog").hide();
				UCIWebSearchLoggerMenu.$("#UCIWebSearchLoggerMenu-logging").show();
				UCIWebSearchLoggerMenu.$("#UCIWebSearchLoggerMenu-ParticipantID").text(ParticipantID);
			}
		});
	}
	
};


//set $ for toolbar (it runs before main_inject to keep $ with correct document (which is overlay.xul))
jQuery.noConflict();
UCIWebSearchLoggerMenu.$ = jQuery;
/*function(selector,context){
	return new jQuery.fn.init(selector,context||window._content.document); 
};*/
