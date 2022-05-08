require "core/tests/template.test.rb"
class TestTemplate

	FILE_INCLUDE_TEST = :file_include_test.to_s
	INCLUDE_TEST_FILE = "#{TMP_TEMPLATE_PATH}/#{FILE_INCLUDE_TEST}.html"    

	INCLUDE_0_TEST = "file_include_0_partial"
	INCLUDE_0_TEST_FILE = "#{TMP_TEMPLATE_PATH}/#{INCLUDE_0_TEST}.html"
	def file_include_test
		File.open(INCLUDE_TEST_FILE,File::CREAT|File::TRUNC|File::RDWR) {|f|
			f <<  contents_file_include_test
		}     
		File.open(INCLUDE_0_TEST_FILE,File::CREAT|File::TRUNC|File::RDWR) {|f|
			f <<  contents_file_include_0_test
		}
	end
	def contents_file_include_0_test
		%Q{      
			<span>INCLUDED</span>      
		}
	end
	def contents_file_include_test
		%Q{
			<html xmlns:t="http://www.nexopia.com/dev/template">
			<t:var name='template_name' type='String'/>
			<body>
			<t:template-include module="#{TMP_MODULE}" name='#{INCLUDE_0_TEST}'/>
			</body>
			</html>          
		}
	end
	
	def test_include
		t = Template.instance(TMP_MODULE,FILE_INCLUDE_TEST,nil)     
		string = t.display
		doc = Hpricot(string)        #work around for bug test_duplicate_display_bug
		assert((doc/'/html/body/span').size > 0, "  Elements are Expected to exist after include")      
	end
	
	FILE_PAGE_INCLUDE_TEST = :file_page_include_test.to_s
	PAGE_INCLUDE_TEST_FILE = "#{TMP_TEMPLATE_PATH}/#{FILE_PAGE_INCLUDE_TEST}.html"
	def file_page_include_test
		File.open(PAGE_INCLUDE_TEST_FILE,File::CREAT|File::TRUNC|File::RDWR) {|f|
			f <<  contents_file_page_include_test
		}
	end
	def contents_file_page_include_test
		%Q{
			<html xmlns:t="http://www.nexopia.com/dev/template">           
			<body>
				<t:handler-include path="{page_path}"/>	
			</body>
			</html>          
		}
	end


	def test_page_handler
		expected_page_path = "/fishy"
		#
		# Create some Mock objects for mitigating the need for dependancies
		#
		mock_handler = mock()
		mock_request = mock()
		mock_out = mock()		
		#
		# This is how they work in this context 
		#
		mock_out.expects(:string).returns("").at_least_once
		mock_request.expects(:ok?).returns(true).at_least_once	
		mock_request.expects(:out).returns(mock_out).at_least_once
		mock_request.expects(:reply).returns(mock_request).at_least_once
		
		mock_handler.expects(:subrequest).returns(mock_request).at_least_once.with(){|io,type,path|
			path == expected_page_path
		}

		t = Template.instance(TMP_MODULE,FILE_PAGE_INCLUDE_TEST,mock_handler)         
		t.page_path = expected_page_path   	
		t.display # execute the template
	end

	
	#
	# If you try and use an Attribute variable in a template_include
	# it will not work.
	# This is a bug because of line  160 in default_view.rb :
	#
	# 	Template.get_class(mod.to_s, templ.to_s)::get_vars.each{|var,type|
	# 
	# going to work around it for now

	FILE_INCLUDE_ATTR_VAR_TEST = :file_include_attr_var_test.to_s
	INCLUDE_ATTR_VAR_TEST_FILE = "#{TMP_TEMPLATE_PATH}/#{FILE_INCLUDE_ATTR_VAR_TEST}.html"    

	INCLUDE_ATTR_VAR_0_TEST = "file_include_attr_var_0_partial"
	INCLUDE_ATTR_VAR_0_TEST_FILE = "#{TMP_TEMPLATE_PATH}/#{INCLUDE_ATTR_VAR_0_TEST}.html"
	def file_include_attr_var_test
		File.open(INCLUDE_ATTR_VAR_TEST_FILE,File::CREAT|File::TRUNC|File::RDWR) {|f|
			f <<  contents_file_include_attr_var_test
		}     
		File.open(INCLUDE_ATTR_VAR_0_TEST_FILE,File::CREAT|File::TRUNC|File::RDWR) {|f|
			f <<  contents_file_include_attr_var_0_test
		}
	end
	def contents_file_include_attr_var_0_test
		%Q{      
			<span>{iter}</span>      
		}
	end
	def contents_file_include_attr_var_test
		%Q{
			<html xmlns:t="http://www.nexopia.com/dev/template">
			<body>
			<t:loop t:id="(1...2)" t:iter="iter">
			<t:template-include module="#{TMP_MODULE}" name='#{INCLUDE_ATTR_VAR_0_TEST}'/>
			</t:loop>
			</body>
			</html>          
		}
	end
	
	def test_include_attr_var
		t = Template.instance(TMP_MODULE,FILE_INCLUDE_ATTR_VAR_TEST,nil)     
		string = t.display
		doc = Hpricot(string)
		assert_equal((doc/'/html/body/span').text, "1", "  Variables should be passed into included templates.")      
	end

end