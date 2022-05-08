lib_require :Core, "bufferio";
lib_require :Devutils, 'quiz'

class TestBufferIO < Quiz
	def setup()
		@io = StringIO.new();
		@io.extend(BufferIO);
		@io.initialize_buffer { };
	end

	def test_raw()
		assert_nothing_raised { @io.print("hello"); }
		assert_equal("", @io.string);
		assert_nothing_raised { @io.raw_print("hello"); }
		assert_equal("hello", @io.string);
	end

	def test_flush()
		assert_nothing_raised { @io.print("hello"); }
		assert_equal("", @io.string);
		assert_nothing_raised { @io.end_buffering(); }
		assert_equal("hello", @io.string);
	end

	def test_clear()
		assert_nothing_raised { @io.print("hello"); }
		assert_equal("", @io.string);
		assert_nothing_raised { @io.clear_buffer(); @io.end_buffering(); }
		assert_equal("", @io.string);
	end

	def test_capture()
		assert_nothing_raised { @io.print("hello"); }
		assert_equal("", @io.string);
		inner_io = StringIO.new();
		assert_nothing_raised {
			@io.capture_output(inner_io) {
				@io.print("goodbye");
			}
		}
		assert_equal("goodbye", inner_io.string);
		assert_equal("", @io.string);
		assert_nothing_raised { @io.end_buffering(); }
		assert_equal("hello", @io.string);
	end

	def test_toggle()
		assert_nothing_raised { @io.print("hello"); }
		assert_equal("", @io.string);
		assert_nothing_raised { @io.buffer = false; }
		assert_equal("hello", @io.string);
		assert_nothing_raised { @io.print("goodbye"); }
		assert_equal("hellogoodbye", @io.string);
		assert_nothing_raised { @io.buffer = true; }
		assert_nothing_raised { @io.print("whatever"); }
		assert_equal("hellogoodbye", @io.string);
		assert_nothing_raised { @io.end_buffering(); }
		assert_equal("hellogoodbyewhatever", @io.string);
	end
end
