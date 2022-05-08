# tests Enum
lib_require :Core, "data_structures/enum";
lib_require :Devutils, 'quiz'

class TestEnum < Quiz
	def setup
		@testenum = Enum.new(:test1, [:test1, :test2, :test3]);
	end

	def test_validate()
		assert_raise(RuntimeError) { @testenum.symbol = :notpresent; }
		assert_nothing_raised()    { @testenum.symbol = :test1; }
		assert_nothing_raised()    {
			@testenum.add_symbol(:test4);
			@testenum.symbol = :test4;
		}
		assert_raise(RuntimeError) {
			@testenum.delete_symbol(:test4);
		}
		assert_nothing_raised()    {
			@testenum.symbol = :test1;
			@testenum.delete_symbol(:test4);
		}
		assert_raise(RuntimeError) { @testenum.symbol = :test4; }
	end

	def test_equality()
		assert_equal(Enum.new(:test1, [:test1]), Enum.new(:test1, [:test1]));
		assert_not_equal(Enum.new(:test1, [:test1]), Enum.new(:test2, [:test2]));
	end

	def test_create
		assert_raise(RuntimeError) {Enum.new(:symbol1, [:sym2, :sym3])}
		assert_nothing_raised() {Enum.new(:symbol1, [:symbol1, :sym2, :sym3])}
		assert_nothing_raised() {Enum.new(:symbol1, [:symbol1, :sym2, :sym3])}
		assert_nothing_raised() {Enum.new(:symbol1, [:symbol1])}
	end

	def test_assign
		enum = Enum.new(:sym1, [:sym1, :sym2]);
		assert_raise(RuntimeError) {enum.symbol = :sym3}
		assert_nothing_raised() {enum.symbol = :sym2}
	end

	def test_equality2
		e1 = Enum.new(:sym1, [:sym1]);
		e2 = Enum.new(:sym1, [:sym1, :sym2]);
		e3 = Enum.new(:sym1, [:sym1, :sym2]);
		e4 = Enum.new(:sym2, [:sym1, :sym2]);
		assert(e1 == e2)
		assert(e1 != e4)
		assert(e1 === e2)
		assert(!(e1 === e4))
		assert(!(1 === e4))
		assert(!e1.eql?(e2))
		assert(e2.eql?(e3))
		assert(!e2.equal?(e3))
	end
end

