# tests HandlerTreeNode
lib_require :Core, "handlertree";
lib_require :Devutils, 'quiz'

class TestHandlerTree < Quiz
	def capture(val)
		return HandlerTreeNode::CaptureInput.new(val);
	end

	def setup
		@handlertree = HandlerTreeNode.new();
	end

	def test_retrieve()
		assert_nothing_raised { @handlertree.add_node([1, 2, 3], 'test1'); }
		assert_nothing_raised { @handlertree.add_node([1, 2, 4], 'test2'); }
		assert_nothing_raised { @handlertree.add_node([1, 2], 'test3'); }
		assert_nothing_raised { @handlertree.add_node([1, 5], 'test4'); }

		assert_equal([ [], 'test1', [] ], @handlertree.find_node([1, 2, 3]));
		assert_equal([ [], 'test2', [] ], @handlertree.find_node([1, 2, 4]));
		assert_equal([ [], 'test3', [] ], @handlertree.find_node([1, 2]));
		assert_equal([ [5], 'test3', [] ], @handlertree.find_node([1, 2, 5]));
		assert_equal([ [], 'test4', [] ], @handlertree.find_node([1, 5]));
		assert_equal([ [2], nil, [] ], @handlertree.find_node([2]));
	end

	def test_capture()
		assert_nothing_raised { @handlertree.add_node([capture(Integer), "whomever", "whatever"], 'test9'); }
		assert_nothing_raised { @handlertree.add_node([capture(Integer), "whatever"], 'test8'); }

		assert_equal([ [], 'test8', [1] ], @handlertree.find_node(['1', 'whatever']));
		assert_equal([ [], 'test9', [1] ], @handlertree.find_node(['1', 'whomever', 'whatever']));
	end

	def test_regex_capture()
		regex1 = /^[hH][eE][lL][lL][oO]$/;
		regex2 = /^[hH][eE][lL][lL]$/;
		assert_nothing_raised { @handlertree.add_node([capture(regex1), capture(String)], 'test5'); }
		assert_nothing_raised { @handlertree.add_node([capture(regex2), capture(Integer)], 'test6'); }

		# THESE TESTS WERE COMMENTED OUT BECAUSE MatchData DOESN'T COMPARE TO ITSELF.
		# SOMEHOW THIS NEEDS TO BE FIXED TO MAKE THE TEST WORK.
		#assert_equal([ [], 'test5', [regex1.match('heLLo'), 'world'] ], @handlertree.find_node(['heLLo', 'world']));
		#assert_equal([ [], 'test6', [regex2.match('heLL'), 1] ], @handlertree.find_node(['heLL', '1']));
		#assert_equal([ ['x'], 'test6', [regex2.match('heLL'), 1] ], @handlertree.find_node(['heLL', '1', 'x']));
		assert_equal([ ['heLL', 'sadfds'], nil, [] ], @handlertree.find_node(['heLL', 'sadfds']));
	end

	def test_regex()
		assert_nothing_raised { @handlertree.add_node([/^hello$/, 1], 'test7'); }
		assert_equal([ [], 'test7', [] ], @handlertree.find_node(['hello', '1']));
		assert_equal([ ['hello', '324'], nil, [] ], @handlertree.find_node(['hello', '324']));
	end
end


