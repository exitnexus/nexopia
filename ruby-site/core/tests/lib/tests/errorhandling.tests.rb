#!/usr/bin/ruby -w
#
# Unit test for error-handling
# $Id: errorhandling.tests.rb 69 2005-08-24 22:28:20Z ged $
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


### Collection of tests for the M class.
class MemCache::ErrorHandlingTestCase < MemCache::TestCase

	ConnectErrorServer = 'localhost:61654'

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

	def test_connection_error
		printTestHeader "ErrorHandling: Connection error"

		# Test to be sure connection errors result in servers being marked dead.
		cache = MemCache::new( ConnectErrorServer )
		
		assert_raises( MemCache::MemCacheError ) do
		    cache.method(:get_server).call( :any_key_should_work )
	    end
	end


	def test_general_error
		printTestHeader "ErrorHandling: General error"
		rval = nil
		
		# Grab the server object that the cache will use (:foo is an arbitrary
		# key to hash on -- in theory anything should work).
		server = @memcache.method( :get_server ).call( :foo )

		# Now send an illegal command directly via #send and expect an
		# "ERROR\r\n" message to be sent back and an exception to be raised
		# because of it.
		assert_raises( MemCache::InternalError ) {
			@memcache.method( :send ).call( server => "bargle" )
		}
	end



	### Test to be sure the error strings can be get/set without triggering
	### exceptions (tests the error-condition matching code).
	def test_false_positive
		printTestHeader "ErrorHandling: False positives"
		rval = nil

		strings = {
			:general => "ERROR\r\n",
			:server  => "SERVER_ERROR false positive\r\n",
			:client  => "CLIENT_ERROR false positive\r\n",
		}

		# Set key and value
		strings.each do |key, str|

			# Key
			debugMsg "Setting %p = %p" % [ str, key ]
			assert_nothing_raised { @memcache[ str ] = key }

			# Value
			debugMsg "Setting %p = %p" % [ key, str ]
			assert_nothing_raised { @memcache[ key ] = str }
		end

		# Get key and value
		strings.each do |key, str|

			# Key
			debugMsg "Fetching %p" % [ str ]
			assert_nothing_raised { rval = @memcache[ str ] }
			assert_nil_or_equal key, rval, "Error message as key"

			# Value
			debugMsg "Fetching %p" % [ key ]
			assert_nothing_raised { rval = @memcache[ key ] }
			assert_nil_or_equal str, rval, "Symbol as key"
		end
	end


	### Test to be sure the error strings and some complex values can be get/set
	### as values with urlencoding turned off.
	def test_false_positive_values_with_urlencoding_off
		printTestHeader "ErrorHandling: False positives"
		rval = nil

		@memcache.urlencode = false

		strings = {
			:general => "ERROR\r\n",
			:server  => "SERVER_ERROR false positive\r\n",
			:client  => "CLIENT_ERROR false positive\r\n",
		}

		# Set key and value
		strings.each do |key, str|
			debugMsg "Setting %p = %p" % [ key, str ]
			assert_nothing_raised { @memcache[ key ] = str }
		end

		# Get key and value
		strings.each do |key, str|
			debugMsg "Fetching %p" % [ key ]
			assert_nothing_raised { rval = @memcache[ key ] }
			assert_nil_or_equal str, rval, "Error message as value"
		end
	end

end

