lib_require :Core, 'pagehandler', 'php_integration'

# Handles the admin role account management interface.
class KeymapManager < PageHandler
	declare_handlers("keymaps") {
		area :Admin
		access_level :Admin, CoreModule, :config
		page :GetRequest, :Full, :keymap_form
		handle :PostRequest, :create_keymap, 'create'
	}

	def keymap_form
		t = Template::instance('adminutils', 'keymap_form')
		puts t.display
	end
	
	def create_keymap
		php_key = params["php_key", String]
		ruby_key = params["ruby_key", String]
		if (php_key && ruby_key)
			m = MemcacheKeyMapping.new
			m.phpkey = php_key
			m.rubykey = ruby_key
			m.store
			puts "Success."
		else
			puts "Missing a key."
		end
	end
end
