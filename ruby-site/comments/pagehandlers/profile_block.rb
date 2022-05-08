lib_require :Comments, "comments"
lib_want	:Profile, "profile_block_query_info_module";

module Comments
	class ProfileBlock < PageHandler
		declare_handlers("profile_blocks/Comments/comments/") {
			area :User
			access_level :Any
			
			# this should become a handle later.
			page :GetRequest, :Full, :comments, input(Integer);
			
			access_level :LoggedIn
			handle :PostRequest, :post_comment_ajax, "post_comment_ajax"
			handle :PostRequest, :delete_comment_ajax, "delete_comment_ajax"
			
			area	:Self
			handle	:PostRequest, :comment_block_create, input(Integer), "create";
			handle	:PostRequest, :comment_block_remove, input(Integer), "remove";
		}
		
		def self.comments_query(info)
			if(site_module_loaded?(:Profile))
				info.extend(ProfileBlockQueryInfo);
				info.title = "Comments";
				info.initial_position = 99;
				info.initial_column = 1;
				info.form_factor = :wide;
				info.multiple = false;
				info.editable = false;
				info.initial_block = true;
			end

			return info;
		end

		def comments(blockid)
			templ = Template::instance('comments', 'profile_block')
			templ.can_post = !request.session.anonymous? && !request.user.ignored?(request.session.user)
			templ.viewing_user = request.session.user
			templ.comments_user = request.user
			templ.comments = request.user.first_five_comments
			templ.error = params['error', String]
			templ.success = params['success', String]

			templ.post_url = (request.base_url/request.user.username/:comments/:post_comment)
			templ.ajax_post_url = (request.base_url/request.user.username/:profile_blocks/:Comments/:comments/:post_comment_ajax).to_s+":Body"
			templ.post_form_key = SecureForm.encrypt(request.session.user, Time.now, url/:User/:profile_blocks/:Comments/:comments/:post_comment_ajax)

			# todo: make it so admins can delete too.
			templ.can_delete = (request.user.userid == request.session.userid)
			templ.ajax_delete_url = (request.base_url/request.user.username/:profile_blocks/:Comments/:comments/:delete_comment_ajax).to_s+":Body"
			templ.delete_form_key = SecureForm.encrypt(request.session.user, Time.now, url/:User/:profile_blocks/:Comments/:comments/:delete_comment_ajax)
			
			templ.static_url = $site.static_files_url/:Comments
			
			# prime the first_five_comments list so it's not spamming queries to the database
			# it's really annoying that I have to do this, we need to make this
			# more automated.
			templ.comments.each {|item|
				item.author
			}
			templ.comments.each {|item|
				item.author.account
				item.author.username_obj
				item.author.first_picture
			}
			
			emoticons = ""
			request.skeleton.smilies.each do |key, value|
				emoticons << "<td valign=\"middle\"><img src=\"#{$site.static_files_url}/smilies/#{value}.gif\" alt=\"#{key}\" /></td>"
			end
			templ.emoticons = "<div style=\"display: none;\">
			<table id=\"emoticons_list\" height=\"30\" width=\"100%\"><tr>#{emoticons}</tr></table>
			<img id=\"emoticon_left_arrow_img\" src=\"#{$site.static_files_url}/core/enhanced_text_input/images/icon_left_arrow.gif\" />
			<img id=\"emoticon_right_arrow_img\" src=\"#{$site.static_files_url}/core/enhanced_text_input/images/icon_right_arrow.gif\" />
			<img id=\"emoticon_left_back_img\" src=\"#{$site.static_files_url}/core/enhanced_text_input/images/left_arrow_back.png\" />
			<img id=\"emoticon_right_back_img\" src=\"#{$site.static_files_url}/core/enhanced_text_input/images/right_arrow_back.png\" />
			</div>"
			
			puts templ.display()
		end
		
		def post_comment()
			content = params['comment_text', String]
			can_post = !request.session.anonymous? && !request.user.ignored?(request.session.user)
			
			raise "No post specified" if (!content || content.length < 1)
			raise "User has ignored you" if (!can_post)

			comment = Comment.new
			comment.userid = request.user.userid
			comment.id = Comment.get_seq_id(request.user.userid)
			comment.authorid = request.session.userid
			comment.authorip = request.get_ip_as_int
			comment.time = Time.now.to_i
			comment.parse_bbcode = true
			comment.nmsg = content
			comment.store
		end
		
		def delete_comment()
			comment_id = params['comment_id', Integer]
			comment = comment_id && Comment.find(:first, request.session.userid, comment_id)
			
			raise "No post specified" if (!comment)

			comment.delete()
		end
		
		def post_comment_ajax()
			rewrite_params = {'success' => "Posted successfuly."}
			begin
				post_comment()
			rescue RuntimeError => str
				rewrite_params = {'error' => str.to_s}
			end
			rewrite(:GetRequest, (url/:profile_blocks/:Comments/:comments/0).to_s+":Body", rewrite_params)
		end
		
		def delete_comment_ajax()
			rewrite_params = {'success' => "Deleted successfuly."}
			begin
				delete_comment()
			rescue RuntimeError => str
				rewrite_params = {'error' => str.to_s}
			end
			rewrite(:GetRequest, (url/:profile_blocks/:Comments/:comments/0).to_s+":Body", rewrite_params)
		end
		
		def comment_block_remove(block_id)
			# Do nothing.
		end
		
		
		def comment_block_create(block_id)
			# Do nothing.
		end		
	end
end
