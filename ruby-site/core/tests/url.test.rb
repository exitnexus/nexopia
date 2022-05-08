lib_require :Core, "url";
lib_require :Devutils, 'quiz'

class TestUrl < Quiz
	def test_url()
		# Simple URL creation
		assert_equal("blah/blah/blorp/bloo", url(:blah,:blah,:blorp,:bloo));
		assert_equal("blah/blah/blorp/bloo", url(:blah)/:blah/:blorp/:bloo);
		assert_equal("/blah/blah/blorp/bloo", url/:blah/:blah/:blorp/:bloo);
	end

	def test_url_encoded()
		# URLs with characters that need escaping in them
		assert_equal("blah+blorp%2Fgomp", urlencode("blah blorp/gomp"));
		assert_equal("/#{urlencode 'blah blorp'}/#{urlencode 'wat/woo'}", url/'blah blorp'/'wat/woo');
	end

	def test_url_params()
		# URLs with params passed in
		assert_equal("blah+blorp%3Dgomp", urlencode("blah blorp=gomp"));
		assert_equal("/testing?test=what&test2=stuff", url/:testing & {:test=>:what} & {:test2=>:stuff});
		assert_equal("/testing/stuff?test=what&test2=stuff", (url/:testing & {:test=>:what}) / :stuff & {:test2=>:stuff});
		assert_equal("/testing?test+stuff=what%3Dthe%3Dhell", url/:testing&{'test stuff'=>'what=the=hell'});
	end

	def test_to_url()
		# calling to_url on a String or on a UrlBuilder should result in no changes
		# to the actual string.
		assert_equal("/testing/stuff?test=what&test2=stuff", "/testing/stuff?test=what&test2=stuff".to_url);
		assert_equal("/testing/stuff?test=what&test2=stuff", ((url/:testing & {:test=>:what}) / :stuff & {:test2=>:stuff}).to_url);
	end
end
