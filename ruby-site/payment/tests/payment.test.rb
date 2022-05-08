lib_require :Payment, 'payment'
lib_require :Devutils, 'quiz'

class TestPayment < Quiz
	def setup
		return;
	end

	def teardown
		return;
	end

	def test_creation
		p = Payment.new();
		assert_nothing_raised {p.type = :MailPayment}
		assert_kind_of(MailPayment, p);
		p = Payment.new();
		assert_nothing_raised {p.type = "MailPayment"}
		assert_kind_of(MailPayment, p);
		p = Payment.new();
		assert_nothing_raised {p.type = MailPayment}
		assert_kind_of(MailPayment, p);
	end
	
	def test_user
		p = Payment.new;
		p.uid = 200; #LogicWolfe's ID
		assert_not_equal(nil, p.user);
	end
	
	def test_pay
		p = Payment.new;
		p.amount_pending = 100;
		p.pay(25);
		assert_equal(25, p.amount_approved);
		assert_equal(75, p.amount_pending);
		assert_raise(NoMethodError) {p.pay(75)} #attempt to complete the purchase with no basket.
	end
end
