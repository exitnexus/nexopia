lib_want :Profile, "user_skin";

class StaticHandler < PageHandler
	declare_handlers("/") {
		area :Static
		handle :GetRequest, :static, Integer, :files, input(String), remain # ruby module static files

		handle :GetRequest, :css, Integer, :style, input(String), input(/^(.+)\.css$/) #style/revision/skeletonname/skinname.css
		handle :GetRequest, :script_js, Integer, :script, input(/^(.+)\.js$/); #module.js
		
	}

	def static(modname, remain)
		mod = SiteModuleBase.get(modname)
		if(mod.nil?())
			SiteModuleBase.site_modules(){|site_mod|
				if(modname.to_s.downcase() == site_mod.to_s.downcase())
					mod = site_mod;
				end
			}
		end
		static_path = mod && mod.static_path()
		file_path = static_path && "#{static_path}/#{remain.join('/')}"

		if (static_path && File.file?(file_path))
			static_cache("/files/#{remain.join('/')}") {|out|
				out.puts(File.read(file_path))
			}
		end
		raise PageError.new(404), "No Such Module or File"
	end

	# Loads all .css files from {modname}/layout/*.css, where modname is every
	# non-skeleton module loaded (and thus not excluded by config), plus the
	# skeleton module named in the path. So given modules A, B, C, Skeleton1, and Skeleton2,
	# the path /layout/skeleton1.css would load [A, B, C, Skeleton1]/layout/*.css
	# and concatenate the output to the client.
	def css(skeleton, skinname_match)
		skinname = skinname_match[1]
		reply.headers["Content-Type"] = PageRequest::MimeType::CSS
		skelmod_name_decapsed = SiteModuleBase.module_name(skeleton);
		skelmod = SiteModuleBase.get(skeleton) || SiteModuleBase.get(skelmod_name_decapsed)
		if (skelmod && skelmod.skeleton?)
			static_cache("/style/#{SiteModuleBase.directory_name(skelmod.name)}/#{skinname}.css") {|out|
				skel_layout = skelmod && skelmod.layout_path()
				out.puts(load_css(skel_layout, skeleton, skinname))
				skel_control = skelmod && skelmod.control_path()
				out.puts(load_css(skel_control, skeleton, skinname))

				SiteModuleBase.loaded {|name|
					mod = SiteModuleBase.get(name)
					layout_path = mod && mod.layout_path()
					if (layout_path && File.directory?(layout_path) && !mod.skeleton?)
						out.puts(load_css(layout_path, skeleton, skinname))
					end
					control_path =  mod && mod.control_path()
					if (control_path && File.directory?(control_path) && !mod.skeleton?)
						out.puts(load_css(control_path, skeleton, skinname))
					end
				}
			}
		end
	end

	#TODO: Tie the css parser generator into this step.
	def load_css(path, skeleton, skin)
		sio = StringIO.new

		file_selector = File.join(path, "**", "*.css");
		if(site_module_loaded?(:Profile))
			user_skin = Profile::UserSkin.new();
			user_skin_values = user_skin.skin_values(request.session.user);
		end
		
		Dir[file_selector].each{|file|
			sio.puts "/*************** begin #{file.to_s} ***************/\n\n"
			t = CSSTrans::instance(file)

			SkinMediator.request_all_values(skeleton, skin).each_pair{|key, value|
				t.send(:"#{key}=", value);
			}
			
			user_skin_values.each_pair{|key, value|
				t.send(:"#{key}=", value);
			};
			
			sio.puts t.display();
			sio.puts "/*************** end #{file.to_s} ***************/"
    	}
		return sio.string
	end

	# Given the path script/module.js, loads all JavaScript files from
	# module/script/*.js.
	def script_js(modname_match)
		modname = modname_match[1];

		reply.headers["Content-Type"] = PageRequest::MimeType::JavaScript;
		mod = SiteModuleBase.get(modname) || SiteModuleBase.get(SiteModuleBase.module_name(modname));
		script_path = mod && mod.script_path();

		if (script_path && File.directory?(script_path))
			static_cache("/script/#{modname}.js") {|out|
				out.print("Site={staticFilesURL:\"#{$site.static_files_url}\"};")

				Dir["#{script_path}/*.js"].each {|file|
					out.print(File.read(file));
				}
			}
		end
	end
end
