=begin
	
require 'mocha'
require 'stubba'
lib_require :Core, 'acts_as_uri','users/pics'
lib_require :Devutils, 'quiz'



class TestActsAsURI < Quiz

	class TestActs
		include Acts::As::URI
	end


	def _test_convert_type
		a = :blah
		b = "String"
		c = [a,b]
		a_out = TestActs._convert_type(a)
		b_out = TestActs._convert_type(b)
		c_out = TestActs._convert_type(c)

		assert_equal(a,a_out)
		assert_equal("'#{b}'",b_out)

		assert_equal("[blah,'String']",c_out)

	end
	def test_acts_as_uri_default

		#
		# Setup the Mock class to act as uri
		#
		TestActs.class_eval {

			acts_as_uri({})
		}
		#
		t = TestActs.new

		#
		# Check for the expected output
		#
		expected = "/"
		# Test Method based argument for the file uri
		assert_equal(expected,t.to_uri)
	end
	def test_acts_as_uri_simple

		TestActs.any_instance.stubs(:test_id).returns(10)
		#
		# Setup the Mock class to act as uri
		#

		TestActs.class_eval {
			acts_as_uri(:uri_spec => ['profile',:test_id])
		}
		#
		t = TestActs.new
		expected_uri = '/profile/10'
		assert_equal(expected_uri,t.to_uri)
	end
	def test_acts_as_uri_default

		#
		# Give the Mock class some methods
		#
		TestActs.any_instance.stubs(:our_group).returns("samurai")
		TestActs.any_instance.stubs(:test_id).returns("baked_red_pepper_stew")
		#
		# Setup the Mock class to act as uri
		#
		TestActs.class_eval {
			acts_as_uri(:uri_spec => [:our_group,:test_id],:protocol => 'fish:')
		}
		#
		t = TestActs.new

		#
		# Check for the expected output
		#
		expected = "fish://samurai/baked_red_pepper_stew"
		# Test Method based argument for the file uri
		assert_equal(expected,t.to_uri)
	end

end

class TestActsAsFileURI < Test::Unit::TestCase

	class TestActs
	end
	def test_to_uri_on_pics
		userid = 5
		picid = 10
		file_group = 'userpics'
		extention = "jpg"
		filegroup = 'userpics'
		expected = "#{$site.image_url}/#{file_group}/#{userid/1000}/#{userid}/#{picid}.#{extention}"
		#
		# To abstract away the actual pic id stub the method
		#
		Pics.any_instance.stubs(:file_id).returns(picid)
		a_pic = Pics.all(userid).first


		assert_equal(expected, a_pic.to_uri)
		assert_equal(a_pic.description,a_pic.uri_info[0])
	end


	def test_acts_as_file_uri
		site_address = "MOUSE"
		extention="JPG"

		#
		# Give the Mock class some methods
		#
		TestActs.any_instance.stubs(:our_group).returns("samurai")
		TestActs.any_instance.stubs(:test_id).returns("baked_red_pepper_stew")

		#
		# Setup the Mock class to act as uri
		#
		TestActs.class_eval {
			include Acts::As::FileURI
			acts_as_file_uri(:site_address =>site_address,:file_group => :our_group , :file_id => :test_id,:extention => extention)
		}
		#
		t = TestActs.new

		#
		# Check for the expected output
		#

		# Test Method based argument for the file uri
		assert_equal(t.our_group,t.file_group)
		assert_equal(t.test_id,t.file_id)

		# Test String base argument for the file uri
		#
		assert_equal(site_address,t.site_address)
		assert_equal(extention,t.extention)

	end
end
=end
