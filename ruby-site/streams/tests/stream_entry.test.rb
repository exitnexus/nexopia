require_gem 'mocha'
require 'stubba'
require 'mocha'

lib_require :Streams, 'stream_entry'
lib_require :Devutils, 'quiz'

class TestStreamEntry < Quiz
	def setup
		@test_stream_entry = StreamEntry.new();
	end
	
	def teardown
		return;
	end
	
	def test_entry
		@test_stream_entry.typeid = :typeid; #just something so it has the standard storable class assumptions
		@test_stream_entry.primaryid = :primaryid;
		@test_stream_entry.secondaryid = :secondaryid;
		
		mock_storable_class = mock();
		TypeID.expects(:get_class).with(:typeid).times(2).returns(mock_storable_class)

		mock_storable_class.expects(:indexes).at_least_once.returns({"PRIMARY".to_sym => [:column1]});
		#This find expectation is subject to breaking if the code gets refactored.
		mock_storable_class.expects(:find).with(:primaryid, :promise, :first).returns(:storable_object);

		assert_equal(:storable_object, @test_stream_entry.entry);

		mock_storable_class.expects(:indexes).at_least_once.returns({"PRIMARY".to_sym => [:column1, :column2]});
		#This find expectation is subject to breaking if the code gets refactored.
		mock_storable_class.expects(:find).with(:primaryid, :secondaryid, :promise, :first).returns(:storable_object);

		assert_equal(:storable_object, @test_stream_entry.entry);
	end
end