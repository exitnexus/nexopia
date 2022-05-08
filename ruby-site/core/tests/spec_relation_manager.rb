lib_require :Core, 'storable/relation_manager'

describe RelationManager, "Prototype Management" do
	before do
		@prototype = stub("prototype", {
			:origin => :origin_table,
			:name => :prototype_name
		})
		@relation_prototypes = {}
		@original_relation_prototypes = RelationManager.send(:class_variable_get, :@@relation_prototypes)
		RelationManager.send(:class_variable_set, :@@relation_prototypes, @relation_prototypes)
	end
	
	it "should be able to create a prototype" do
		args = [:name, :type, :origin, :key_columns, :target, [:extra_args]]
		RelationPrototype.should_receive(:new).with(*args).and_return(@prototype)
		RelationManager.create_prototype(*args)
	end
	
	it "should be able to register and lookup prototype" do
		RelationManager.register_prototype(@prototype)
		RelationManager.get_prototype(:origin_table, :prototype_name).should == @prototype
	end
	
	it "should be able to find prototypes that match a table and set of columns" do
		value1 = stub("value1")
		value2 = stub("value2")
		@relation_prototypes[:key1] = value1
		@relation_prototypes[:key2] = value2
		
		value1.should_receive(:match?).and_return(true)
		value2.should_receive(:match?).and_return(false)
		
		RelationManager.find_prototypes(:some_table).should == [value1]
	end
	
	after do
		RelationManager.send(:class_variable_set, :@@relation_prototypes, @original_relation_prototypes)
	end
end

describe RelationManager, "Relation Caching" do
	
	before do
		@prototype = stub("prototype", {
			:origin => :origin_table,
			:name => :prototype_name
		})
		@relation_prototypes = {}
		@original_relation_prototypes = RelationManager.send(:class_variable_get, :@@relation_prototypes)
		RelationManager.send(:class_variable_set, :@@relation_prototypes, @relation_prototypes)
		RelationManager.register_prototype(@prototype)
		@relation_cache = {}
		RelationManager.stub!(:relation_cache).and_return(@relation_cache)
	end
	
	it "should create relation objects" do
		@prototype.should_receive(:create_relation).with(:instance).and_return(stub("example_relation", :null_object => true))
		RelationManager.create_relation(:origin_table, :prototype_name, :instance)
	end
	
	it "should cache newly created relation objects" do
		example_relation = stub("example_relation")
		example_relation.should_receive(:cache_key).at_least(:once).and_return(:example_cache_key)
		@prototype.should_receive(:create_relation).with(:instance).and_return(example_relation)
		RelationManager.create_relation(:origin_table, :prototype_name, :instance)
		@relation_cache[:example_cache_key].should == example_relation
	end
	
	it "should return a cached relation if there is one when an attempt is made to create a new relation for the same class and name" do
		example_relation = stub("example_relation", {:cache_key => :example_cache_key})
		RelationManager.cache_relation(example_relation)
		example_relation2 = stub("example_relation2", {:cache_key => :example_cache_key})
		@prototype.should_receive(:create_relation).with(:instance).and_return(example_relation2)
		relation = RelationManager.create_relation(:origin_table, :prototype_name, :instance)
		relation.should == example_relation
		relation.should_not == example_relation2
	end
	
	it "should raise an error if it can't create a relation" do
		lambda {RelationManager.create_relation(:unknown_table, :unknown_name, :instance)}.should raise_error
	end
	
	after do
		RelationManager.send(:class_variable_set, :@@relation_prototypes, @original_relation_prototypes)
	end
end

describe RelationManager, "Relation Invalidation" do
	before do
		@memcache = $site.memcache
		$site.instance_variable_set(:@memcache, stub("memcache", {
			:delete => nil
		}))
		@relation_cache = {}
		RelationManager.stub!(:relation_cache).and_return(@relation_cache)
	end
	
	it "should invalidate relations" do
		example_relation = stub("example_relation", {
			:cache_key => :example_cache_key
		})
		RelationManager.cache_relation(example_relation)
		example_relation.should_receive(:invalidate)
		RelationManager.invalidate_relation(:example_cache_key)
	end
	
	it "should delete from memcache when invalidating a relation" do
		$site.memcache.should_receive(:delete).with(:example_cache_key)
		RelationManager.invalidate_relation(:example_cache_key)
	end
	
	it "should invalidate both the previous and current relations when storing a relation instance" do
		instance = stub("storable instance", {
			:modified_columns => [],
			:class => :storable_class,
			:original_version => stub("original_storable_instance")
		})
		prototype = stub("prototype")
		#should be called twice once for the original version once for the new version
		prototype.should_receive(:cache_key).and_return(:cache_key1, :cache_key2)
		RelationManager.should_receive(:find_prototypes).with(:storable_class, []).and_return([prototype])
		RelationManager.should_receive(:invalidate_relation).with(:cache_key1)
		RelationManager.should_receive(:invalidate_relation).with(:cache_key2)
		RelationManager.invalidate_store(instance)
	end
	
	it "should invalidate both only previous relation when invalidating for deletions" do
		instance = stub("storable instance", {
			:class => :storable_class,
			:original_version => stub("original_storable_instance")
		})
		prototype = stub("prototype")
		#should be called twice once for the original version once for the new version
		prototype.should_receive(:cache_key).and_return(:cache_key1)
		RelationManager.should_receive(:find_prototypes).with(:storable_class).and_return([prototype])
		RelationManager.should_receive(:invalidate_relation).with(:cache_key1)
		RelationManager.invalidate_delete(instance)
	end
	
	
	after do
		$site.instance_variable_set(:@memcache, @memcache)
	end
end