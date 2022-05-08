class RapModule < SiteModuleBase
	require 'RAP'
	
	@@rap_config = {:path => "#{$site.config.svn_base_dir}/php-site/public_html/"}
	@@php = RAP.new(@@rap_config)
	@@php.php_begin
	
	def self.php
		return @@php
	end
	
	def shutdown()
		@@php.php_end
	end
end