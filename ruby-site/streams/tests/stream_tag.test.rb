require_gem 'mocha'
require 'stubba'
require 'mocha'

lib_require :Streams, 'stream_tag'
lib_require :Devutils, 'quiz'

class TestStreamTag < Quiz
	def setup
		@test_tag = StreamTag.new();
	end
	
	def teardown
		return;
	end
	
	def test_entry_ids
		mock_tag = mock();
		mock_tag.expects(:entryid).times(5).returns(0,1,2,3,4);
		mock_array = [mock_tag,mock_tag,mock_tag,mock_tag,mock_tag]
		@test_tag.expects(:entry_tags).returns(mock_array);
		@test_tag.entry_ids.each_with_index {|id,index|
			assert_equal(index, id);
		}
	end
	
	def test_entries
		mock_tag = mock();
		mock_array = [mock_tag,mock_tag,mock_tag,mock_tag,mock_tag]
		@test_tag.expects(:entry_tags).returns(mock_array);
		mock_tag.expects(:entryid).times(5).returns(:entryid);
		mock_stream_entry = mock();
		mock_stream_entry.expects(:date).at_least_once.returns(0)
		mock_stream_entry_array = [mock_stream_entry,mock_stream_entry,mock_stream_entry,mock_stream_entry,mock_stream_entry]
		StreamEntry.expects(:find).with([:entryid, :entryid, :entryid, :entryid, :entryid], {:order => 'date DESC'}).returns(mock_stream_entry_array)
		mock_stream_entry.expects(:entry).times(5).returns(0,1,2,3,4)
		@test_tag.entries.each_with_index {|id,index|
			assert_equal(index, id);
		}
	end
	
	def test_find_items_by_name
		mock_stream_tag = mock();
		mock_stream_tag.expects(:entries).times(2).returns(:an_array_of_storable_objects);
		StreamTag.expects(:find).returns(mock_stream_tag);
		
		assert_equal(:an_array_of_storable_objects, StreamTag.find_items_by_name("some tag name"))
		assert_equal(:an_array_of_storable_objects, StreamTag.find_items_by_name(mock_stream_tag))
	end
end