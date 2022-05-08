#!/usr/bin/ruby
# 
# This is an abstract test case class for building Test::Unit unit tests for the
# MemCache module. It consolidates most of the maintenance work that must be
# done to build a test file by adjusting the $LOAD_PATH appropriately, as well
# as adding some other useful methods that make building and maintaining the
# tests much easier (IMHO). See the docs for Test::Unit for more info on the
# particulars of unit testing.
# 
# == Synopsis
# 
#	# Allow the unit test to be run from the base dir, or from tests/ or
#	# similar:
#	begin
#		require 'tests/mctestcase'
#	rescue
#		require '../mctestcase'
#	end
#
#	class MySomethingTest < MemCache::TestCase
#		def setup
#			super()
#			@foo = 'bar'
#		end
#
#		def test_00_something
#			obj = nil
#			assert_nothing_raised { obj = MySomething::new }
#			assert_instance_of MySomething, obj
#			assert_respond_to :myMethod, obj
#		end
#	end
# 
# == Subversion ID
# 
# $Id: mctestcase.rb 69 2005-08-24 22:28:20Z ged $
# 
# == Authors
# 
# * Michael Granger <ged@FaerieMUD.org>
# 
#:include: COPYRIGHT
#
#---
#
# Please see the file COPYRIGHT in the 'docs' directory for licensing details.
#

begin
	basedir = File::dirname( File::dirname(__FILE__) )
	unless $LOAD_PATH.include?( "#{basedir}/lib" )
		$LOAD_PATH.unshift "#{basedir}/lib"
	end
end

require 'test/unit'
require 'memcache'

