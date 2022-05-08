require_gem 'mocha'
require 'stubba'
require 'mocha'

lib_require :Streams, 'music_news'
lib_require :Devutils, 'quiz'

class TestMusicNews < Quiz
	def setup
		return;
	end
	
	def teardown
		return;
	end
	
	#no functionality outside of the basic storable functionality currently exists in band entry.
	#Update when there is some.
	def test_placeholder
		assert(true);
	end
end