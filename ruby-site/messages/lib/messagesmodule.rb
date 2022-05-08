
# require 'preferences/lib/preference'
# require 'preferences/pagehandlers/preferences'

class MessagesModule < SiteModuleBase
    # Preference::addModule(self, 20)
        
    def MessagesModule.index(user, has_plus, returnHash)
    	t = Template.instance("messages", "messages_preferences");

   		returnHash["checkbox"]["fwmsgs"         ] = user.fwmsgs
   		returnHash["checkbox"]["ignorebyagemsgs"] = (user.ignorebyage == "both" || user.ignorebyage == "msgs"    ) ? true : false
   		returnHash["checkbox"]["onlyfriendsmsgs"] = (user.onlyfriends == "both" || user.onlyfriends == "msgs"    ) ? true : false

        return t.display
    end

    def MessagesModule.update(user, has_plus, params)
        user.fwmsgs = (params.include?('fwmsgs')) ? true : false

        # The following are handled by commentsmodule.rb:
        # user.ignorebyage
        # user.onlyfriends
    end   
end