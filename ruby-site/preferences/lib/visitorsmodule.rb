
require 'preferences/lib/preference'
require 'preferences/pagehandlers/preferences'

class VisitorsModule < SiteModuleBase
    Preference::addModule(self, 15)
        
    def VisitorsModule.index(user, has_plus, returnHash)
    	t = Template.instance("preferences", "visitors_preferences");

        t.has_plus = has_plus
        
		if (has_plus)
       		returnHash["checkbox"]["recentvisitlistthumbs"] = user.recentvisitlistthumbs
       		returnHash["checkbox"]["recentvisitlistanon"  ] = user.recentvisitlistanon
		end

        return t.display
    end

    def VisitorsModule.update(user, has_plus, params)
        if (has_plus)
            user.recentvisitlistthumbs = (params.include?('recentvisitlistthumbs')) ? true : false
            user.recentvisitlistanon   = (params.include?('recentvisitlistanon'  )) ? true : false        
        end
    end
end
