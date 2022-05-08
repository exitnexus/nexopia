describe RAP do
	def run_php(code)
		php_file = File.open('phptest.php', "w")
		php_file.chmod(0700)
		begin
			php_file.puts(code)
			php_file.flush()
			return RapModule::php.exec(File.expand_path(php_file.path))
		ensure
			File::delete php_file.path
		end
	end
	
	class RapTestObj
		attr_reader :i
		attr_writer :i

		def initialize
			@i = 0
		end
	end

	before do
		@test = RapTestObj.new
	end


	it "should have RAP defined" do
		defined?(RAP).should == 'constant'
	end
	
	it "should have RapModule defined" do
		defined?(RapModule).should == "constant"
	end
	
	it "should have a php instance" do
		defined?(RapModule::php).should == "method"
		RapModule::php.should_not == nil
	end
	
	it "should give an About page" do
		# So long as it gives us something useful
		RAP::about.should =~ /<html><head><title>RAP/
	end
	
	it "should be able to execute Php" do
		php = <<-end_php
		<?
			echo 'hello, world';
		end_php
		result = run_php(php)
		result[:errors].should == ""
		result[:output].lstrip().should == "hello, world"
	end

	it "should be able to register a named Ruby object" do
		@test.i.should == 0
		RapModule::php.register_object(@test, "test")
		php = <<-end_php
		<?
			$test->i++;
		end_php
		result = run_php(php)
		result[:errors].should == ""
		@test.i.should == 1
	end

	it "should be able to pass a NULL" do
		@test.i = nil
		RapModule::php.register_object(@test, "test")
		php = <<-end_php
		<?
			if (is_null($test->i)) {
				echo 'null';
			} else {
				echo 'not null';
			}
		end_php
		result = run_php(php)
		result[:errors].should == ""
		result[:output].lstrip().should == "null"
	end

	it "should be able to pass false" do
		@test.i = false
		RapModule::php.register_object(@test, "test")
		php = <<-end_php
		<?
			if ($test->i) {
				echo 'true';
			} else {
				echo 'false';
			}
		end_php
		result = run_php(php)
		result[:errors].should == ""
		result[:output].lstrip().should == "false"
	end

	it "should be able to pass true" do
		@test.i = true
		RapModule::php.register_object(@test, "test")
		php = <<-end_php
		<?
			if ($test->i) {
				echo 'true';
			} else {
				echo 'false';
			}
		end_php
		result = run_php(php)
		result[:errors].should == ""
		result[:output].lstrip().should == "true"
	end

	it "should be able to pass a double" do
		@test.i = 0.125
		RapModule::php.register_object(@test, "test")
		php = <<-end_php
		<?
			if (($test->i > 0.124) && ($test->i < 0.126)) {
				echo 'true';
			} else {
				echo $test->i;
			}
		end_php
		result = run_php(php)
		result[:errors].should == ""
		result[:output].lstrip().should == "true"
	end

	it "should be able to pass an integer" do
		@test.i = 42
		@test.i.kind_of?(Integer).should == true
		RapModule::php.register_object(@test, "test")
		php = <<-end_php
		<?
			if (($test->i == 42)) {
				echo 'true';
			} else {
				echo $test->i;
			}
		end_php
		result = run_php(php)
		result[:errors].should == ""
		result[:output].lstrip().should == "true"
	end

	it "should be able to pass a fixnum" do
		@test.i = (1 << 30) - 1 # 1073741823
		@test.i.kind_of?(Fixnum).should == true
		@test.i.kind_of?(Bignum).should == false
		RapModule::php.register_object(@test, "test")
		php = <<-end_php
		<?
			if (($test->i == 1073741823)) {
				echo 'true';
			} else {
				echo $test->i;
			}
		end_php
		result = run_php(php)
		result[:errors].should == ""
		result[:output].lstrip().should == "true"
	end

	it "should be able to pass a bignum as int" do
		@test.i = 1 << 30 # 1073741824
		@test.i.kind_of?(Fixnum).should == false
		@test.i.kind_of?(Bignum).should == true
		RapModule::php.register_object(@test, "test")
		php = <<-end_php
		<?
			if (($test->i == 1073741824)) {
				echo 'true';
			} else {
				echo $test->i;
			}
		end_php
		result = run_php(php)
		result[:errors].should == ""
		result[:output].lstrip().should == "true"
	end

	it "should be able to pass a bignum as string" do
		@test.i = 1 << 33 # 8589934592
		@test.i.kind_of?(Fixnum).should == false
		@test.i.kind_of?(Bignum).should == true
		RapModule::php.register_object(@test, "test")
		php = <<-end_php
		<?
			if (($test->i == "8589934592")) {
				echo 'true';
			} else {
				echo $test->i;
			}
		end_php
		result = run_php(php)
		result[:errors].should == ""
		result[:output].lstrip().should == "true"
	end
	it "should be able to pass a hash table" do
		@test.i = { "first" => 'a', "second" => 'b' }
		@test.i.kind_of?(Hash).should == true
		RapModule::php.register_object(@test, "test")
		php = <<-end_php
		<?
			$success = 0;
			if ($test->i['first'] == 'a')
				$success += 1 << 0;
			if ($test->i['second'] == 'b')
				$success += 1 << 1;
			echo $success;
		end_php
		result = run_php(php)
		result[:errors].should == ""
		result[:output].lstrip().should == "3"
	end

	it "should be able to pass an array" do
		@test.i = [ "first", "second" ]
		@test.i.kind_of?(Array).should == true
		RapModule::php.register_object(@test, "test")
		php = <<-end_php
		<?
			$success = 0;
			if ($test->i[0] == "first")
				$success += 1 << 0;
			if ($test->i[1] == "second")
				$success += 1 << 1;
			echo $success;
		end_php
		result = run_php(php)
		result[:errors].should == ""
		result[:output].lstrip().should == "3"
	end

	it "should pass array keys as ints" do
		@test.i = [ "first", "second" ]
		@test.i.kind_of?(Array).should == true
		RapModule::php.register_object(@test, "test")
		php = <<-end_php
		<?
			$success = 0;
			foreach($test->i as $k => $v) {
				if (is_int($k)) echo "1";
			}
		end_php
		result = run_php(php)
		result[:errors].should == ""
		result[:output].lstrip().should == "11"
	end
	
	it "should be able to pass complex arrays/hashes" do
		# The following mess sets up a total count of 42
		a = [ 10, 11 ]
		h = { 'first' => a, 'second' => a }
		a = [ h, -10, 5, 5 ]
		h = { 'up' => a, 'down' => -3, 'charm' => -4, 'strange' => -3,
			  'top' => 8, 'bottom' => 2 }
		@test.i = [ h, [ -3, 0, 3] ]
		
		RapModule::php.register_object(@test, "test")
		php = <<-end_php
		<?
			$total = 0;
			function recurse_sum($obj) {
				if (is_array($obj)) {
					$sum = 0;
					foreach($obj as $key => $value) {
						$sum += recurse_sum($value);
					}
					return $sum;
				} else {
					return $obj;
				}
			}
			echo recurse_sum($test->i);
		end_php
		result = run_php(php)
		result[:errors].should == ""
		result[:output].lstrip().should == "42"
	end

	it "should be able to pass an object" do
		@test.i = RapTestObj.new()
		@test.i.i = 1
		RapModule::php.register_object(@test, "test")
		php = <<-end_php
		<?
			echo $test->i->i;
		end_php
		result = run_php(php)
		result[:errors].should == ""
		result[:output].lstrip().should == "1"
	end

	it "should be able to pass an array of objects" do
		@test.i = [ RapTestObj.new(), RapTestObj.new() ]
		@test.i[0].i = 1
		@test.i[1].i = 42
		RapModule::php.register_object(@test, "test")
		php = <<-end_php
		<?
			echo $test->i[0]->i . ", " . $test->i[1]->i;
		end_php
		result = run_php(php)
		result[:errors].should == ""
		result[:output].lstrip().should == "1, 42"
	end

	it "should be able to pass a hash table of objects" do
		@test.i = { "first" => RapTestObj.new(), "second" => RapTestObj.new() }
		@test.i["first"].i = 1
		@test.i["second"].i = 42
		RapModule::php.register_object(@test, "test")
		php = <<-end_php
		<?
			echo $test->i["first"]->i . ", " . $test->i["second"]->i;
		end_php
		result = run_php(php)
		result[:errors].should == ""
		result[:output].lstrip().should == "1, 42"
	end
	
	it "should be able to pass stupidly complicated arrays of objects" do
		# The following mess sets up a total count of 42
		a = [ RapTestObj.new(), RapTestObj.new() ]
		a[0] = 10
		a[1] = 11
		h = { 'first' => a, 'second' => a }
		a = [ h, -10, 5, 5 ]
		h = { 'up' => a, 'down' => -3, 'charm' => -4, 'strange' => -3,
			  'top' => 8, 'bottom' => 2 }
		@test.i = [ h, [ -3, 0, 3] ]
		
		RapModule::php.register_object(@test, "test")
		php = <<-end_php
		<?
			$total = 0;
			function recurse_sum($obj) {
				if (is_array($obj)) {
					$sum = 0;
					foreach($obj as $key => $value) {
						$sum += recurse_sum($value);
					}
					return $sum;
				} else if (is_object($obj)) {
					return $obj->i;
				} else {
					return $obj;
				}
			}
			echo recurse_sum($test->i);
		end_php
		result = run_php(php)
		result[:errors].should == ""
		result[:output].lstrip().should == "42"
	end

	it "should be able to pass a string" do
		@test.i = "hello, world"
		@test.kind_of?(Hash).should == false
		@test.i.kind_of?(Hash).should == false
		RapModule::php.register_object(@test, "test")
		php = <<-end_php
		<?
			if (($test->i == "hello, world")) {
				echo 'true';
			} else {
				echo $test->i;
			}
		end_php
		result = run_php(php)
		result[:errors].should == ""
		result[:output].lstrip().should == "true"
	end

	it "should be able to pass another type, wrapped" do
		# We'll send in a symbol, that's not something we normally allow
		@test.i = :helloworld
		RapModule::php.register_object(@test, "test")
		php = <<-end_php
		<?
			echo $test->i->to_s;
		end_php
		result = run_php(php)
		result[:errors].should == ""
		result[:output].lstrip().should == "helloworld"
	end

end
