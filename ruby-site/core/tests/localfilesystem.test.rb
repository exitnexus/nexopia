lib_require :Core, 'filesystem/local_file_system';
lib_require :Devutils, 'quiz'
require 'stringio'

def recursive_delete(path)
	if (File.directory?(path))
		Dir.foreach(path) { |file|
			if (file == '.' || file == '..')
				next;
			elsif (File.directory?(path +'/'+ file))
				recursive_delete(path +'/'+ file);
				Dir.unlink(path +'/'+ file);
			else
				File.unlink(path +'/'+ file);
			end
		}
		Dir.unlink(path);
	else
		File.unlink(path);
	end
end

class TestLocalFileSystem < Quiz
	TEST_PATH = '/localfilesystemtest';
	TEST_FILE1 = "test_file1"
	TEST_FILE2 = "test_file2"
	TEST_FILE3 = "test_file3"

	def setup
		@tmp = '.'
		for dir in [ENV['TMPDIR'], ENV['TMP'], ENV['TEMP'],
			ENV['USERPROFILE'],  '/tmp']
			if dir and File.directory?(dir) and File.writable?(dir)
				@tmp = dir
				break
			end
		end
		if (File.exist?(@tmp+TEST_PATH))
			recursive_delete(@tmp+TEST_PATH)
		end
		Dir.mkdir(@tmp + TEST_PATH);
		test_file = File.new(@tmp+TEST_PATH+'/'+TEST_FILE1, File::CREAT|File::RDWR)
		test_file.write("This is a sample file.")
		test_file.flush();
		@lfs = LocalFileSystem.new(@tmp + TEST_PATH)
	end

	def teardown
		recursive_delete(@tmp+TEST_PATH)
	end

	def test_read
		io = @lfs.read(TEST_FILE1, FileType, Hash.new);
		assert(io.read == "This is a sample file.");
	end

	def test_write
		@lfs.write(StringIO.new("This is a sample write string"), TEST_FILE2, FileType, Hash.new);
		test_file = File.new(@tmp+TEST_PATH+'/'+TEST_FILE2)
		assert(test_file.read == "This is a sample write string");
	end
	def test_delete
		@lfs.delete(TEST_FILE1, FileType, Hash.new);
		assert(!File.exist?(@tmp+TEST_PATH+'/'+TEST_FILE2))
	end

end
