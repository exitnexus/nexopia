# tests Cacheable
lib_require :Core, 'filesystem/mogile_file_system';


class TestMogileFileSystem < Quiz
	def setup
		@some_content = "You shall not pass!"
		@mogilefs = MogileFileSystem.new(['mogilefs:6001'])
		@key = 'TestMogileFileSystem'
		@class = 'userpics'
		@mogilefs.delete(@key)
	end

	def teardown
	end

	def test_write_read_delete
		assert_nil(@mogilefs.read(@key))
		@mogilefs.write(StringIO.new(@some_content), @key, @class)
		assert_equal(@some_content, @mogilefs.read(@key).read)
		@mogilefs.delete(@key)
		assert_nil(@mogilefs.read(@key))
	end
end