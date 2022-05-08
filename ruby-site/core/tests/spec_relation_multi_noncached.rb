lib_require :Core, "storable/relation_multi_noncached"

describe RelationMultiNoncached do
	before do
		@storable_id = stub("storable_id")
		@prototype = stub("prototype", {
			:target => stub("target_class", {
				:indexes => {:example_index => [:target_column1, :target_column2]},
				:group_ids => [@storable_id],
				:promised_ids => {}
			}),
			:origin_columns => [:column1, :column2],
			:extracted_options => {:index => :example_index, :selection => :example_selection}
		})
		@instance = stub("instance", {
			:column1 => 1,
			:column2 => 2
		})
	end
	
	it "should call find at execution" do
		@prototype.should_receive(:find_options).and_return([:example_find_options])
		@prototype.target.should_receive(:find).and_return([])
		@relation = RelationMultiNoncached.new(@instance, @prototype)
		@relation.should == []
	end
	
	it "should register itself with all of its results" do
		@prototype.should_receive(:find_options).and_return([:example_find_options])
		storable = stub("storable")
		storable.should_receive(:register_relation_to)
		@prototype.target.should_receive(:find).and_return([storable])
		@relation = RelationMultiNoncached.new(@instance, @prototype)
		demand(@relation)
	end
	
	it "should not be memcached" do
		RelationSingular.should_not be_memcache
	end
end