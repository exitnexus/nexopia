lib_require :Core, 'typeid'
lib_require :Devutils, 'quiz'

class TestFooTypeID
	extend TypeID;
end

class TestTypeid < Quiz
	
	def setup
	end
	
	def teardown
	end
	
	def test_get_id
		assert_equal(TestFooTypeID.typeid, TypeID.get_typeid("testfootypeid"));
		assert(TypeID.get_typeid("testfootypeid", false).nil?);
		assert_equal(TestFooTypeID.typeid, TypeID.get_typeid("TestFooTypeID"));
	end
	
end