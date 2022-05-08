class CoreModule < SiteModuleBase
	def script_values()
		vals = {}

		if (smilies_mod = site_module_get(:Smilies))
			vals[:smilies] = smilies_mod.smilies
		end
		{ # this could be made more direct/generic, maybe.
			:www_url => :wwwURL,
			:admin_url => :adminURL,
			:admin_self_url => :adminSelfURL,
			:upload_url => :uploadURL,
			:user_url => :userURL,
			:image_url => :imageURL,
			:user_files_url => :userFilesURL,
			:self_url => :selfURL,
			:static_url => :staticURL,
			:style_url => :styleURL,
			:script_url => :scriptURL,
			:static_files_url => :staticFilesURL,
		}.each {|ruby, js|
			vals[js] = $site.send(ruby).to_s
		}
		vals
	end
	
	def script_function_string()
		return<<-EOS
			CoreModule.coloredImgURL = function(color)
			{
				if (color.match('rgb'))
				{
					color = Nexopia.Utilities.getHexValue(color);
				}
				
				var rubyURL = "#{$site.colored_img_url('__color__')}";
				return rubyURL.replace(/__color__/, color.replace(/\#/, ''));
			};
			EOS
	end
	
	def after_load()
		lib_require :Core, "recolour"
		lib_require :Core, "user_time"
		lib_require :Core, "run-scripts"
	end
end