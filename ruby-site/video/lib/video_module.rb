lib_require :Core, "storable/user_content"

class VideoModule < SiteModuleBase
	class HostBase
		
		# removes anything 'malicious' about the url (ie. autoplay). Simple derived objects should just overload allowed_params.
		# only works for urls that are split on ?&. It is ok with urls that are split on just & as well. Any other format needs
		# to have this method redefined.
		def validate_url(url)
			allowed = allowed_url_params()
			return url.gsub(/([\&\?])([^=\&\?]+)=([^=\&\?]+)/) { |match|
				delim, key, val = $1, CGI::unescape($2), CGI::unescape($3)
				valid = allowed[key]
				valid_match = valid.match(val) if (valid)
				if (valid && valid_match)
				 	match
				else
				 	""
				end
			}
		end
		
		# a map of url params to a validating regex. validate_url uses this to determine what to allow as params to the url.
		def allowed_url_params()
			return {}
		end
		
		# A map of <param> names to a validating regex. parse_object_tags uses this to determine what param tags to allow.
		def allowed_params()
			return {}
		end
		
		# Use this in the derived class to register it as a url handler for the video
		def self.real_register(url, derived)
			@urls ||= {}
			@urls[url] = derived.new
		end
		def self.register(url)
			HostBase.real_register(url, self)
		end
		
		def self.find(url)
			@urls ||= {}
			@urls.each {|url_match, val|
				if (url_match.match(url))
					return val
				end
			}
			return nil
		end
		
	end # class HostBase

=begin
	<object width="425" height="355"><param name="movie" value="http://www.youtube.com/v/-GNApD8yoTM&hl=en"></param><param name="wmode" value="transparent"></param><embed src="http://www.youtube.com/v/-GNApD8yoTM&hl=en" type="application/x-shockwave-flash" wmode="transparent" width="425" height="355"></embed></object>
	<object width="425" height="373"><param name="movie" value="http://www.youtube.com/v/-GNApD8yoTM&hl=en&rel=0&border=1"></param><param name="wmode" value="transparent"></param><embed src="http://www.youtube.com/v/-GNApD8yoTM&hl=en&rel=0&border=1" type="application/x-shockwave-flash" wmode="transparent" width="425" height="373"></embed></object>
	<object width="425" height="373"><param name="movie" value="http://www.youtube.com/v/-GNApD8yoTM&hl=en&rel=0&color1=0x006699&color2=0x54abd6&border=1"></param><param name="wmode" value="transparent"></param><embed src="http://www.youtube.com/v/-GNApD8yoTM&hl=en&rel=0&color1=0x006699&color2=0x54abd6&border=1" type="application/x-shockwave-flash" wmode="transparent" width="425" height="373"></embed></object>
=end
	class YoutubeVideo < HostBase
		def allowed_url_params()
			return {
				"rel" => /^[0-1]$/,
				"hl" => /^\w{2}$/,
				"border" => /^[0-1]$/,
				"color1" => /^[0-9a-zA-Z]{1,8}$/,
				"color2" => /^[0-9a-zA-Z]{1,8}$/,
			}
		end
		def allowed_params()
			return {
				"wmode" => /^transparent$/,
			}
		end
		register(/^www\.youtube\.com$/)
		register(/^www\.youtube-nocookie\.com$/)
	end
	
=begin
	<object width="464" height="392"><param name="movie" value="http://embed.break.com/NDc5ODUx"></param><embed src="http://embed.break.com/NDc5ODUx" type="application/x-shockwave-flash" width="464" height="392"></embed></object><br><font size=1><a href="http://break.com/index/genius-interviewed-about-aliens.html">Genius Interviewed About Aliens</a> - Watch more <a href="http://www.break.com/">free videos</a></font>
=end
	class BreakComVideo < HostBase
		def validate_url(url)
			match = /^http:\/\/embed\.break\.com\/[\w=\/]+$/.match(url)
			if (match)
				return match[0]
			else
				return nil
			end
		end
		register(/^embed\.break\.com$/)
	end
	
=begin
	<embed style="width:400px; height:326px;" id="VideoPlayback" type="application/x-shockwave-flash" src="http://video.google.com/googleplayer.swf?docId=-4974994628499449162&hl=en-CA" flashvars=""> </embed>
=end
	class GoogleVideo < HostBase
		def allowed_url_params()
			return {
				"docId" => /^\-?\d+$/,
				"hl" => /^\w{2}-\w{2}$/
			}
		end
		register(/^video\.google\.com$/)
	end
	
=begin
	<embed width="448" height="361" type="application/x-shockwave-flash" wmode="transparent" src="http://i212.photobucket.com/player.swf?file=http://vid212.photobucket.com/albums/cc38/babes_photobucket/CIMG0211.flv&amp;sr=1">
