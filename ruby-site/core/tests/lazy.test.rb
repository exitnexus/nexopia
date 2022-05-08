lib_require :Core, 'lazy'
lib_require :Devutils, 'quiz'

class TestLazy < Quiz

	def setup
		return;
	end

	def teardown
		return;
	end

	def test_evaluated
		a = promise {"test"}
		assert(!evaluated?(a))
		assert(a == "test");
		assert(evaluated?(a))
	end
end
