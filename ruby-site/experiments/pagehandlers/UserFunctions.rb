require "storable"
require 'auth'
require 'template'
require 'skin'
require 'mail'

def getPOSTval(key)
    return $cgi.params[key][0];
end

class UserFunctions < PageHandler
	declare_handlers("/") {
		area :Public
		access_level :Any

		handle :GetRequest, :login_page, "login"
		handle :GetRequest, :logout_page, "logout"
		handle :GetRequest, :mynex_page, "mynex"
		handle :GetRequest, :nexmsg_page, "nexmsg"
		handle :GetRequest, :checkmsg, "checkmsg"
		handle :GetRequest, :getmsg, "getmsg"

		area :User

		handle :GetRequest, :default_page
	}

	#Returns one actual message in plain text.
	def getmsg()
		session = Session.get(self);
		if (session != nil)
			@headers['type'] = 'text/plain';
			msg = Message.new();
			msg.load!(["userid", "id"], [session.userid, getPOSTval('id')]);
			print msg.msg;
		else
			print "Not logged in."
		end
	end
	
	#Returns message data in XML form for AJAX.
	def checkmsg()
		@headers['type'] = 'text/xml';

		session = Session.get(self);
		if (session != nil)
			print "<table>";
			for msg in Message.getMessages(session.userid)
				print "<tr><td><a href=\"javascript:void(0)\" onclick=\"";
				print "box = newBox(); loadXMLDoc('getmsg?id=" + msg.id.to_s + "', box);\">";
				print msg.subject + "</a></td></tr>";
			end
			print "</table>";
		else
			print "Not logged in."
		end
	end
	
	def nexmsg_page()
		template = Template.new("nexmsg");
		template.display();
	end

	def mynex_page()
		template = Template.new("mynex");
		menu = array();
		for item in getMenuManage()
			menu.push "<a href='javascript:void(#{item.addr})' onclick='\n" +
					"loadXMLDoc(\"#{item.addr}\");\n" +
					"'> #{item.name} </a>";
		end
		
		scriptstring = "<script>var req;\n" +
					"function loadXMLDoc(url) {"+
					"req = false;\n" +
					"    if(window.XMLHttpRequest) {"+
					"    	try {  "+
					"			req = new XMLHttpRequest();\n" +
					"        } catch(e) {"+
					"			req = false;\n" +
					"        }\n" +
					"    } else if(window.ActiveXObject) {"+
					"       	try {"+
					"        	req = new ActiveXObject(\"Msxml2.XMLHTTP\");\n" +
					"      	} catch(e) {"+
					"        	try {"+
					"          		req = new ActiveXObject(\"Microsoft.XMLHTTP\");"+
					"        	} catch(e) {"+
					"          		req = false;\n" +
					"        	}\n" +
					"		}\n" +
					"    }\n" +
					"	if(req) {"+
					"		req.onreadystatechange = processReqChange;\n" +
					"		req.open(\"GET\", url, true);\n" +
					"		req.send(\"\");\n" +
					"	}\n" +
					"}\n" +
					"function processReqChange(){\n"+
					"	if (req.readyState == 4) { \n"+
					"        if (req.status == 200) { \n"+
					"			document.getElementById('MainObj').innerHTML = \"<table height=600><tr><td>\" + req.responseText;\n + \" </td></tr>\"" +
					"        } else {"+
					"            alert(req.statusText);" +
					"        }" +
					"    }" +
					"}\n" +
					"</script>" ;
		
		menustring = implode($skindata.menudivider, menu);
		template.set("menu", scriptstring + menustring);
		template.set("skindir", $skindir);
		#template.setHeader();
		template.display();
	end
	

	def login_page()

		username = getPOSTval('username');
		password = getPOSTval('password')

		if(username != nil)
		    session = Session.new();
		    id = UserNamesDB.getID(username);
		    if (id != nil)
			correct_password = Password.check_password(password, id);
			if (correct_password)
			    session.create_session(self, id, 'n');
			    puts "Congrats, #{username} #{id}!";
			else
			    puts "Password for #{username} is incorrect.";
			end
		    else
			puts "Username #{username} does not exist!";
		    end
		else

		    template = Template.new('login/login');
		    template.set('checkSecure', "checkbox1");
		    template.set('checkRememberMe', "checkbox2");
		    template.set('referer', "sdaf");

		    str = template.toString();
		    rhtml = ERB.new(str, 0);

		    def htmlentities(str)
			return str;
		    end
		    #Run with the current binding, which allows the template to access
		    #all of the variables we set.
		    rhtml.run(template.get_binding);

		end
	end


	def logout_page()
		Session.destroy_session(self);
		print "You should be successfully logged out.";
	end

end
