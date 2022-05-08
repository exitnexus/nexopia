#lib_require :Preferences, 'preference'
require 'preferences/lib/preference'
require 'core/lib/pagehandler'

require 'json'

class Preferences < PageHandler
	# Change this declare_handlers. In use because login is not working
	declare_handlers("/") {
		area   :User
		page   :GetRequest, :Full, :index,  "preferences"            # /users/xxx/preferences
		handle :GetRequest,        :mail,   "preferences_mail"
		handle :GetRequest,        :password,   "preferences_password"
		handle :GetRequest,        :close,  "preferences_close"

		handle :PostRequest,        :update, "update_preferences"
	
	}

	declare_handlers("preferences") {
		area :Public
		access_level :Any	
		page :GetRequest, :Full, :confirm,  "confirm", remain
	}

	def update
		Preference.update(session.user, params)
	end
	
    def close
        user   = request.user
        error  = ""

        closepass = params['closepass', String, ""]
        reason    = params['reason',    String, ""]
        
        error += "Please fill in 'Password' \n" if (closepass == "")
        error += "Please fill in 'Reason'   \n" if (reason    == "")

        if (error == "")
    		correct_password = Password.check_password(closepass, user.userid)
    		
    		if (correct_password)
                error += "Make this step in the future ...";
    		else
    			error += "Current Password is incorrect";
    		end
        end

        puts error if (error != "")
    end

    def confirm(remain)
        currentInactive = UserEmail.find(:first, :conditions => ["`key` = ?", remain[0]])

        if (currentInactive == nil)
            puts "<p style=\"background-color:white;\"> Incorrect Key </p>"
        else
            currentActive = UserEmail.find(:first, :conditions => ["userid = ? AND active = 'y'", currentInactive.userid])

            if (currentActive == nil)
                puts "<p style=\"background-color:white;\"> Error: There is no active email </p>"
            else
                currentActive.email = currentInactive.email
                currentActive.key   = currentInactive.key
                currentActive.time  = currentInactive.time

                currentInactive.delete
                currentActive.store

                puts "<p style=\"background-color:white;\"> Email updated successfully </p>"
            end           
        end        

        puts "<p style=\"background-color:white;\">  </p>"
    end

    def password
        user   = request.user
        error  = ""

        currentpass = params['currentpass', String, ""]
        newpass1    = params['newpass1',    String, ""]
        newpass2    = params['newpass2',    String, ""]
        
        error += "Please fill in 'Current Password'    \n" if (currentpass == "")
        error += "Please fill in 'New Password'        \n" if (newpass1    == "")
        error += "Please fill in 'Retype new Password' \n" if (newpass2    == "")

        error  = "Passwords don't match                \n" if (error == "" && newpass1 != newpass2)

        if (error == "")
    		correct_password = Password.check_password(currentpass, user.userid)
    		
#    		if (correct_password)
                user.password.change_password(newpass1)
#                user.password.store
#    		else
#    			error = "Current Password is incorrect";
#    		end
        end

        puts error if (error != "")
    end
    
    def mail        
        user   = request.user        
        error  = ""

        email1  = CGI::unescape( params['email1',  String, ""] )
        email2  = CGI::unescape( params['email2',  String, ""] )
        oldpass = CGI::unescape( params['oldpass', String, ""] )

        error += "Please fill in 'New Email'        \n" if (email1  == "")
        error += "Please fill in 'Retype New Email' \n" if (email2  == "")
        error += "Please fill in 'Current Password' \n" if (oldpass == "")

        error  = "Emails don't match                \n" if (error == "" &&  email1 != email2)
        error  = "This is not a valid email address \n" if (error == "" && (email1 =~ /^[a-z0-9]+([a-z0-9_.&-]+)*@([a-z0-9.-]+)+$/ ) == nil)
        error  = "Email already in use              \n" if (error == "" &&  UserEmail.find(:first, :conditions => ["email = ?", email1]) != nil )

        if (error == "")
    		correct_password = Password.check_password(oldpass, user.userid)
    		
    		if (correct_password)
                currentInactive = UserEmail.find(:first, :conditions => ["userid = # && active = 'n'", user.userid])                               
                                
                if (currentInactive == nil)
                    currentInactive        = UserEmail.new
                    currentInactive.userid = user.userid
                    currentInactive.active = false
                end
                
                currentInactive.email = email1
                currentInactive.time  = Time.new.to_i
                currentInactive.key   = MD5.new( rand.to_s ).to_s
                currentInactive.store
    		
                # sudo apt-get install postfix
                require 'net/smtp'

                from       = "Nexopia"
                from_alias = "nexopia@nexopia.com"

                to         = user.username
                to_alias   = email1
                
                subject    = "Nexopia email confirmation"

        		tt      = Template.instance("preferences", "confirm");
        		tt.code = currentInactive.key
        		message = tt.display
		                
                msg = [ "Subject: #{subject}                \n",
                        "From   : #{from}   <#{from_alias}> \n",
                        "To     : #{to}     <#{to_alias}>   \n",
                        "Content-type: text/html            \n",
                        message
                       ]
                       
                Net::SMTP.start("localhost", 25) {|smtp|
                    smtp.sendmail(msg, from_alias, to_alias)
                }                
    		else
    			error = "Current Password is incorrect";
    		end
        end
        
        puts error if (error != "")        
        error # for testing 
    end

	def index
		t = Template.instance("preferences", "preferences");
	
        user     = request.user             
        has_plus = (user.premiumexpiry - Time.new.to_i > 0)        
        
        if (has_plus)
            t.has_plus      = true
            t.expiry_days   = "%5.2f" % ((user.premiumexpiry - Time.new.to_i).to_f/86400)
            t.premiumexpiry = Time.at(user.premiumexpiry).strftime("%B %d, %Y, %I:%M %p")
        end
       
		returnHash             = Hash.new
		returnHash["text"    ] = Hash.new
		returnHash["select"  ] = Hash.new
		returnHash["checkbox"] = Hash.new
        returnPage             = ""
        
        Preference.modules.sort.each{| position, mod | 
        	returnPage += pref_index(mod, user, has_plus, returnHash) 
        }

        t.email      = user.email.email
        t.modules    = returnPage
        t.setObjects = returnHash.to_json
        
		puts t.display
	end
	
	def pref_index(mod, user, has_plus, returnHash)
    	t = Template.instance("preferences", "general_preferences");

        t.has_plus = has_plus
        
   		returnHash["checkbox"]["limitads"       ] = user.limitads if (has_plus)
   		returnHash["checkbox"]["showrightblocks"] = user.showrightblocks
   		returnHash["checkbox"]["trustjstimezone"] = user.trustjstimezone
   		returnHash["select"  ]["timezone"       ] = Preference::createStringSelect("timezone", user.timeoffset, Preference::getTimeZonesNames, true)
   		returnHash["select"  ]["skin"           ] = Preference::createStringSelect("skin",     user.skin,       DefaultSkinPage::styles.sort)

        t.prefdate = Time.at(Time.new.to_i + Preference::getTimeZones(user.timeoffset) * 60).strftime("%B %d, %Y, %I:%M %p")

        # As soon as login is working agains, set user.jstimezone
        if (true == false)
           	t.autodetect_timezone = Time.at(Time.new.to_i + user.jstimezone * 60).strftime("%B %d, %Y, %I:%M %p")
        else
            t.time_zone_fail = true
        end        

        return t.display
    end

end