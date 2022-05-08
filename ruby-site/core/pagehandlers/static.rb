lib_want :Profile, "user_skin";
lib_require :Core, 'json'

class StaticHandler < PageHandler
	declare_handlers("/") {
		area :Static
		handle :GetRequest, :static, input(Integer), :files, input(String), remain # ruby module static files

		handle :GetRequest, :script_js, input(Integer), :script, input(/^(.+)\.js$/); #module.js

		handle :GetRequest, :css, input(Integer), :style, input(String), input(/^(.+)\.css$/) #revision/style/skeletonname/skinname.css
	}

	def static(rev_num, modname, remain)
		if($site.config.live && rev_num != $site.static_number.to_i)
			site_redirect(url/$site.static_number/:files/modname/remain, :Static, false)
		end
		
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

		if (static_path)
			static_cache("/files/#{mod.to_s}/#{remain.join('/')}") {|out|
				if (File.file?(file_path))
					out.puts(File.read(file_path))
				else
					raise PageError.new(404), "No Such Module or File"
				end
			}
		end
	end

	# Loads all .css files from {modname}/layout/*.css, where modname is every
	# non-skeleton module loaded (and thus not excluded by config), plus the
	# skeleton module named in the path. So given modules A, B, C, Skeleton1, and Skeleton2,
	# the path /layout/skeleton1.css would load [A, B, C, Skeleton1]/layout/*.css
	# and concatenate the output to the client.
	def css(rev_num, skeleton, skinname_match)
		if($site.config.live && rev_num != $site.static_number.to_i)
			site_redirect(url/$site.static_number/:style/skeleton/skinname_match[0], :Public, false)
		end
		
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
			user_skin_values = user_skin.site_skin_values(skin);
		end
		
		Dir[file_selector].each{|file|
			sio.puts "/*************** begin #{file.to_s} ***************/\n\n"
			begin
				t = CSSTrans::instance(file)

				t.static_files_url = $site.static_files_url
			
				SkinMediator.request_all_values(skeleton, skin).each_pair{|key, value|
					t.send(:"#{key}=", value);
				}
			
				user_skin_values.each_pair{|key, value|
					t.send(:"#{key}=", value);
				};
			
				sio.puts t.display();
			rescue
				$log.info("Error in CSS #{file.to_s}: #{$!}", :error)
				sio.puts "/* Error in file. */"
			end
			sio.puts "/*************** end #{file.to_s} ***************/"
		}
		return sio.string
	end

	# Given the path script/module.js, loads all JavaScript files from
	# module/script/*.js.
	def script_js(rev_num, modname_match)
		if($site.config.live && rev_num != $site.static_number.to_i)
			site_redirect(url/$site.static_number/:script/modname_match[0], :Static, false)
		end
		
		modname = modname_match[1];

		reply.headers["Content-Type"] = PageRequest::MimeType::JavaScript;
		mod = SiteModuleBase.get(modname) || SiteModuleBase.get(SiteModuleBase.module_name(modname));
		script_path = mod && mod.script_path();

		site_values = mod.script_values;
		if (!site_values.empty? || script_path)
			static_cache("/script/#{modname}.js") {|out|
				if (File.directory?(script_path))
					out.print("#{mod.name}Module=#{site_values.to_json};\n")
					out.print("#{mod.script_function_string}\n");
					if (mod.name.to_sym == :Core)
						out.print("Site = CoreModule;\n");
					end

					if(script_path && File.directory?(script_path))
						Dir["#{script_path}/*.js"].each {|file|
							output_script_file(out, script_path, file)
						}
					end
				end
			}
		end
	end
	
	def output_script_file(out, script_path, file)
		@read_files ||= []
		if (@read_files.include?(file))
			return
		else
			@read_files << file
			
			file_obj = File.new(file)
			first_line = file_obj.readline
			prescanned = ""
			# preprocessor directive to include a file // require myscript.js
			while (first_line =~ /\/\/\s*require ([a-zA-Z_\-0-9]+\.js)/)
				$log.info "#{file} is requiring #{script_path}/#{$1}", :debug
				output_script_file(out, script_path, "#{script_path}/#{$1}")
				prescanned += first_line
				first_line = file_obj.readline
			end
			out.print(prescanned)
			out.print(first_line)
			out.print(file_obj.read + "\n")
		end
	end
end
