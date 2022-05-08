#
# TODO
# This class is getting very large.
# Consider breaking it up into chunks somehow
#
#

require 'samifier/lib/samifier'
require 'pp'
lib_require :Core, "template/template";
require 'core/lib/pagehandler'
require 'rubygems'
require 'hpricot'

require 'mocha'
require 'stubba'
require 'core/tests/_template_testing_helper'
lib_require :Devutils, 'quiz'

class TestTemplate < Quiz
	include TemplateTestingHelper
	require 'core/tests/lib/template_include_test'

#===============================================================================
#Array test
#===============================================================================
	FILE_ARRAY_TEST = :file_array_test.to_s
	ARRAY_TEST_FILE = "#{TMP_TEMPLATE_PATH}/#{FILE_ARRAY_TEST}.html"
	def file_array_test
		File.open(ARRAY_TEST_FILE,File::CREAT|File::TRUNC|File::RDWR) {|f|
			f <<  contents_file_array_test
		}
	end
	def contents_file_array_test
		%Q{
			<html xmlns:t="http://www.nexopia.com/dev/template">
			<body>
			<p t:id='array' t:iter='e' t:index='index' class='{index}'>{e}</p>

			</body>
			</html>
		}
	end
	def test_array
		t = Template.instance(TMP_MODULE,FILE_ARRAY_TEST,nil)
		expected = [0,1,2,3]
		t.array = expected
		string = t.display
		doc = Hpricot(string)        #work around for bug test_duplicate_display_bug
		assert((doc/'/html/body/p').size == expected.size, " #{expected.size} Elements are Expected")
		(doc/'/html/body/p').each_with_index {|e,i|

			assert_equal(expected[i],e.innerHTML.strip.to_i)
			assert_equal(expected[i],e['class'].strip.to_i)
		}

	end


#===============================================================================
#Duplicate display test
#===============================================================================
	#
	# bug found Oct 17th 2006
	#
	def test_duplicate_display_bug
		t = Template.instance(TMP_MODULE,FILE_ARRAY_TEST,nil)
		expected = [0,1,2,3]
		t.array = expected
		string = t.display
		doc = Hpricot(string)
		#
		# Call t.display again
		doc1 = Hpricot(t.display)
		assert((doc/'/html/body/p').length == (doc1/'/html/body/p').length,"Multiple Calls to Display Create Duplicated Body")
	end

#===============================================================================
#Attribute test
#===============================================================================
	FILE_ATTRIBUTE_TEST = :file_attribute_test.to_s
	ATTRIBUTE_TEST_FILE = "#{TMP_TEMPLATE_PATH}/#{FILE_ATTRIBUTE_TEST}.html"
	def file_attribute_test
		File.open(ATTRIBUTE_TEST_FILE,File::CREAT|File::TRUNC|File::RDWR) {|f|
			f <<  contents_file_attribute_test
		}
	end
	def contents_file_attribute_test
		%Q{
			<html xmlns:t="http://www.nexopia.com/dev/template">
			<body>
			<p class="{source}" src="{source}"/>
			</body>
			</html>
		}
	end
	def test_attribute
		t = Template.instance(TMP_MODULE,FILE_ATTRIBUTE_TEST,self)
		t.source = 42
		doc = Hpricot(t.display)
		assert_equal(42,(doc/'/html/body/p').first['src'].to_i)
		assert_equal(42,(doc/'/html/body/p').first['class'].to_i)
	end


#===============================================================================
#Simple test
#===============================================================================

	FILE_SIMPLE_TEST = :file_simple_test.to_s
	SIMPLE_TEST_FILE = "#{TMP_TEMPLATE_PATH}/#{FILE_SIMPLE_TEST}.html"
	def file_simple_test
		File.open(SIMPLE_TEST_FILE,File::CREAT|File::TRUNC|File::RDWR) {|f|
			f <<  contents_file_simple_test
		}
	end
	def contents_file_simple_test
		%Q{
			<html xmlns:t="http://www.nexopia.com/dev/template">
			<body>
			<p>{simple}</p>
			<b>{pie}</b>
			<span>{bloop}</span>
			</body>
			</html>
		}
	end
	def test_simple
		t = Template.instance(TMP_MODULE,FILE_SIMPLE_TEST,nil)
		t.simple = "pie"
		t.pie = 'horse'
		t.bloop = 42
		doc = Hpricot(t.display)
		assert_equal("pie",(doc/'/html/body/p').innerHTML.strip)
		assert_equal("horse",(doc/'/html/body/b').innerHTML.strip)
		assert_equal(42,(doc/'/html/body/span').innerHTML.to_i)
	end

