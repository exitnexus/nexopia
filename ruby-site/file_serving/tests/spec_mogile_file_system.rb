lib_require :FileServing, 'type'

# Reopen class to allow easy access to MogileFS instance for mocking.
#class MogileFileSystem < FileSystem
#	attr_accessor :mogilefs
#end

=begin

# Helper method to get a MogileFileSystem object.
def init_mog(mock = false)
#	memcache = MemCache.new(*$site.config.memcache_options){|key|
#		#This is the same hash function as the php code uses.
#		key = key.to_s;
#		len = key.size;
#		hash = 0;
#		(0...len).each{|i|
#			hash ^= (i+1)*(key[i]);
#		}
#		hash;
#	}
	cache = mock("memcache", :null_object =>true )
	mog = MogileFileSystem.new($site.config.mogilefs_hosts, cache, $site.config.mogilefs_domain, $site.config.mogilefs_options)
	return mog
end

describe MogileFileSystem, "initialisation" do
	it "should initialise properly" do
		@mog = init_mog()
		@mog.should_not eql(nil)
	end
	
	it "should allow access to mogilefs variable for mocking" do
		@mog = init_mog()
		@mog.mogilefs.stub!(:a_method).and_return(true)
		@mog.mogilefs.a_method.should eql(true)
	end
end

describe MogileFileSystem, "store method" do
	before do
		@mog = init_mog()
		@file = "data/sample.txt"
	end
	
	it "should be able to store data" do
		@mog.mogilefs.stub!(:store_file).with(any_args())
		@mog.store("data", "key", @mog.class_code['userpics'], @file)
	end
	
	it "should handle read-only error conditions"
end
=end