=end
	class PhotoBucketVideo < HostBase
		def allowed_url_params()
			return {
				"file" => /^http:\/\/\w+\.photobucket\.com\/.*\.flv$/,
				"sr" => /^[0-9]$/,
			}
		end
		def allowed_params()
			return {
				"wmode" => /^transparent$/,
			}
		end
		register(/^\w+\.photobucket\.com$/)
	end

=begin
	<object type="application/x-shockwave-flash" width="400" height="225" data="http://www.vimeo.com/moogaloop.swf?clip_id=419344&amp;server=www.vimeo.com&amp;fullscreen=1&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=">	<param name="quality" value="best" />	<param name="allowfullscreen" value="true" />	<param name="scale" value="showAll" />	<param name="movie" value="http://www.vimeo.com/moogaloop.swf?clip_id=419344&amp;server=www.vimeo.com&amp;fullscreen=1&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=" /></object><br /><a href="http://www.vimeo.com/419344/l:embed_419344">Pause</a> from <a href="http://www.vimeo.com/sjogren/l:embed_419344">Aaron Sjogren</a> on <a href="http://vimeo.com/l:embed_419344">Vimeo</a>.
=end
	class Vimeo < HostBase
		def allowed_url_params()
			return {
				"clip_id" => /^\d+$/,
				"server" => /^vimeo\.com$/,
				"fullscreen" => /^\d$/,
				"show_title" => /^\d$/,
				"show_byline" => /^\d$/,
				"show_portrait" => /^\d$/,
				"color" => /^\w+$/,
			}
		end
		def allowed_params()
			return {
				"quality" => /^\w+$/,
				"scale" => /^\w+$/,
				"allowfullscreen" => /^(true|false)$/,
			}
		end
		register(/vimeo.com$/)
	end
	
=begin
music:
	<object width="460" height="390"><param name="movie" value="http://media.imeem.com/s/cdr5rYCuCu/aus=false/"></param><param name="allowFullScreen" value="true"></param></param><param name="salign" value="lt"></param><param name="scale" value="noscale"></param><embed src="http://media.imeem.com/s/cdr5rYCuCu/aus=false/" allowFullScreen="true" scale="noscale" type="application/x-shockwave-flash" width="460" height="390"></embed></object>
video:
	<object width="400" height="345"><param name="movie" value="http://media.imeem.com/v/SDyLPCvheA/aus=false/pv=2"></param><param name="allowFullScreen" value="true"></param><embed src="http://media.imeem.com/v/SDyLPCvheA/aus=false/pv=2" type="application/x-shockwave-flash" width="400" height="345" allowFullScreen="true"></embed></object>
video autoplay:
	<object width="400" height="345"><param name="movie" value="http://media.imeem.com/v/SDyLPCvheA/pv=2"></param><param name="allowFullScreen" value="true"></param><embed src="http://media.imeem.com/v/SDyLPCvheA/pv=2" type="application/x-shockwave-flash" width="400" height="345" allowFullScreen="true"></embed></object>
=end
	class Imeem < HostBase
		def validate_url(url)
			if (!url.gsub!(/aus=\w+/, "aus=false"))
				url.gsub!(/^(http:\/\/media\.imeem\.com\/[a-z]+\/[\w-]+)(.*)$/, '\1/aus=false\2')
			end
			return url
		end
		def allowed_params()
			return {"wmode" => /^transparent$/}
		end
		register(/^media.imeem.com$/)
	end
	
	class MetroLyrics < HostBase
		def allowed_url_params()
			return {
				"lyricid" => /^\d+$/,
				"border" => /^[.0-9]+$/,
				"bordert" => /^[.0-9]+$/,
				"bgfont" => /^0x[A-F0-9]{1,8}$/,
				"bg" => /^http:\/\/www\.metrolyrics\.com\//,
				"filter" => /^0x[A-F0-9]{1,8}$/,
				"filtert" => /^[.0-9]+$/,
				"txt" => /^0x[A-F0-9]{1,8}$/,
				"fontname" => /^\w+$/,
				"fontsize" => /^[.0-9]+$/,
				"speed" => /^[.0-9]+$/,
			}
		end
		def allowed_params()
			return {
				"quality" => /^\w+$/,
				"wmode" => /^transparent$/,
				"bgcolor" => /^#[0-9A-F]{6,6}$/,
			}
		end
		
		register(/^www\.metrolyrics\.com$/)
	end

=begin
	<object width="480" height="392" data="http://flash.revver.com/player/1.0/player.swf?mediaId=1521139" type="application/x-shockwave-flash" id="revvervideoa17743d6aebf486ece24053f35e1aa23"><param name="Movie" value="http://flash.revver.com/player/1.0/player.swf?mediaId=1521139"></param><param name="FlashVars" value="allowFullScreen=true"></param><param name="AllowFullScreen" value="true"></param><param name="AllowScriptAccess" value="always"></param><embed type="application/x-shockwave-flash" src="http://flash.revver.com/player/1.0/player.swf?mediaId=1521139" pluginspage="http://www.macromedia.com/go/getflashplayer" allowScriptAccess="always" flashvars="allowFullScreen=true" allowfullscreen="true" height="392" width="480"></embed></object>
