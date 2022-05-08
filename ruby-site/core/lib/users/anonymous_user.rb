class AnonymousUser
	attr_accessor :userid
	
	def initialize(ip, id=nil)
		if (ip)
			@userid = self.class.ip_to_id(ip)
		else
			@userid = id
		end
	end
	
	def username
		return ""
	end
	
	def online
		return false
	end
	
	def id
		return self.userid
	end
	
	def id=(val)
		self.userid=(val)
	end
	
	def anonymous?
		return true
	end
	
	def skintype
		"frames"
	end
	
	def activated?
		return false
	end

	def logged_in?
		return false;
	end
	
	def skin
		return "newblack"
	end
	def showrightblocks
		return false;
	end
	def profilefriendslistthumbs
		return true
	end
	
	def plus?
		return false
	end

	def ignored?(user)
		return false;
	end

	def age()
		return 0;
	end

	def uri_info
		return ['', '']
	end
	
	def img_info(type = 'landscapethumb')
		return ['', $site.static_files_url / :Userpics / :images / "no_profile_image_#{type}.gif"]
	end
	
	def profilefriendslistthumbs
		return true
	end
	
	
	def pic_mod?()
		return false;
	end
	
	class << self
		def ip_to_id(ip)
			ip = ip.split('.').map {|ip_chunk| ip_chunk.to_i}
			return -(ip[0]*16777216+ip[1]*65536+ip[2]*256+ip[3])
		end
	end
end