class MemCache::TestCase < Test::Unit::TestCase

	# The name of the file containing marshalled configuration values
	ConfigSaveFile = "core/tests/lib/test.cfg"

	@methodCounter = 0
	@setupBlocks = []
	@teardownBlocks = []
	class << self
		attr_accessor :methodCounter, :setupBlocks, :teardownBlocks
	end


	### Inheritance callback -- adds @setupBlocks and @teardownBlocks ivars
	### and accessors to the inheriting class.
	def self::inherited( klass )
		klass.module_eval {
			@setupBlocks = []
			@teardownBlocks = []

			class << self
				attr_accessor :setupBlocks, :teardownBlocks
			end
		}
		klass.methodCounter = 0
	end



	### Output the specified <tt>msgs</tt> joined together to
	### <tt>STDERR</tt> if <tt>$DEBUG</tt> is set.
	def self::debugMsg( *msgs )
		return unless $DEBUG
		self.message "DEBUG>>> %s" % msgs.join('')
	end

	### Output the specified <tt>msgs</tt> joined together to
	### <tt>STDOUT</tt>.
	def self::message( *msgs )
		$stderr.puts msgs.join('')
		$stderr.flush
	end


	### Add a setup block for the current testcase
	def self::addSetupBlock( &block )
		self.methodCounter += 1
		newMethodName = "setup_#{self.methodCounter}".intern
		define_method( newMethodName, &block )
		self.setupBlocks.push newMethodName
	end

	### Add a teardown block for the current testcase
	def self::addTeardownBlock( &block )
		self.methodCounter += 1
		newMethodName = "teardown_#{self.methodCounter}".intern
		define_method( newMethodName, &block )
		self.teardownBlocks.unshift newMethodName
	end


	#############################################################
	###	I N S T A N C E   M E T H O D S
	#############################################################

	def initialize( *args )
		if $Memcached
			# If there's no readable config file, prompt the user for configuration
			# values and make sure the logs are present and empty.
			unless File::readable?( ConfigSaveFile )
				File::open(ConfigSaveFile, File::CREAT|File::TRUNC|File::WRONLY) {|ofh|
					config = self.promptForConfigValues()
					Marshal::dump( config, ofh )
				}
			end

			File::open( ConfigSaveFile, File::RDONLY ) {|ifh|
				@config = Marshal::load( ifh )
			}


			# If the user said to skip when prompted, kill the config object.
			@config = nil if @config[:server] == :skip
		else
			@config = nil
		end

		super
	rescue => e
		File::unlink( ConfigSaveFile ) if File::exists?( ConfigSaveFile )
		Kernel::raise( e )
	end


	### Prompt the user with the given <tt>promptString</tt> via #prompt, or
	### #promptWithDefault if a default is given, and return the answer after
	### testing to make sure it's been filled. If it has not, a RuntimeError is
	### raised.
	def promptForRequiredValue( promptString, default=nil )
		res = nil
		
		if default
			res = promptWithDefault( promptString, default )
		else
			res = prompt( promptString )
		end
		
		if res.nil? || res.empty?
			raise RuntimeError,
				"Cannot test: missing required config value"
		end
		
		return res
	end


	### Prompt for configuration values
	def promptForConfigValues
		puts "The test suite can run quite a lot of tests against ",
			"a real memcache server, if you have one running."
		ans = promptWithDefault( "Do you want to run these tests?", "y" )

		unless /^y/i.match( ans )
			return { :server => :skip }
		end

		config = {}
		config[:server] =
			promptForRequiredValue( "Memcached host or ip", "localhost" )
		config[:port] =
			promptForRequiredValue( "Memcached port", "11211" )

		return config
	end


	### A dummy test method to allow this Test::Unit::TestCase to be
	### subclassed without complaining about the lack of tests.
	def test_0_dummy
	end


	### Forward-compatibility method for namechange in Test::Unit
	def setup( *args )
		self.class.setupBlocks.each {|sblock|
			debugMsg "Calling setup block method #{sblock}"
			self.send( sblock )
		}
		super( *args )
	end
	alias_method :set_up, :setup


	### Forward-compatibility method for namechange in Test::Unit
	def teardown( *args )
		super( *args )
		self.class.teardownBlocks.each {|tblock|
			debugMsg "Calling teardown block method #{tblock}"
			self.send( tblock )
		}
	end
	alias_method :tear_down, :teardown


	### Skip the current step (called from #setup) with the +reason+ given.
	def skip( reason=nil )
		if reason
			msg = "Skipping %s: %s" % [ @method_name, reason ]
		else
			msg = "Skipping %s: No reason given." % @method_name
		end

		$stderr.puts( msg ) if $VERBOSE
		@method_name = :skipped_test
	end


	def skipped_test # :nodoc:
	end     


	### Add the specified +block+ to the code that gets executed by #setup.
	def addSetupBlock( &block ); self.class.addSetupBlock( &block ); end


	### Add the specified +block+ to the code that gets executed by #teardown.
	def addTeardownBlock( &block ); self.class.addTeardownBlock( &block ); end


	### Instance alias for the like-named class method.
	def message( *msgs )
		self.class.message( *msgs )
	end


	### Instance alias for the like-named class method
	def debugMsg( *msgs )
		self.class.debugMsg( *msgs )
	end


	### Output a separator line made up of <tt>length</tt> of the specified
	### <tt>char</tt>.
	def writeLine( length=75, char="-" )
		$stderr.puts "\r" + (char * length )
	end


	### Output a header for delimiting tests
	def printTestHeader( desc )
		return unless $VERBOSE || $DEBUG
		message ">>> %s <<<" % desc
	end


	### Try to force garbage collection to start.
	def collectGarbage
		a = []
		1000.times { a << {} }
		a = nil
		GC.start
	end


	### Output the name of the test as it's running if in verbose mode.
	def run( result )
		$stderr.puts self.name if $VERBOSE || $DEBUG

		# Support debugging for individual tests
		olddb = nil
		if $DebugPattern && $DebugPattern =~ @method_name
			olddb = $DEBUG
			$DEBUG = true
		end

		super

		$DEBUG = olddb unless olddb.nil?
	end


	#############################################################
	###	E X T R A   A S S E R T I O N S
	#############################################################

	### Negative of assert_respond_to
	def assert_not_respond_to( obj, meth )
		msg = "%s expected NOT to respond to '%s'" %
			[ obj.inspect, meth ]
		assert_block( msg ) {
			!obj.respond_to?( meth )
		}
	rescue Test::Unit::AssertionFailedError => err
		cutframe = err.backtrace.reverse.find {|frame|
			/assert_not_respond_to/ =~ frame
		}
		firstIdx = (err.backtrace.rindex( cutframe )||0) + 1
		Kernel::raise( err, err.message, err.backtrace[firstIdx..-1] )
	end


	### Assert that the instance variable specified by +sym+ of an +object+
	### is equal to the specified +value+. The '@' at the beginning of the
	### +sym+ will be prepended if not present.
	def assert_ivar_equal( value, object, sym )
		sym = "@#{sym}".intern unless /^@/ =~ sym.to_s
		msg = "Instance variable '%s'\n\tof <%s>\n\texpected to be <%s>\n" %
			[ sym, object.inspect, value.inspect ]
		msg += "\tbut was: <%s>" % object.instance_variable_get(sym)
		assert_block( msg ) {
			value == object.instance_variable_get(sym)
		}
	rescue Test::Unit::AssertionFailedError => err
		cutframe = err.backtrace.reverse.find {|frame|
			/assert_ivar_equal/ =~ frame
		}
		firstIdx = (err.backtrace.rindex( cutframe )||0) + 1
		Kernel::raise( err, err.message, err.backtrace[firstIdx..-1] )
	end


	### Assert that the specified +object+ has an instance variable which
	### matches the specified +sym+. The '@' at the beginning of the +sym+
	### will be prepended if not present.
	def assert_has_ivar( sym, object )
		sym = "@#{sym}" unless /^@/ =~ sym.to_s
		msg = "Object <%s> expected to have an instance variable <%s>" %
			[ object.inspect, sym ]
		assert_block( msg ) {
			object.instance_variables.include?( sym.to_s )
		}
	rescue Test::Unit::AssertionFailedError => err
		cutframe = err.backtrace.reverse.find {|frame|
			/assert_has_ivar/ =~ frame
		}
		firstIdx = (err.backtrace.rindex( cutframe )||0) + 1
		Kernel::raise( err, err.message, err.backtrace[firstIdx..-1] )
	end


	### Assert that the specified +object+ is either nil or equal (via #eql?) to
	### the given +value+. This is to test return values from the cache, which
	### should return either the original value or +nil+ if the value has
	### expired from the cache.
	def assert_nil_or_equal( value, object, msg="" )
		msg += "\n" unless msg.empty?
		msg += "Object <%p> expected to be nil or equal to <%p>" %
			[ object, value ]
		assert_block( msg ) {
			object.nil? || object == value
		}
	rescue Test::Unit::AssertionFailedError => err
		cutframe = err.backtrace.reverse.find {|frame|
			/assert_nil_or_equal/ =~ frame
		}
		firstIdx = (err.backtrace.rindex( cutframe )||0) + 1
		Kernel::raise( err, err.message, err.backtrace[firstIdx..-1] )
	end

end # class MemCache::TestCase

