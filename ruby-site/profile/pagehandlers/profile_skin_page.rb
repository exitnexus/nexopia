lib_require :Profile, 'user_skin'

module Profile	
	class ProfileSkinPage < PageHandler
		declare_handlers("/") {
			area :User
			handle :GetRequest, :user_css, "style", Integer, Integer, input(String);#input(/\d\.css$/) #users/UserName/style/site_revision/skin_revision/skinid.css
		}
		
		def user_css(skin_id)
			skin_id = skin_id.to_s().gsub(".css", "");
			
			reply.headers["Content-Type"] = PageRequest::MimeType::CSS;
			reply.headers["Expires"] = (Time.now + 365*24*60*60).httpdate
			
			# If the user does not have plus, do not output the stylesheet as it's
			#  the ability to custom style pages is plus only.
			if(!request.user.plus?())
				return;
			end
			
			skin = UserSkin.find(:first, [request.user.userid, skin_id.to_i()]);
			SiteModuleBase.loaded {|name|
				mod = SiteModuleBase.get(name)
				layout_path = mod && mod.layout_path()
				if (layout_path && File.directory?(layout_path) && !mod.skeleton?)
					puts(load_user_css(layout_path, skin))
				end
				control_path = mod && mod.control_path()
				if (control_path && File.directory?(control_path) && !mod.skeleton?)
					puts(load_user_css(control_path, skin))
				end
			}
		end
	
		def load_user_css(path, skin)
			sio = StringIO.new();
			file_selector = File.join(path, "**", "*.css");
			
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
