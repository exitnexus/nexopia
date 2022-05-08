#!/usr/bin/ruby -w
#
# Unit tests for fixes/bug reports
# $Id: bug.tests.rb 80 2005-09-29 03:56:23Z ged $
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

require 'uri'

### Collection of tests for the bug reports class.
class BugReportTestCase < MemCache::TestCase


	#################################################################
	###	T E S T S
	#################################################################

	### Fix for #1: "NameError when using more than one memcached server"
	def test_more_than_one_memcached
		printTestHeader "Bugfixes: #1: More than one memcached -> NameError"

		if @config
			server = [ @config[:server], @config[:port] ].join(":")

			cache = MemCache::new( "1.2.3.4", server, :debug => $DEBUG )
			rval = nil

			assert_nothing_raised do
				cache.set( "foo", "aa" )
			end
		else
			skip( "No memcached to test against" )
		end
	end
	

	### Serialization of empty array raises an exception from debugging
	### message. Thanks to Robert Cottrell <bob@robotcoop.com> for the report
	### and the fix. I also added an empty array to the general get/set tests,
	### but I wanted to express this directly, too.
	def test_serializing_empty_array
		printTestHeader "Bugs: Serialization of empty array raises an exception"
		cache = MemCache::new( :debug => $DEBUG )
		rval, flags = nil

		assert_nothing_raised {
			# Sidestep the 'protected'-ness of the #prep_value method
			prep_value = cache.method( :prep_value )
			rval, flags = prep_value.call( [] )
		}

		debugMsg "Got: rval = %p" % [rval]

		# Result should be both serialized and url-escaped, and should
		# de-serialize into an empty Array again.
		assert (flags | MemCache::F_SERIALIZED).nonzero?
		assert (flags | MemCache::F_ESCAPED).nonzero?
		assert_equal [], Marshal::load( URI::unescape(rval) ),
			"De-serialized prepped Array should be an Array"
	end


	### False positives in end-of-data block detection (#6 submitted by Ron
	### Mayer).
	def test_false_positives_in_end_of_block_detection
		printTestHeader "Bugs: False positives in end-of-data block detection"

		if @config
			server = [ @config[:server], @config[:port] ].join(":")
			cache = MemCache::new server,
				:urlencode=>false,
				:compression => false

			rval = nil
			testval = "\r\nEND" * 1000

			cache['a'] = testval
		
			assert_nothing_raised do
				rval = cache['a']
			end

			assert_equal testval, rval
		else
			skip( "No memcached to test against" )
		end
	end
	
end

