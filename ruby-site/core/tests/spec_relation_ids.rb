lib_require :Core, "storable/relation_ids"

describe RelationIds do
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
				:promised_ids => {},
				:find => []
			}),
			:cache_key => :example_cache_key,
			:find_options => [:example_find_options],
			:origin_columns => [:column1, :column2],
			:extracted_options => {:index => :example_index, :selection => :example_selection}
		})
		@instance = stub("instance", {
			:column1 => 1,
			:column2 => 2
		})
	end
	
	it "should look for the ids in memcache" do
		@relation = RelationIds.new(@instance, @prototype)
		$site.memcache.should_receive(:get).with(@relation.cache_key)
		demand(@relation)
	end
	
	it "should call find if it doesn't find a result in memcache" do
		@relation = RelationIds.new(@instance, @prototype)
		@prototype.target.should_receive(:find).and_return([])
		demand(@relation)
	end

	it "should memcache its result if it looks it up with find" do
		@relation = RelationIds.new(@instance, @prototype)
		$site.memcache.should_receive(:set).with(:example_cache_key,anything())
		demand(@relation)
	end

	it "should be memcached" do
		RelationIds.should be_memcache
	end

	after do
		$site.instance_variable_set(:@memcache, @memcache)
	end
end