#===============================================================================
#Anchor/Linkable test
#===============================================================================

	FILE_A_TEST = :file_a_test.to_s
	A_TEST_FILE = "#{TMP_TEMPLATE_PATH}/#{FILE_A_TEST}.html"
	def file_a_test
		File.open(A_TEST_FILE,File::CREAT|File::TRUNC|File::RDWR) {|f|
			f <<  contents_file_a_test
		}
	end
	def contents_file_a_test
		%Q{
			<html xmlns:t="http://www.nexopia.com/dev/template">
			<body>
			<a id='onclick_added' href="/fish/soup"/>
			<a id='nothing' onclick="" />
			<a id='auto' t:id="object"/>
			</body>
			</html>
		}
	end

	class Linkable
		attr_accessor :name,:uri
		def to_uri
			self.uri
		end
		def uri_info
			[self.name,self.uri]
		end
	end

	def test_a_link
			me = Linkable.new
			me.name = "Joe Pieface"
			me.uri = "http://www/joe"

			t = Template.instance(TMP_MODULE,FILE_A_TEST,nil)
			t.object = me
			doc = Hpricot(t.display)
			auto_a_elm = (doc/'/html/body/a[@id=auto]').first
			assert_equal(me.uri,auto_a_elm['href'])
			assert_equal(me.name,auto_a_elm.innerHTML.strip)
	end

#===============================================================================
#Handler include test
#===============================================================================

	SUBREQUEST_TEST_HANDLER= :test_page_handler_subrequest
	FILE_INCLUDE_AREA_W_USER_TEST = :file_include_area_w_user_test.to_s
	INCLUDE_AREA_W_USER_TEST_FILE = "#{TMP_TEMPLATE_PATH}/#{FILE_INCLUDE_AREA_W_USER_TEST}.html"

	def file_include_area_w_user_test
		File.open(INCLUDE_AREA_W_USER_TEST_FILE,File::CREAT|File::TRUNC|File::RDWR) {|f|
			f <<  contents_file_include_area_test_w_user
		}

	end

	def contents_file_include_area_test_w_user
		%Q{
			<html xmlns:t="http://www.nexopia.com/dev/template">
			<body>
				<t:handler-include path="/#{SUBREQUEST_TEST_HANDLER}/user_area_0" area="User" user="me" />
			</body>
			</html>
		}
	end

#===============================================================================
#Handler include test
#===============================================================================

	FILE_INCLUDE_AREA_TEST = :file_include_area_test.to_s
	INCLUDE_AREA_TEST_FILE = "#{TMP_TEMPLATE_PATH}/#{FILE_INCLUDE_AREA_TEST}.html"
	def file_include_area_test
			File.open(INCLUDE_AREA_TEST_FILE,File::CREAT|File::TRUNC|File::RDWR) {|f|
				f <<  contents_file_include_area_test
			}
	end
	def contents_file_include_area_test
		%Q{
			<html xmlns:t="http://www.nexopia.com/dev/template">
			<body>
				<t:handler-include path="/#{SUBREQUEST_TEST_HANDLER}/user_area_0" area="User"  />
			</body>
			</html>
		}
	end

	def test_handler_include_with_area_and_user
		expected_page_path = "/#{SUBREQUEST_TEST_HANDLER}/user_area_0:Body"
		expected_page_area = :User
		#
		# Create some Mock objects for mitigating the need for dependancies
		#
		mock_handler = mock()
		mock_request = mock()
		mock_out = mock()
		mock_user = mock
		#
		# This is how they work in this context
		#
		mock_out.expects(:string).returns("").at_least_once
		mock_request.expects(:ok?).returns(true).at_least_once
		mock_request.expects(:out).returns(mock_out).at_least_once
		mock_request.expects(:reply).returns(mock_request).at_least_once
		mock_handler.expects(:subrequest).returns(mock_request).at_least_once.with(){|io,type,path,param,area,user|
			##
			## Collapse into either all true => true or any false => false
			[path == expected_page_path,
			 area == expected_page_area,
			 user == mock_user
			].inject {|memo,elm| memo = memo && elm}
		}

		t = Template.instance(TMP_MODULE,FILE_INCLUDE_AREA_W_USER_TEST,mock_handler)
		t.me = mock_user
		t.display # execute the template

		#
		# Change the Expectation for a testing without the user argument
		#
		mock_handler.expects(:subrequest).returns(mock_request).at_least_once.with(){|io,type,path,param,area,user|
			##
			## Collapse into either all true => true or any false => false
			[path == expected_page_path,
			 area == expected_page_area,
			# Nil user
			 user == nil
			].inject {|memo,elm| memo = memo && elm}
		}

		t = Template.instance(TMP_MODULE,FILE_INCLUDE_AREA_TEST,mock_handler)
		t.display # execute the template

	end

