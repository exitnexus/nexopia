#!/usr/bin/ruby -w
#
# Unit test for Memcache stats functions
# $Id: stats.tests.rb 66 2005-08-18 00:28:40Z ged $
#
# Copyright (c) 2004, 2005 RubyCrafters, LLC. Most rights reserved.
# 
# This work is licensed under the Creative Commons Attribution-ShareAlike
# License. To view a copy of this license, visit
# http://creativecommons.org/licenses/by-sa/1.0/ or send a letter to Creative
# Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
#
# 

unless defined? MemCache::TestCase
	testsdir = File::dirname( File::expand_path(__FILE__) )
	basedir = File::dirname( testsdir )
	$LOAD_PATH.unshift "#{basedir}/lib" unless
		$LOAD_PATH.include?( "#{basedir}/lib" )
	$LOAD_PATH.unshift "#{basedir}/tests" unless
		$LOAD_PATH.include?( "#{basedir}/tests" )

	require 'mctestcase'
end


### Collection of tests for the Memcache class.
class MemCache::StatsTestCase < MemCache::TestCase

	### Set up memcache instance or skip
	def setup
		if @config
			server = [ @config[:server], @config[:port] ].join(":")
			@memcache = MemCache::new( server, :debug => $DEBUG )
			@memcache.debug = $DEBUG
		else
			skip( "No memcached to test against." )
		end

		super
	end
		

	#################################################################
	###	T E S T S
	#################################################################

	def test_00_client_stats
		printTestHeader "Stats: Client stats method"
		rval = nil

		# Generate some stats to test
		@memcache[ :foo ] = 1
		@memcache[ :foo ]
		@memcache[ :foo, :bar ] = 47, "Mr Roboto"

		assert_respond_to @memcache, :stats
		assert_nothing_raised { rval = @memcache.stats }
		assert_instance_of Hash, rval
	end

	
	# :TODO: Most of these methods should really be tested with multiple
	# servers, but that's kind of a pain in the arse for the user to set
	# up. Once I have a good enough mock object perhaps I'll add some more
	# tests.


	def test_50_server_stats
		printTestHeader "Stats: Server stats method"
		rval = nil

		assert_respond_to @memcache, :server_stats

		# Without servers specified
		assert_nothing_raised {
			rval = @memcache.server_stats
		}
		assert_instance_of Hash, rval

		# With servers specified
		assert_nothing_raised {
			rval = @memcache.server_stats( @memcache.servers )
		}
		assert_instance_of Hash, rval
	end

	def test_55_server_map_stats
		printTestHeader "Stats: Server stats maps method"
		rval = nil

		assert_respond_to @memcache, :server_map_stats

		# 'stat maps' doesn't work on MacOS X (no /proc/self/maps) and hangs on
		# the Linux boxes I've tested it on, so this is commented out until it
		# works somewhere.

		## Without servers specified
		#assert_nothing_raised {
		#	rval = @memcache.server_map_stats
		#}
		#assert_instance_of Hash, rval
		#
		## With servers specified
		#assert_nothing_raised {
		#	rval = @memcache.server_map_stats( @memcache.servers )
		#}
		#assert_instance_of Hash, rval
	end

	def test_60_server_malloc_stats
		printTestHeader "Stats: Server malloc stats method"
		rval = nil

		assert_respond_to @memcache, :server_malloc_stats

		# Without servers specified
		assert_nothing_raised {
			rval = @memcache.server_malloc_stats
		}
		assert_instance_of Hash, rval

		# With servers specified
		assert_nothing_raised {
			rval = @memcache.server_malloc_stats( @memcache.servers )
		}
		assert_instance_of Hash, rval
	end

	def test_65_server_slab_stats
		printTestHeader "Stats: Server slab stats method"
		rval = nil

		assert_respond_to @memcache, :server_slab_stats

		# Without servers specified
		assert_nothing_raised {
			rval = @memcache.server_slab_stats
		}
		assert_instance_of Hash, rval

		# With servers specified
		assert_nothing_raised {
			rval = @memcache.server_slab_stats( @memcache.servers )
		}
		assert_instance_of Hash, rval
	end

	def test_70_server_item_stats
		printTestHeader "Stats: Server item stats method"
		rval = nil

		assert_respond_to @memcache, :server_item_stats

		# Without servers specified
		assert_nothing_raised {
			rval = @memcache.server_item_stats
		}
		assert_instance_of Hash, rval

		# With servers specified
		assert_nothing_raised {
			rval = @memcache.server_item_stats( @memcache.servers )
		}
		assert_instance_of Hash, rval
	end

	def test_65_server_size_stats
		printTestHeader "Stats: Server size stats method"
		rval = nil

		assert_respond_to @memcache, :server_size_stats

		# Without servers specified
		assert_nothing_raised {
			rval = @memcache.server_size_stats
		}
		assert_instance_of Hash, rval

		# With servers specified
		assert_nothing_raised {
			rval = @memcache.server_size_stats( @memcache.servers )
		}
		assert_instance_of Hash, rval
	end

	def test_99_server_reset_stats
		printTestHeader "Stats: Server reset stats method"
		rval = nil

		assert_respond_to @memcache, :server_reset_stats

		# Without servers specified
		assert_nothing_raised {
			rval = @memcache.server_reset_stats
		}
		assert_instance_of Hash, rval
		rval.each_pair do |svr,reply|
			assert_equal true, reply
		end

		# With servers specified
		assert_nothing_raised {
			rval = @memcache.server_reset_stats( @memcache.servers )
		}
		assert_instance_of Hash, rval
		rval.each_pair do |svr,reply|
			assert_equal true, reply
		end
	end



end

