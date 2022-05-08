lib_require :Core, "cache";
lib_require :Devutils, 'quiz'

class TestCache < Quiz
	def setup
		@testCacheA = Cache.new()
		@testCacheB = Cache.new()
	end

	def test_get
            time = 1

            sleep(2 * time) # Cleans Memcache
            assert(@testCacheA.get("value", time) {"A"} == "A") # Hash/Memcache empty
            assert(@testCacheA.get("value", time) {"B"} == "A") # Hash value

            sleep(2 * time) # Cleans Memcache
            assert(@testCacheA.get("value", time) {"B"} != "A")  # Hash/Memcache empty
	end

	def test_context()
		@testCacheA.use_context({}) {
			assert_equal('stuff', @testCacheA.get(:test, :context) {'stuff'} );
			assert_nothing_raised { @testCacheA.get(:test, :context) {raise SiteError;} }
			assert_equal('stuff', @testCacheA.get(:test, :context) {'what'} );
		}
		@testCacheA.use_context({}) {
			assert_raise(SiteError) { @testCacheA.get(:test, :context) { raise SiteError } }
		}
	end
end
