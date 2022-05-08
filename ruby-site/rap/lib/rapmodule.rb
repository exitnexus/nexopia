class RapModule < SiteModuleBase
	require 'RAP'
	
	@@rap_config = {:path => "#{$site.config.legacy_base_dir}/"}
	@@php = RAP.new(@@rap_config)
	@@php.php_begin
	
	def self.php
		return @@php
	end
	
	def shutdown()
		@@php.php_end
	end
end

class RAP

	#define some functions to be used php side
	class HTMLParser
		extend UserContent
		attr_accessor :content
		user_content(:content, :htmlescape => true, :bbcode => false, :smilies => false)

		def initialize(str)
			self.content = str;
		end
	end

	def html_escape(str)
		parser = HTMLParser.new(str);

		return parser.content.parsed;
	end

	def bbcode(str)
		new_str = BBCode.parse(str);

		return new_str;
	end

	def wrap(str, len = 50)
		return str.wrap(len);
	end
	
	# location_array should be a PHP-style array (i.e. ruby hash) with id values
	def location_text(location_array)
		return {} if location_array.nil?
		# PHP's arrays always get sent in as hashes, so we really just want to grab the values of
		# our location_array object, which is really a ruby hash. Furthermore, the values will be
		# Strings, so we will want to convert those to Integers
		locations = location_array.values.map { |location| location.to_i }
	
		locs = Locs.find(:order => "id", *locations)

		return_hash = {}
		locs.each {|loc|
			return_hash[loc.id] = loc.augmented_name
		}
		
		return return_hash
	end

	def sub_locations(location_id)
		return Locs.get_children_ids(location_id)
	end

	def smilies(str)
		if (SiteModuleBase.get(:Smilies))
			return SmiliesModule::smilify(str);
		else
			return str;
		end
	end
	
	alias :old_call :call
	def call(*args)
		trap("SIGPROF") {
			$log.info("PHP Forced us to exit early", :error, :rap)
			raise SiteError.new(500), "Execution timeout forced by php."
		}
		return old_call(*args)
	end
end
