
require 'preferences/lib/preference'
require 'preferences/pagehandlers/preferences'

class GeneralModule < SiteModuleBase
    Preference::addModule(self)
        
    def GeneralModule.update(user, has_plus, params)
        user.timeoffset      = params['timezone', Integer] if (params.include?('timezone'))
        user.skin            = params['skin'    ,  String] if (params.include?('skin'))

        user.limitads        = (params.include?('limitads'       )) ? true : false if (has_plus)      
        user.showrightblocks = (params.include?('showrightblocks')) ? true : false
        user.trustjstimezone = (params.include?('trustjstimezone')) ? true : false        
  end
end
