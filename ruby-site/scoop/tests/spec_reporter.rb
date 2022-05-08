lib_require :Core, 'storable/storable'
lib_require :Scoop, 'reporter'

class SpecReporter < Storable
	def initialize
	end
	
	def self.event_hooks
		@event_hooks ||= Hash.new
		return @event_hooks
	end
	
	def self.event_hooks=(val)
		@event_hooks = val
	end
	
	USERID = 42
	TYPEID = 69
	PRIMARYID = 100
	SECONDARYID = 101
	SEQID = 13
	
	def even?(int)
		return int%2 == 0
	end
	
	def userid
		return USERID
	end
	
	def self.typeid
		return TYPEID
	end
	
	def get_primary_key
		return [primaryid, secondaryid]
	end
	
	def primaryid
		return PRIMARYID
	end
	
	def secondaryid
		return SECONDARYID
	end
end


describe Scoop::Reporter do
	before do
		Scoop::Event.should_receive(:get_seq_id).any_number_of_times.and_return(SpecReporter::SEQID)
	end
	
	def generate_event
		generate_event = mock "SpecReporter::Event"
		generate_event.should_receive(:userid=).with(SpecReporter::USERID)
		generate_event.should_receive(:id=).with(SpecReporter::SEQID)
		generate_event.should_receive(:typeid=).with(SpecReporter::TYPEID)
		generate_event.should_receive(:primaryid=).with(SpecReporter::PRIMARYID)
		generate_event.should_receive(:secondaryid=).with(SpecReporter::SECONDARYID)
		generate_event.should_receive(:store).and_return(true)
		return generate_event
	end
	
	it do
		SpecReporter.should respond_to(:report)
	end
	
	it do
		SpecReporter.should respond_to(:report_create)
	end
	
	it do
		SpecReporter.should respond_to(:report_update)
	end

	it do
		SpecReporter.should respond_to(:report_delete)
	end

	it "should generate and store a Scoop::Event when after_create is called once it has been told to report :create" do
		lambda {
			SpecReporter.report :create
		}.should_not raise_error
		
		event = generate_event
		event.should_receive(:event=).with(:create)
		
		Scoop::Event.should_receive(:new).and_return(event)
		Scoop::Event.should_receive(:distribute_event).and_return(true)
		
		sr = SpecReporter.new
		sr.after_create
	end

	it "should generate and store a Scoop::Event when after_update is called once it has been told to report :update" do
		lambda {
			SpecReporter.report :update
		}.should_not raise_error
	
		event = generate_event
		event.should_receive(:event=).with(:update)
		
		Scoop::Event.should_receive(:new).and_return(event)
		Scoop::Event.should_receive(:distribute_event).and_return(true)
		
		sr = SpecReporter.new
		sr.after_update
	end
	
	it "should generate and store a Scoop::Event when before_delete is called once it has been told to report :delete" do
		lambda {
			SpecReporter.report :delete
		}.should_not raise_error
	
		event = generate_event
		event.should_receive(:event=).with(:delete)
		
		Scoop::Event.should_receive(:new).and_return(event)
		Scoop::Event.should_receive(:distribute_event).and_return(true)
		
		sr = SpecReporter.new
		sr.before_delete
	end
	
	it "should raise an error when told to report an invalid event" do
		lambda {
			SpecReporter.report :invalid_event
		}.should raise_error
	end
end

describe Scoop::Reporter, "userid_column" do
	before do
		SpecReporter.send(:remove_instance_variable, :@userid_column) if (SpecReporter.instance_variable_defined?(:@userid_column))
	end
	
	it do
		SpecReporter.should respond_to(:userid_column)
	end
	
	it do
		SpecReporter.should respond_to(:userid_column=)
	end
	
	it do
		SpecReporter.userid_column.should == :userid
	end
	
	it "setting userid_column to :test should cause accessing it to also return :test" do
		SpecReporter.userid_column = :test
		SpecReporter.userid_column.should == :test
	end
	
	after do
		SpecReporter.send(:remove_instance_variable, :@userid_column) if (SpecReporter.instance_variable_defined?(:@userid_column))
	end
end

describe Scoop::Reporter, "can_view_event?" do
	before do
		Scoop::Event.should_receive(:get_seq_id).any_number_of_times.and_return(SpecReporter::SEQID)
		@random_uid1 = rand(100)
		@random_uid2 = rand(100)
		@random_uid3 = rand(100)
		SpecReporter.send(:remove_instance_variable, :@restrict_report_func) if (SpecReporter.instance_variable_defined?(:@restrict_report_func))
	end

	it "should return true if restrict has not been called" do
		SpecReporter.new.can_view_event?(@random_uid1).should be_true
		SpecReporter.new.can_view_event?(@random_uid2).should be_true
		SpecReporter.new.can_view_event?(@random_uid3).should be_true
	end
	
	it "should return the value of the function registered with restrict if restrict has been called" do
		SpecReporter.restrict :even?
		sr = SpecReporter.new
		sr.can_view_event?(@random_uid1).should == sr.even?(@random_uid1)
		sr.can_view_event?(@random_uid2).should == sr.even?(@random_uid2)
		sr.can_view_event?(@random_uid3).should == sr.even?(@random_uid3)
	end
end