# tests TypeSafeHash
lib_require :Core, "typesafehash";
lib_require :Devutils, 'quiz'

class TestTypeSafeHash < Quiz
	#####
	##### Commented out tests pass, but do not show as passing because of MatchData not having a comparison operator.
	#####
	def setup
		@basehash = {0 => "hello", 1 => "1234", 2 => "-234", 3 =>"12.4324", 4 => "-234.324", 5 => "324.234432.342"};
		@realvals = ["hello", 1234, -234, 12.4324, -234.324, nil];
		@arrhash = {0 => "sdfjasld", 1 => ['1', '2', 'x', '3'], 2 => [['1', '2'], ['3', '4'], ['x', 'y'], ['z', '1']] };
		@tshash = TypeSafeHash.new(@basehash);
		@tsarrhash = TypeSafeHash.new(@arrhash);
	end

	def test_simple
		assert_equal(@basehash, @tshash.to_hash);
		assert_equal(TypeSafeHash.new(@basehash), @tshash);
		assert_equal(false, @tshash.empty?);
		assert_equal(true, TypeSafeHash.new({}).empty?);
		assert_equal(true, @tshash.has_key?(0));
		assert_equal(false, @tshash.has_key?(:fsdaf));
		assert_equal([0, 1, 2, 3, 4, 5], @tshash.keys().sort());
		assert_equal(6, @tshash.length());
		assert_raise(ArgumentError) { @tshash[0]; };
	end

	def test_validate_string
		assert_equal(@realvals[0], @tshash[0, String]);
		#assert_equal(/el/.match(@realvals[0]), @tshash[0, /el/]);
		assert_nil(@tshash[0, /x/]);
	end

	def test_validate_int
		assert_equal(@realvals[1], @tshash[1, Integer]);
		assert_equal(@realvals[1].to_f, @tshash[1, Float]);
		assert_equal(@realvals[1].to_s, @tshash[1, String]);
		assert_nil(@tshash[1, /x/]);

		assert_equal(@realvals[2], @tshash[2, Integer]);
		assert_equal(@realvals[2].to_f, @tshash[2, Float]);
	end

	def test_float
		assert_nil(@tshash[3, Integer]);
		assert_equal(@realvals[3], @tshash[3, Float]);

		assert_nil(@tshash[4, Integer]);
		assert_equal(@realvals[4], @tshash[4, Float]);

		assert_nil(@tshash[5, Integer]);
		assert_nil(@tshash[5, Float]);
	end

	def test_array
		assert_nil(@tsarrhash[0, [String]]);
		assert_equal(1, @tsarrhash[1, Integer]);
		assert_equal([1, 2, 3], @tsarrhash[1, [Integer]]);
		assert_equal(@arrhash[1], @tsarrhash[1, [String]]);
		#assert_equal([/x/.match('x')], @tsarrhash[1, [/x/]]);
		assert_equal([[1, 2], [3, 4], [1]], @tsarrhash[2, [[Integer]]]);
		assert_equal(@arrhash[2], @tsarrhash[2, [[String]]]);
		#assert_equal([[/[a-z]/.match('x'), /[a-z]/.match('y')], [/[a-z]/.match('z')]], @tsarrhash[2, [[/[a-z]/]]]);
	end
end
