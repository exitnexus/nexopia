#!/usr/bin/ruby -w
#
# Unit test for instantiation of MemCache objects
# $Id: instantiation.tests.rb 12 2004-10-03 21:04:34Z ged $
#
# Copyright (c) 2004 RubyCrafters, LLC. Most rights reserved.
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


### Collection of tests for the instantiation class.
class InstantiationTestCase < MemCache::TestCase


	TestServerSets = {
		:simple => [
			{
				:name => 'One IP (unweighted)',
				:servers => %w[ 127.0.0.1 ],
			},
			{
				:name => 'Two IPs (unweighted)',
				:servers => %w[ 127.0.0.1 10.1.0.1 ],
			},
		],

		:weighted => [
			{
				:name => 'One IP (weighted)',
				:servers => [['127.0.0.1', 1]],
			},
			{
				:name => 'Two IPs (weighted)',
				:servers => [['127.0.0.1', 1], ['10.1.0.1', 2]],
			},
		],

		:ports => [
			{
				:name => 'One IP (w/port)',
				:servers => %w[ 127.0.0.1:11211 ]
			},
			{
				:name => 'Two IPs (w/ports)',
				:servers => %w[ 127.0.0.1:123123 10.1.0.1:11292 ],
			},
		],

		:weighted_ports => [
			{
				:name => 'One IP (weighted w/port)',
				:servers => [['127.0.0.1:11001', 1]],
			},
			{
				:name => 'Two IPs (weighted w/ports)',
				:servers => [['127.0.0.1:11211', 1], ['10.1.0.11:11320', 2]],
			},
		],

		:with_opts => [
			{ 
				:name => 'One IP (weighted w/port)',
				:servers => [['127.0.0.1:11001', 1]],
				:opts => { :compression => true },
			},
			{
				:name => 'Two IPs (weighted w/ports)',
				:servers => [['127.0.0.1:11211', 1], ['10.1.0.11:11320', 2]],
				:opts => { :compression => true },
			},
		],
	}

	#################################################################
	###	T E S T S
	#################################################################

	### Instantiate with no args
	def test_00_no_args
		printTestHeader "Instantiation: No-args construction"
		rval = cache = nil

		assert_nothing_raised { cache = MemCache::new }
		assert_instance_of MemCache, cache

		assert_nothing_raised { rval = cache.servers }
		assert_instance_of Array, rval
		assert_equal 0, rval.nitems
	end


	### Instantiate with one or more server args
	def test_01_with_servers
		printTestHeader "Instantiation: Server args"
		rval = cache = nil
		
		TestServerSets.each {|key, sets|
			sets.each do |set|
				debugMsg "Testing #{key} sets"

				# Instantiate with various args specified by the set
				if set.key?( :opts )
					args = set[:servers].dup
					args.push( set[:opts] )
					assert_nothing_raised( set[:name] ) {
						cache = MemCache::new( *args )
					}
				else
					assert_nothing_raised( set[:name] ) {
						cache = MemCache::new( *(set[:servers]) )
					}
				end

				assert_instance_of MemCache, cache

				# Check to be sure we can get the server list back out
				assert_nothing_raised { rval = cache.servers }
				assert_instance_of Array, rval
				assert_equal set[:servers].nitems, rval.nitems

				# Test the #active method while we're at it
				assert_nothing_raised { rval = cache.active? }
				assert_equal true, rval
			end
		}
	end

	
	### Compression option
	def test_10_compression_opt
		printTestHeader "Instantiation: Compression option"
		rval = cache = nil

		# Test getter with default value
		cache = MemCache::new
		assert_nothing_raised { rval = cache.compression }
		assert_equal true, rval

		# Test setting via constructor and getter
		assert_nothing_raised { cache = MemCache::new(:compression => false) }
		assert_nothing_raised { rval = cache.compression }
		assert_equal false, rval
	end


	### Compression threshold option
	def test_20_compression_threshold_opt
		printTestHeader "Instantiation: Compression threshold option"
		rval = cache = nil

		# Test getter with default value
		cache = MemCache::new
		assert_nothing_raised { rval = cache.c_threshold }
		assert_equal MemCache::DefaultCThreshold, rval

		# Test setting via constructor with various sizes and getter
		16.times do |factor|
			thresh = 2 ** factor

			assert_nothing_raised { cache = MemCache::new(:c_threshold => thresh) }
			assert_nothing_raised { rval = cache.c_threshold }
			assert_equal thresh, rval
			assert_nothing_raised { cache.c_threshold = 2 ** (factor-1) }
		end
	end

	
	### Compression option
	def test_30_debug_opt
		printTestHeader "Instantiation: Debug option"
		rval = cache = nil

		# Test default value and getter
		cache = MemCache::new
		assert_nothing_raised { rval = cache.debug }
		assert_equal false, rval

		# Test <<-style debugging object (String)
		rval = ''
		assert_nothing_raised { cache = MemCache::new(:debug => rval) }
		assert_nothing_raised {
			cache.servers = TestServerSets[:simple][0][:servers]
		}
		assert !rval.empty?, "Rval should not be empty"
		assert_match( /Transforming/, rval )

		# Test <<-style debugging object (Array)
		rval = []
		assert_nothing_raised { cache = MemCache::new(:debug => rval) }
		assert_nothing_raised {
			cache.servers = TestServerSets[:simple][0][:servers]
		}
		assert !rval.empty?, "Rval should not be empty"
		assert_match( /Transforming/, rval[0] )

		# Test call-style debugging object (Proc)
		rval = ''
		func = lambda {|msg| rval << msg }
		assert_nothing_raised { cache = MemCache::new(:debug => func) }
		assert_nothing_raised {
			cache.servers = TestServerSets[:simple][0][:servers]
		}
		assert !rval.empty?, "Rval should not be empty"
		assert_match( /Transforming/, rval )

	end

end


