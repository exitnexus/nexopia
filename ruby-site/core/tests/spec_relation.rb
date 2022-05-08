lib_require :Core, 'storable/relation'

describe Relation do
	before do
		@promised_ids = {}
		@storable_id = stub("storable_id")
		@prototype = stub("prototype", {
			:target => stub("target_class", {
				:indexes => {:example_index => [:target_column1, :target_column2]},
				:group_ids => [@storable_id],
				:promised_ids => @promised_ids
			}),
			:origin_columns => [:column1, :column2],
			:extracted_options => {:index => :example_index, :selection => :example_selection}
		})
		@instance = stub("instance", {
			:column1 => 1,
			:column2 => 2
		})
		@relation = Relation.new(@instance, @prototype)
	end
	
	it "should not immediately execute" do
		evaluated?(@relation).should_not be_true
	end
	
	it "should pass auto_prime? and cache_key through to its prototype" do
		@prototype.should_receive(:auto_prime?).and_return(:auto_prime_result)
		@relation.auto_prime?.should == :auto_prime_result
		@prototype.should_receive(:cache_key).with(@instance).and_return(:cache_key_result)
		@relation.cache_key.should == :cache_key_result
	end
	
	it "should calculate the ids it needs to use to query for its result" do
		@instance.should_receive(:column1).and_return(1)
		@instance.should_receive(:column2).and_return(2)
		@relation.query_ids.should == [1,2]
	end
	
	it "should call execute and error when evaluating itself" do
		#This depends on the fact that execute raises an error for Relation
		lambda {demand(@relation)}.should raise_error {|error|
			error =~ /Attempted to execute from class Relation\. Only subclasses of Relation should be executed\./
		}
	end
	
	it "should register itself with its result when executed" do
		class SampleRelation < Relation
			def execute
				return RESULT
			end
		end
		relations_to = []
		SampleRelation::RESULT = stub("relation_result", {
			:respond_to? => true,
			:relations_to => relations_to,
		})
		SampleRelation::RESULT.stub!(:__result__).and_return(SampleRelation::RESULT)

		sample_relation = SampleRelation.new(@instance, @prototype)
		demand(sample_relation).should == SampleRelation::RESULT
		relations_to.should_not be_empty
	end
	
	it "should promise storable ids to its target class before it is executed" do
		@promised_ids.delete_if {|key,val| true}
		@promised_ids.should be_empty
		@relation = Relation.new(@instance, @prototype)
		@promised_ids.should_not be_empty
		@promised_ids[:example_selection][@storable_id].should be_true
		evaluated?(@relation).should_not be_true
	end

	it "should be memcached" do
		Relation.should be_memcache
	end
end