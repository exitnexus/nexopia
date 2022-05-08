
lib_require :floatmenu, "floatmenu"

class Sidebar < PageHandler
	declare_handlers("sidebar") {
		area :User
		handle :GetRequest, :sidebar_page

		area :Public
		access_level :Any

		handle :GetRequest, :sidebar_page
		handle :GetRequest, :sidebar_content, "content"
	}

	def sidebar_page()		
        if (!session.anonymous?() && !session.user.anonymous?())		
            Floatmenu.new("Sidebar", "<a href='/logout'>Log out</a>")#userData)
        else						
            Floatmenu.new("Login", LoginPages.login_page)
        end
	end

	def sidebar_content()
        if (!session.anonymous?() && !session.user.anonymous?())
            #puts userData;
            puts "<a href='/logout'>Log out</a>"
        else
            puts LoginPages.login_page;
        end
	end

	def userData
		t = Template.instance("sidebar", "sidebar", self)

        user             = session.user

        t.user           = user
       # t.userid         = user.userid
        t.username       = user.username

        t.messages       = true
        t.new_messages   = MessageHeader.getStatus(user, "new")

        t.allStyles = DefaultSkinPage::styles.sort

       #t.friends        = true

       # onlineFriends    = Array.new
       # Friend.all(user.userid).each {|friend| onlineFriends << friend if (friend.user.online)}
       # t.friends_online = onlineFriends.length
       # t.onlineFriends  = onlineFriends

        t.moderator      = true
        t.pics           = 0
        t.mods           = nil
        t.mods_online    = 0
        t.admins         = nil
        t.admins_online  = 0
        t.globals        = nil
        t.globals_online = 0

        return t.display
    end
end
