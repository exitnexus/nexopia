lib_require :Profile, 'profile_skin', 'php_profile_skin'

module Profile	
	class ProfileSkinPage < PageHandler
		declare_handlers("/") {
			area :User
			handle :GetRequest, :user_css, :style, Integer, Integer, input(/^(.+)\.css$/) #users/UserName/style/skinrevision/skinname.css
		}
		
		def user_css(skin_match)
			skin_name = skin_match[1]
			reply.headers["Content-Type"] = PageRequest::MimeType::CSS
			SiteModuleBase.loaded {|name|
				mod = SiteModuleBase.get(name)
				layout_path = mod && mod.layout_path()
				if (layout_path && File.directory?(layout_path) && !mod.skeleton?)
					puts(load_user_css(layout_path, skin_name))
				end
				control_path = mod && mod.control_path()
				if (control_path && File.directory?(control_path) && !mod.skeleton?)
					puts(load_user_css(control_path, skin_name))
				end
			}
		end
	
		def load_user_css(path, user_skin_name)
			sio = StringIO.new();
			file_selector = File.join(path, "**", "*.css");
			#skin = ProfileSkin.find(request.user.userid, user_skin_name, :first, :name);
			skin = UserSkin.find(:first, :conditions => ["userid = ? AND name = ?", request.user.userid, user_skin_name]);
			$log.info("I'm getting #{user_skin_name} for #{request.user.userid} via #{path}");
			Dir[file_selector].each{|file|
				t = CSSTrans::user_instance(file)
				
				if(!skin.nil?())
					skin.skin_values(request.user).each_pair {|name, value|
						t.send("#{name}=".to_sym, value)
					}
				end
				
				display = t.display();
				if (display.strip.empty?)
					sio.print("");
				else
					sio.print("/*************** begin #{file.to_s} ***************/\n\n");
					sio.print(display);
					sio.print("/*************** end #{file.to_s} ***************/");
				end
			}
			return sio.string
		end
	end
end
