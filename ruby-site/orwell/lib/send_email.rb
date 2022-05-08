lib_require :Orwell, 'emailer'
lib_require :Core, 'users/user', 'lrucache'

require 'hpricot'
require 'open-uri'

module Orwell
	# Compose and send the email from templates.
	class SendEmail
		attr_writer :subject
		
		def subject=(rhs)
			@subject = rhs.strip()
		end
		
		# Send an email to a user.
		# - user is the user to send the message to. (This can be nil, but
		#     if so, a :to param needs to be set in the args hash)
		# - plaintext_template is the template to use for the
		# plaintext part of the message.
		# - :html_template (optional) is the template to use
		# for the html part of the message.  Images will be
		# embedded automatically.
		# - :template_module (optional) indicates the module where
		# the template is located.  It defaults to 'orwell' if not specified.
		# - :limit_emails (optional) will, if true, engage rate limits
		# when sending emails.  See the rate_limit method.
		# - Any other arguments will be passed to the templates
		# as template variables.  For example, to pass the
		# session object, you may call as:
		# send(user, 'plaintext_template', :html_template => 'html_template',
		#      :session => req.session)
		def send(user, plaintext_template, args = {} )
			start_time = Time.now.to_f
				
			# PHP doesn't send symbols through, so let's normalize any string keys to symbols
			# before going ahead.
			args_normalized = {}
			args.each { |k,v|
				args_normalized[k.to_sym] = v
			}
			args = args_normalized
			
			if user.nil?()
				if args.has_key?(:to)
					email_check_regex = /^[a-z0-9]+([a-z0-9_.+&-]+)*@([a-z0-9.-]+)+\.([a-z0-9.-]+)+$/;
					if ( !(args[:to].downcase =~ email_check_regex) )
						raise ArgumentError.new("Email address provided (#{args[:to]})is not a valid email address")
					end
					
					to_address = args[:to]
				else
					raise ArgumentError.new('Missing to argument with nil user. One or the other must be present')
				end
			else	
				raise ArgumentError.new("nil user email for userid #{user.userid}") if (user.email.nil?)
				if (user.email.strip.empty?)
					raise ArgumentError.new('empty user email')
				end
				
				to_address = user.email
			end
			
			html_template = nil
			if args.has_key?(:html_template)
				html_template = args[:html_template]
				args.delete(:html_template)
			end

			template_module = nil
			if args.has_key?(:template_module)
				template_module = args[:template_module]
				args.delete(:template_module)
			else
				template_module = 'orwell'
			end
			
			limit_emails = args.has_key?(:limit_emails) && args[:limit_emails]
			
			template_variables = Array.new
			args.each { |key,value|
				template_variables << [key, value]
			}

			body_plain = process_plaintext(user, template_module, plaintext_template,
			                               template_variables)
			if html_template != nil
				body_html, body_parts = process_html(user, template_module, html_template,
				                                     template_variables)
			end
			
			params = Hash.new
			if html_template != nil
				params[:msg_html] = body_html
				params[:msg_parts] = body_parts unless body_parts.empty?
			end
			params[:smtp_server] = $site.config.mail_server
			params[:smtp_port] = $site.config.mail_port
			#if args.has_key?(:from)
			#	params[:from] = args[:from]
			#else
				params[:from] = "Nexopia <no-reply@#{$site.config.email_domain}>"
			#end
			to = to_address
			subject = @subject
			
			if ($site.config.override_email)
				to = $site.config.override_email
				if !user.nil?
					subject = "#{user.email}:#{user.userid} - #{@subject}"
				else
					subject = "#{to_address} - #{@subject}"
				end
				$log.info "Sending email: #{subject}"
			end
				
			begin
				Orwell::send_email(to, subject, body_plain, params)
				rate_limit(start_time) if (limit_emails)
			rescue ArgumentError => e
				$log.info "KNOWN ISSUE NEX-1352: Probably a bad 'To' line, #{to}.  Exception: #{e.to_s}", :warning
			rescue Net::SMTPServerBusy, Net::SMTPSyntaxError, Net::SMTPFatalError, Net::SMTPUnknownError => e
				$log.info "KNOWN ISSUE NEX-1352: SMTPError, #{to}.  Exception: #{e.to_s}", :warning
			rescue Object => e
				$log.info "KNOWN ISSUE NEX-1352: WTFError, #{to}.  Exception: #{e.to_s}", :warning
			end
		end # def send(user, plaintext_template, args = {} )
		
		def self.php_send(userid, subject, template, args = {})

			user = User.find(:first, userid.to_i)
			
			msg = SendEmail.new
			msg.subject = subject
			msg.send(user, template, args) unless user.nil?

		end
		
		private
		def process_plaintext(user, template_module, plaintext_template, variables)
			t = Template::instance(template_module, plaintext_template)
			t.user = user
			variables.each { |variable|
				t.method_missing("#{variable[0]}=", variable[1]) 
			}
			return t.display()
		end
		
		def process_html(user, template_module, html_template, variables)
			# Get the raw HTML
			t = Template::instance(template_module, html_template)
			t.user = user
			variables.each { |variable|
				t.method_missing("#{variable[0]}=", variable[1]) 
			}
			msg_html = t.display()
			
			# Now convert any images to embedded
			msg_parts = Array.new()
			doc = Hpricot(msg_html)
			img_count = 0
			(doc/:img).each { |img|
				
				# In order to do delay loading we have an images src tag
				# in the a custom tag called url.  Then when we actually
				# want the image we use JS to put the url into src.
				# Since this is an email we just want to do that replacement right away.
				if (img.has_attribute?(:url))
					img[:src] = img[:url]
				end
				
				# Skip this if we don't have have a src attribute at this point.
				next unless img.has_attribute?(:src)
				
				# Grab the image
				content = content_type = nil
				
				# Already cached?
				@lrucache = LRUCache.new(128) if (!defined?(@lrucache))
				if (@lrucache.include?(img[:src]))
					content, content_type = @lrucache[img[:src]]
				else
					# first check if it's an image on our site.
					# if it is, then do a sub request for it.
					# if it isn't, then do a wget.
					url_parts = $site.url_to_area(img[:src])
					if (url_parts[0] && !PageHandler.current.nil?)
						out = StringIO.new()
						req = PageHandler.current.subrequest(out, :GetRequest,
						 	url_parts[1], nil, url_parts[0])
						next if( !req.get_reply_ok() )
						content = req.get_reply_output
						content_type = req.reply.headers["Content-Type"]
					else
						begin
							open(img[:src], 'User-Agent' => 'Ruby-Wget') { |image|
								content = image.read()
								content_type = image.content_type()
							}
						rescue
							content = nil
							# Ignore errors, leave image unchanged
						end
					end
					if (!content.nil?)
						@lrucache[img[:src]] = [content, content_type]
					end
				end
				
				if (!content.nil?)
					id = "embedded_image_#{img_count}" #"@nexopia.com"
					img_count += 1
					img[:src] = "cid:#{id}"
					msg_parts << [content_type, id, content]
				end
			}
			
			# Make sure our links are fully qualified.
			# According to http://www.w3schools.com/TAGS/att_a_href.asp
			# this means that we can search for ://.  If we have it,
			# we assume it's fully qualified.  If we don't, we presume
			# we don't have the scheme or the host.domain:port, and
			# add it.
			(doc/:a).each { |a|
				next unless a.has_attribute?(:href)
				next if a[:href] =~ /^mailto:.*/
				
				href = a[:href]
				unless (href =~ /:\/\//)
					if (href =~ /^\#/) # Start with '#' character, an anchor
						# Nothing to do
					elsif (href =~ /^\//) # Start with '/' character?
						a[:href] = "#{$site.www_url}#{href}"
					else
						a[:href] = "#{$site.www_url}/#{href}"
					end
				end
			}

			return [doc.to_s, msg_parts]
		end
		
		# We don't want to send too many emails at a time, so this
		# method institutes rate limiting.
		# - start_time is the time (as floating point) at the beginning
		# of the calling method.
		# We compare this to the current time.  If we are rate limited to
		# 2 per second, that means we'll pause for up to 500 ms, depending
		# on how long the calling method took.
		# You can set the rate limit by altering the global variable,
		# $site.config.orwell_email_rate_limit, or by  setting the memcache
		# key, orwell_email_rate_limit.  The memcache key is checked once
		# every ten seconds or so.  Both values are specified as max-per-ms
		# so to rate limit to two per second, set 500.  To rate limit to ten
		# per second, set to 100.
		def rate_limit(start_time)
			if !defined? @@limit
				@@limit = 500
				if defined? $site.config.orwell_email_rate_limit
					@@limit = $site.config.orwell_email_rate_limit 
				end
			end
			if !defined? @@last_memcache_rate_limit_check
				@@last_memcache_rate_limit_check = 0
			end
			if ((Time.now.to_i - @@last_memcache_rate_limit_check) > 10)
				new_limit = $site.memcache.get('orwell_email_rate_limit')
				if (new_limit.nil?)
					$site.memcache.set('orwell_email_rate_limit', @@limit)
				else
					@@limit = new_limit.to_f
				end
				@@last_memcache_rate_limit_check = Time.now.to_i
			end

			interval = @@limit / 1000.0 - (Time.now.to_f - start_time.to_f);
			sleep(interval) if (interval > 0)
		end
		
	end
	
end
