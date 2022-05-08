lib_require :Devutils, 'quiz'
lib_require :Core, 'storable/user_content'
class TestUserContent < Quiz
	class Foo
		extend UserContent
		attr_accessor :foo, :bar, :content, :content_no_nl2br, :content_foo, :content_not_foo
		user_content(:content)
		user_content(:content_no_nl2br, :nl2br => false)
		user_content(:content_foo, :nl2br => :foo)
		user_content(:content_not_foo, :nl2br => lambda { !self.foo })
	end
	
	def setup
		@foo = Foo.new
		@foo.foo = true
		@foo.content = "This is a string\nwith a newline"
		@foo.content_foo = "This is a string\nwith a newline"
		@foo.content_not_foo = "This is a string\nwith a newline"
		@foo.content_no_nl2br = "This is a string\nwith a newline"
	end
	
	def teardown
	end
	
	def test_content
		assert_equal(@foo.content.gsub("\n", "<br/>"), @foo.content.parsed)
		assert_equal(@foo.content_no_nl2br, @foo.content_no_nl2br.parsed)
		assert_equal(@foo.content_foo.gsub("\n", "<br/>"), @foo.content_foo.parsed)
		assert_equal(@foo.content_not_foo, @foo.content_not_foo.parsed)
		@foo.foo = false
		assert_equal(@foo.content_not_foo.gsub("\n", "<br/>"), @foo.content_not_foo.parsed)
		assert_equal(@foo.content_foo, @foo.content_foo.parsed)
	end	
end

