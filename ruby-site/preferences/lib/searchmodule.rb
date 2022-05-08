
require 'preferences/lib/preference'
require 'preferences/pagehandlers/preferences'

class SearchModule < SiteModuleBase
    Preference::addModule(self, 0)

    def SearchModule.index(user, has_plus, returnHash)
    	t = Template.instance("preferences", "search_preferences");

   		returnHash["text"]["defaultminage"] = user.defaultminage
   		returnHash["text"]["defaultmaxage"] = user.defaultmaxage

   		returnHash["select"]["defaultsex"]  = Preference::createStringSelect("defaultsex", user.defaultsex, [*user.instance_variable_get(:@defaultsex).symbols])
   		returnHash["select"]["defaultloc"]  = Preference::createStringSelect("defaultloc", user.defaultloc, Locs::create_string_name( Locs.find(:all) ) )

        return t.display
    end

    def SearchModule.update(user, has_plus, params)
        user.defaultminage = params['defaultminage', Integer] if (params.include?('defaultminage'))
        user.defaultmaxage = params['defaultmaxage', Integer] if (params.include?('defaultmaxage'))
        user.defaultloc    = params['defaultloc'   , Integer] if (params.include?('defaultloc'))

        user.defaultsex    = params['defaultsex'   ,  String] if (params.include?('defaultsex'))
    end
end
