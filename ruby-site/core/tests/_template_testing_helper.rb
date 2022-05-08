
require 'fileutils'
module TemplateTestingHelper
	TMP_MODULE = :test_data.to_s
	TMP_TEMPLATE_PATH = "#{$site.config.site_base_dir}/#{TMP_MODULE}/templates/"
		
	def create_test_files
		FileUtils.mkdir_p(TMP_TEMPLATE_PATH)      
		self.class.constants.reject{|c| c.scan(/^FILE_\w+/).empty?}.each { |const|
			method_name = 	self.class.const_get(const)
			self.send(method_name)
		}     
	end

	def delete_test_files
		FileUtils.rmtree(TMP_TEMPLATE_PATH)
	end

	def setup 
		create_test_files
	end

	def teardown
		delete_test_files
	end
	
end