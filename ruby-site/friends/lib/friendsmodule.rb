
#require 'preferences/lib/preference'
#require 'preferences/pagehandlers/preferences'

class FriendsModule < SiteModuleBase
=begin    Preference::addModule(self, 10)
        
    def FriendsModule.index(user, has_plus, returnHash)
    	t = Template.instance("friends", "friends_preferences");

        t.has_plus = has_plus
        
  		returnHash["checkbox"]["friendsauthorization" ] = user.friendsauthorization if (has_plus)
   		returnHash["checkbox"]["friendslistthumbs"    ] = user.friendslistthumbs

        return t.display
    end

    def FriendsModule.update(user, has_plus, params)
        user.friendsauthorization  = (params.include?('friendsauthorization' )) ? true : false if (has_plus)
        user.friendslistthumbs     = (params.include?('friendslistthumbs'    )) ? true : false
    end
=end   
end