#===============================================================================
#Samifier test
#===============================================================================
	FILE_SAM_TEST = :file_sam_test.to_s
	SAM_TEST_FILE = "#{TMP_TEMPLATE_PATH}/#{FILE_SAM_TEST}.html"
	def file_sam_test
		#SamFactory.storage_dir = "./#{FILE_SAM_TEST}"
		#tSam = TemplateSamifier.new(contents_file_sam_test)
		#SamFactory.store_sam('en',tSam.to_hash)
		#File.open(SAM_TEST_FILE,File::CREAT|File::TRUNC|File::RDWR) {|f|
		#	f <<  	tSam.to_sam_template
		#}
	end
	def contents_file_sam_test
		%Q{
			<html xmlns:t="http://www.nexopia.com/dev/template">
			<body>
			<p>{simple}</p>
			<b>{pie}</b>
			<span>{bloop}</span>
			</body>
			</html>
		}

	end
	def test_sam
		#SamFactory.storage_dir = "./#{FILE_SAM_TEST}"
		#t = Template.instance(TMP_MODULE,FILE_SAM_TEST,nil)
		#t.simple = "Totem"
		#t.pie = 'horse'
		#t.bloop = 42
		#doc = Hpricot(t.display)
		#assert_equal("Totem",(doc/'/html/body/p').innerHTML.strip)
		#assert_equal("horse",(doc/'/html/body/b').innerHTML.strip)
		#assert_equal(42,(doc/'/html/body/span').innerHTML.to_i)
	end

#===============================================================================
#Conditional Attribute test
#===============================================================================
	FILE_COND_ATTR_TEST = :file_cond_attr_test.to_s
	COND_ATTR_TEST_FILE = "#{TMP_TEMPLATE_PATH}/#{FILE_COND_ATTR_TEST}.html"
	def file_cond_attr_test
		File.open(COND_ATTR_TEST_FILE,File::CREAT|File::TRUNC|File::RDWR) {|f|
			f <<  contents_file_cond_attr_test
		}
	end
	def contents_file_cond_attr_test
		%Q{
			<html xmlns:t="http://www.nexopia.com/dev/template">
			<body>
			<span cond:style="!var">Hi</span>
			<span cond:style="var">Hi</span>
			</body>
			</html>
		}
	end
	def test_cond_attr
		t = Template.instance(TMP_MODULE,FILE_COND_ATTR_TEST,nil)
		t.var = true;
		doc = Hpricot(t.display)
		assert_equal(nil,(doc/'/html/body/span')[0]['style'])
		assert_equal('style',(doc/'/html/body/span')[1]['style'])
	end

#===============================================================================
#Conditional Attribute test
#===============================================================================
	FILE_DEFINE_TEST = :file_define_test.to_s
	DEFINE_TEST_FILE = "#{TMP_TEMPLATE_PATH}/#{FILE_DEFINE_TEST}.html"
	def file_define_test
		File.open(DEFINE_TEST_FILE,File::CREAT|File::TRUNC|File::RDWR) {|f|
			f <<  contents_file_define_test
		}
	end
	def contents_file_define_test
		%Q{
			<html xmlns:t="http://www.nexopia.com/dev/template">
			<body>
			<t:define t:name="test_def" t:inner="inner" t:attrs="class1,style1">
				<div class="{class1}" style="{style1}">
					inner
				</div>
			</t:define>
			<t:test_def t:class1="test_class" t:style1="test_style">
				Hi!
			</t:test_def>
			</body>
			</html>
		}
	end
	def test_define
		t = Template.instance(TMP_MODULE,FILE_DEFINE_TEST,nil)
		doc = Hpricot(t.display)
		assert_equal("Hi!",(doc/'/html/body/div').innerHTML.strip)
		assert_equal("test_class",(doc/'/html/body/div').first['class'])
		assert_equal("test_style",(doc/'/html/body/div').first['style'])
	end


	def test_inline
		assert_nothing_raised {
			Template.inline "core", "test_inline_template", "<t:empty>Hello, world!</t:empty>"
		}
		t = nil
		assert_nothing_raised {
			t = Template.instance("core", "test_inline_template", nil)
		}
		assert_equal("Hello, world!", t.display)
	end
end
