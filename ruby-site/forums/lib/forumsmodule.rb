
lib_require :Preferences, 'preference'
#lib_require preferences/pagehandlers/preferences'

class ForumsModule < SiteModuleBase
    Preference::addModule(self, 30)
    
    set_log_actions(
    	:none => 0,
    	:deletethread => 1,
    	:deletepost => 2,
    	:lock => 3,
    	:unlock => 4,
    	:stick => 5,
    	:unstick => 6,
    	:announce => 7,
    	:unannounce => 8,
    	:move => 9,
    	:mute => 10,
    	:unmute => 11,
    	:invite => 12,
    	:uninvite => 13,
    	:editpost => 14,
    	:addmod => 15,
    	:removemod => 16,
    	:editmod => 17,
    	:flag => 18,
    	:unflag => 19
    );
    
    def ForumsModule.index(user, has_plus, returnHash)
    	t = Template.instance("forums", "preferences");

   		returnHash["checkbox"]["replyjump"        ] = (user.replyjump == "forum") ? true : false 
   		
   		returnHash["checkbox"]["autosubscribe"    ] = user.autosubscribe
   		returnHash["checkbox"]["forumjumplastpost"] = user.forumjumplastpost
   		returnHash["checkbox"]["showpostcount"    ] = user.showpostcount
   		returnHash["checkbox"]["showsigs"         ] = user.showsigs
   		
   		returnHash["select"]["forumpostsperpage"]   = Preference::createStringSelect("forumpostsperpage", user.forumpostsperpage, [ 10, 25, 50, 100 ])
   		returnHash["select"]["forumsort"        ]   = Preference::createStringSelect("forumsort",         user.forumsort,         [ ["Most Recently Active", "post"], ["Most Recently Created", "thread"] ])

        return t.display
    end

    def ForumsModule.update(user, has_plus, params)
        user.replyjump         = (params.include?('replyjump'        )) ? "forum" : "thread"

        user.autosubscribe     = (params.include?('autosubscribe'    )) ? true : false
        user.forumjumplastpost = (params.include?('forumjumplastpost')) ? true : false
        user.showpostcount     = (params.include?('showpostcount'    )) ? true : false
        user.showsigs          = (params.include?('showsigs'         )) ? true : false

        user.forumpostsperpage = params['forumpostsperpage', Integer] if (params.include?('forumpostsperpage'))
        user.forumsort         = params['forumsort'        ,  String] if (params.include?('forumsort'))
    end
end