=end	
	class Revver < HostBase
		def allowed_url_params()
			return {
				"mediaId" => /^\d+$/
			}
		end
		def allowed_params()
			return {
				"FlashVars" => /^\w+=\w+$/,
				"AllowFullScreen" => /^\w+$/
			}
		end
		
		register(/^flash\.revver\.com$/)
	end
	
	def self.parse_embed_tag(elem, buffer, recurse)
		$log.info("Video embed tag: #{elem}", :debug)
		
		host_regex = /^http:\/\/([a-zA-Z0-9\.\-]+)/
		default_allowed_params = {
			"width" => /^[0-9]{1,3}$/,
			"height" => /^[0-9]{1,3}$/,
			"type" => /^application\/x\-shockwave\-flash$/,
			"pluginspage" => /^http:\/\/www\.macromedia\.com\/go\/getflashplayer/, 
			"align" => /^middle$/,
			"name" => /^\w+$/,
			"wmode" => /^transparent$/,
		}
		embed_attr = elem.attributes
		
		# validate the url
		src = embed_attr["src"]
		return false if (!src)
		host_match = host_regex.match(src)
		return false if (!host_match)
		host = host_match[1]
		video_handler = HostBase.find(host)
		return false if (!video_handler)
		src = video_handler.validate_url(src)
		return false if (!src)
		elem.set_attribute("src", src)
		embed_attr.delete("src")
		
		elem.set_attribute("wmode", "transparent") # force transparent so it doesn't blow up popups.
		
		# validate the params and remove any that aren't whitelisted.
		allowed = video_handler.allowed_params()
		allowed.merge!(default_allowed_params)
		embed_attr.each {|key, val|
			if (!allowed[key] || !allowed[key].match(val))
				elem.remove_attribute(key)
			end
		}
		buffer << elem.stag.inspect
		buffer << elem.etag.inspect if (elem.etag) # only if there was one in the source
		
		# hpricot doesn't understand that embed is self-closing, so we emulate this ourselves by now
		# letting the user-content parser handle its children
		elem.search("/*").each {|child_elem|
			recurse.call(child_elem)
		}
		
		return true
	end

	def self.parse_object_tag(elem, buffer, recurse)
		$log.info("Video object tag: #{elem}", :debug)

		host_regex = /^http:\/\/([a-zA-Z0-9\.\-]+)/
		default_allowed_params = {
			"movie" => /^http:\/\/.*$/,
			"wmode" => /^transparent$/,
		}

		object_attr = elem.attributes
		data_src = nil
		object_attr.each {|key, val|
			case key
			when "data"
				data_src = val
			when "width", "height"
				if (!/[0-9]{1,3}/.match(val))
					elem.remove_attribute(key)
				end
			else
				elem.remove_attribute(key)
			end
		}
		movie_param = elem.search("/param[@name='movie']").first
		movie_src = nil
		if (movie_param)
			movie_param = movie_param
			movie_src = movie_param.attributes["value"].to_s
		end
		
		# if we have both, check if they're not the same and force them to be if not.
		if (movie_src && data_src && movie_src != data_src)
			elem.set_attribute("data", movie_src)
			data_src = movie_src
		end
		
		# now validate the source
		src = data_src || movie_src
		return false if (!src)
		host_match = host_regex.match(src)
		return false if (!host_match)
		host = host_match[1]
		video_handler = HostBase.find(host)
		return false if (!video_handler)
		src = video_handler.validate_url(src)
		return false if (!src)
		if (data_src)
			elem.set_attribute("data", src)
		end
		if (movie_src)
			movie_param.set_attribute("value", src)
		end
		
		buffer << elem.stag.inspect
		# now validate the params by looping through them
		allowed = video_handler.allowed_params().merge(default_allowed_params)
		wmode_added = false
		elem.each_child {|child_elem|
			if (child_elem.class == Hpricot::Elem)
				case child_elem.name
				when "param"
					param = child_elem
					key = param.attributes['name']
					val = param.attributes['value']
					next if (!key || !val)
					if (allowed[key] && allowed[key].match(val))
						buffer << param.stag.inspect
						buffer << "</param>"
						if (key == "wmode")
							wmode_added = true
						end
					end
				when "embed"
					parse_embed_tag(child_elem, buffer, recurse)
				end
			end
		}
		if (!wmode_added)
			buffer << %Q{<param name="wmode" value="transparent"></param>}
		end
		if (elem.etag)
			buffer << elem.etag.inspect
		else
			buffer << "</object>"
		end
	end
	
	UserContent.add_allowed_html("object", lambda {|elem, buffer, recurse| parse_object_tag(elem, buffer, recurse) })
	UserContent.add_allowed_html("embed", lambda {|elem, buffer, recurse| parse_embed_tag(elem, buffer, recurse) })
end