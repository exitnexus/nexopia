#!/usr/bin/ruby -w
#
# Unit test for the MemCache class
# $Id: require.tests.rb 12 2004-10-03 21:04:34Z ged $
#
# Copyright (c) 2004 RubyCrafters, LLC. Most rights reserved.
# 
# This work is licensed under the Creative Commons Attribution-ShareAlike
# License. To view a copy of this license, visit
# http://creativecommons.org/licenses/by-sa/1.0/ or send a letter to Creative
# Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
#
# 

unless defined? Arrow::TestCase
	testsdir = File::dirname( File::expand_path(__FILE__) )
	basedir = File::dirname( testsdir )
	$LOAD_PATH.unshift "#{basedir}/lib" unless
		$LOAD_PATH.include?( "#{basedir}/lib" )
	$LOAD_PATH.unshift "#{basedir}/tests" unless
		$LOAD_PATH.include?( "#{basedir}/tests" )

	require 'mctestcase'
end


### Collection of tests for the MemCache class.
class RequireTestCase < MemCache::TestCase

	### Instance test
	def test_00_Require
		assert_nothing_raised { require 'memcache' }
		assert_kind_of Class, MemCache
	end
	
end

