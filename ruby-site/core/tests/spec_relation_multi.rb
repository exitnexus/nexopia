lib_require :Core, "storable/relation_multi"

class SpecRelationMulti < RelationMulti
	attr_accessor :cached_ids
end

describe RelationMulti do
	before do
		@memcache = $site.memcache
		$site.instance_variable_set(:@memcache, stub("memcache", {
			:get => nil,
			:set => nil
		}))
		@storable_id = stub("storable_id")
		@prototype = stub("prototype", {
			:target => stub("target_class", {
				:indexes => {:example_index => [:target_column1, :target_column2]},
				:group_ids => [@storable_id],
				:promised_ids => {}
			}),
			:cache_key => :example_cache_key,
			:origin_columns => [:column1, :column2],
			:extracted_options => {:index => :example_index, :selection => :example_selection},
			:find_options => [:example_find_options]
		})
		@instance = stub("instance", {
			:column1 => 1,
			:column2 => 2
		})
	end
	
	it "should attempt to load a set of ids from memcache" do
		$site.memcache.should_receive(:get).with(:example_cache_key).and_return(:example_cached_ids)
		@relation = SpecRelationMulti.new(@instance, @prototype)
		@relation.cached_ids.should == :example_cached_ids
	end
	
	it "should use the ids it is configured with if it can't find ids in memcache" do
		$site.memcache.should_receive(:get).with(:example_cache_key).and_return(nil)
		@relation = SpecRelationMulti.new(@instance, @prototype)
		@relation.cached_ids.should be_nil
		@prototype.target.should_receive(:find).with(*(@prototype.find_options + @relation.query_ids)).and_return([])
		demand(@relation)
	end
	
	it "should cache the ids of the result if it didn't find ids in memcache" do
		$site.memcache.should_receive(:get).with(:example_cache_key).and_return(nil)
		@relation = SpecRelationMulti.new(@instance, @prototype)
		@prototype.target.should_receive(:find).and_return([])
		$site.memcache.should_receive(:set)
		demand(@relation)
	end
	
	it "should throw away its local copy of the memcached ids when it is invalidated" do
		$site.memcache.should_receive(:get).with(:example_cache_key).and_return(:example_cached_ids)
		@relation = SpecRelationMulti.new(@instance, @prototype)
		@relation.cached_ids.should == :example_cached_ids
		@relation.invalidate
		@relation.cached_ids.should be_nil
	end
	
	it "should be memcached" do
		RelationMulti.should be_memcache
	end
	
	after do
		$site.instance_variable_set(:@memcache, @memcache)
	end
end