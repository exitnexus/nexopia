class JavascriptConfig
	attr_accessor :config_module
	
	YUI_VERSION = "2.6.0"
	
	def initialize(module_require=[], static_require=[], yui_require=[])
		@static_require = static_require
		@module_require = module_require
		@yui_require = yui_require
	end

	#recursively merge javascript dependencies and then load the paths for the merged list
	def javascript_paths
		unless (@calculated_paths)
			js = recursive_config
			module_paths = js.module_require.map {|mod|
				$site.script_url/"#{mod}.js"
			}
			static_paths = js.static_require.map {|stat|
				$site.static_files_url.to_s + "/#{stat}.js"
			}
			yui_paths = js.yui_require.map {|yui|
				#XXX: The $site.config.live || true should just be $site.config.live but version 2.6.0 of YUI doesn't work on IE in a non minified state
				"http://yui.yahooapis.com/#{YUI_VERSION}/build/#{yui.chomp('-beta').chomp('-experimental')}/#{yui}#{($site.config.live || true) ? '-min' : ''}.js"
			}
			config_module_paths = @config_module ? [$site.script_url/"#{@config_module}.js"] : []
 			@calculated_paths = (yui_paths + static_paths + module_paths + config_module_paths).map {|path| path.to_s}
		end
		return @calculated_paths
	end
	
	def module_require
		@module_require ||= []
	end
	
	def yui_require
		@yui_require ||= []
		return @yui_require
	end
	
	def static_require
		@static_require ||= []
		return @static_require
	end
	
	def recursive_config
		js = JavascriptConfig.new
		self.module_require.each {|dir_name|
			mod = SiteModuleBase.get(SiteModuleBase.module_name(dir_name))
			begin
				js += mod.javascript_config.recursive_config
			rescue
				$log.info "Unable to load javascript for module: #{dir_name}.", :error
			end
		}
		js += self
		return js
	end

	def +(other_conf)
		new_static_require = (self.static_require + other_conf.static_require).uniq
		new_module_require = (self.module_require + other_conf.module_require).uniq
		new_yui_require = (self.yui_require + other_conf.yui_require).uniq
		result = self.class.new(new_module_require, new_static_require, new_yui_require)
	end
end