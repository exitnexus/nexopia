lib_require :Core, "memcache";
lib_require :Devutils, 'quiz'

class TestMemcache < Quiz
	def test_legacy_memcache_tests
		output = `core/tests/lib/test.rb`
		puts output
		assert(output =~ /0 failures/)
		assert(output =~ /0 errors/)
	end
end
