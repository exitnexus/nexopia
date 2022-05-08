require_gem 'mocha'
require 'stubba'
require 'mocha'

lib_require :Streams, 'entry_tag'
lib_require :Devutils, 'quiz'

class TestEntryTag < Quiz
	def setup
		return;
	end
	
	def teardown
		return;
	end
	
	def test_initialize
		assert_nothing_raised {EntryTag.new}
	end
end