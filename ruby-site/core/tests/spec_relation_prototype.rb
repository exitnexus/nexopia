lib_require :Core, 'storable/relation_prototype'

class RelationTest < Relation
	AUTO_PRIME_DEFAULT = false
end

describe RelationPrototype do
	before do
		@storable_class_origin = stub("StorableClassOrigin", {
			:prefix => "Origin"
		})
		@origin_instance = stub("origin_instance", {
			:origin_column1 => 1,
			:origin_column2 => 2,
			:class => @storable_class_origin
		})
		@storable_class_target = stub("StorableClassTarget", {
			:extract_options_from_args! => {:index => :example_index},
			:indexes => {:example_index => [:target_column1, :target_column2] },
			:group_ids => stub("StorableIDArray", {
				:each => nil
			})
		})
		@target_instance = stub("target_instance", {
			:target_column1 => 1,
			:target_column2 => 2,
			:class => @storable_class_target
		})
		@prototype = RelationPrototype.new(:relation_test, :test, @storable_class_origin, [:origin_column1, :origin_column2], @storable_class_target)
	end

	it "should know whether to auto prime a relation" do
		@prototype.should_not be_auto_prime
		@prototype.relation_options[:auto_prime] = true
		@prototype.should be_auto_prime
	end
	
	it "should be able to calculate the cache key for a storable instance" do
		@prototype.cache_key(@target_instance).should == "Origin_relation_test_relation-1/2"
		@prototype.cache_key(@origin_instance).should == "Origin_relation_test_relation-1/2"
	end
	
	it "should create relation instances" do
		RelationTest.should_receive(:new)
		@prototype.create_relation(@origin_instance)
	end
	
	it "should be able to tell if a set of columns on a table intersects with the relation" do
		@prototype.should be_a_match(@storable_class_origin, [:origin_column1, :foo, :bar])
		@prototype.should_not be_a_match(@storable_class_origin, [:target_column1])
		@prototype.should be_a_match(@storable_class_target, [:foo, :target_column1])
		@prototype.should_not be_a_match(@storable_class_target, [])
		@prototype.should be_a_match(@storable_class_target)
		@prototype.should_not be_a_match(:foo)
	end
	
	
	it "should be instantiated with a type, name, origin, and target" do
		@prototype.type.should == RelationTest
		@prototype.name.should == :relation_test
		@prototype.origin.should == @storable_class_origin
		@prototype.target.should == @storable_class_target
	end
	
	it "should have a list of origin columns and target columns that it depends on" do
		@prototype.origin_columns.should == [:origin_column1, :origin_column2]
		@prototype.target_columns.should == [:target_column1, :target_column2]
	end
	
	it "should extra relation specific options from it's constructor" do
		@prototype.relation_options.should be_empty
		new_proto = RelationPrototype.new(:relation_test, :test, @storable_class_origin, [:origin_column1, :origin_column2], @storable_class_target, :auto_prime => true)
		new_proto.relation_options.should_not be_empty
	end
	
	it "should be able to determine the index being used on the target of the relation" do
		@prototype.extracted_options[:index].should == :example_index
	end
end