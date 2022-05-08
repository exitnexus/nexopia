# tests Storable
lib_require :Wiki, "wiki";
require 'mocha'
lib_require :Devutils, 'quiz'

class TestWiki < Quiz
	def setup
	end
	def teardown
	end

	def test_clean_addr
		assert_equal("/asdf/asdf", Wiki.clean_addr("/asdf/asdf/"));
		assert_equal("/asdf/asdf", Wiki.clean_addr("/asdf//asdf/"));
		assert_equal("/asdf/asdf", Wiki.clean_addr("/asdf/a+sdf/"));
		assert_equal("/asdf/asdf", Wiki.clean_addr("asdf/asdf/"));
	end

	def test_get_page_name
		assert_equal("asdf", Wiki.parse_page_name("/asdf/asdf/asdf"));
		assert_equal("asdf", Wiki.parse_page_name("/asdf/asdf"));
		assert_equal("asdf", Wiki.parse_page_name("/asdf"));
	end

	def test_get_parent_addr
		assert_equal("/asdf/asdf", Wiki.parse_parent_addr("/asdf/asdf/asdf"));
		assert_equal("/asdf", Wiki.parse_parent_addr("/asdf/asdf"));
		assert_equal("", Wiki.parse_parent_addr("/asdf"));
	end

	def test_set
	end

	def test_delete
	end

	def test_children
	end

	def test_history
	end

	def test_wiki
		header = mock();
		header.stubs(:name).returns("name")
		header.stubs(:id).returns(1)
		header.stubs(:maxrev).returns(1)
		header.stubs(:activerev).returns(1)
		parent = Wiki.new(header)
		child = Wiki.new(header, parent)
	end
	

end
