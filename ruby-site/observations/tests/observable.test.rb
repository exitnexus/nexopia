lib_require :Observations, 'observable'
lib_require :Devutils, 'quiz'

module Observations
	class Foo
		include(Observable)
	end
	
	class Bar
		include(Observable)
		OBSERVABLE_NAME = "xyz"
		OBSERVABLE_DEFAULT = false
	end
	
	
	class TestObservable < Quiz
		def setup
		end
		
		def teardown
		end
		
		def test_include
			assert_respond_to(Foo.new, :display_message)
		end
		
		def test_classes
			assert_nothing_raised { Observable.classes }
			assert(Observable.classes.include?(Foo))
			assert(Observable.classes.include?(Bar))
			assert_raise(NoMethodError) { Foo.classes }
		end
		
		def test_observable_name
			assert_equal(Foo.name, Foo.observable_name)
			assert_equal(Bar::OBSERVABLE_NAME, Bar.observable_name)
		end
		
		def test_observable_default
			assert_equal(Observable::OBSERVABLE_DEFAULT, Foo.observable_default)
			assert_equal(Bar::OBSERVABLE_DEFAULT, Bar.observable_default)
		end
	end
end