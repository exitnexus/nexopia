lib_require :UserDump, "dumpable"

class ExtendedObject
	extend Dumpable
end

class ImplementedObject
	extend Dumpable

	def user_dump(a, b, c)
		return File.new()
	end
end

describe Dumpable do
	
	it "should return a file from str_to_file with the file name passed in"
end

describe ExtendedObject do
	
	it do
		ExtendedObject.should respond_to :user_dump
	end

	it "should respond to #extended" do
		ExtendedObject.should respond_to :extended
	end

	it "should throw an error if #user_dump hasn't been over-ridden" do
		lambda {ExtendedObject.user_dump(0, 0, 0)}.should raise_error
	end
end

describe ImplementedObject do
	
	it "should return a file from user_dump" do
		user_dump_file = ImplementedObject.user_dump(0, 0, 0)
		user_dump_file.should be_a_kind_of File
	end
	
end