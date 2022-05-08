require 'test/unit/failure'
require 'test/unit/error'

lib_require :Core, 'storable/storable'

class TestStatus < Storable
	set_enums(:type => {:unknown => 0, :error => 1, :failure => 2});
	init_storable(:taskdb, 'teststatus')
	
	def initialize(error=nil, revision = 0, author = "none")
		super()
		if (error)
			self.test = error.short_display[/^(.*?)\(/, 1]
			self.testclass = error.short_display[/^.*?\((.*?)\)/, 1]
			self.content = error.long_display
			if (error.kind_of? Test::Unit::Error)
				self.type = :error
			elsif (error.kind_of? Test::Unit::Failure)
				self.type = :failure
			end
			if (self.type == :error)
				self.testerror = error.short_display[/^.*?\(.*?\)\:\s(.*?)\:\s/, 1]
			else
				self.testerror = "Test Failure"
			end
			self.revision_broken = revision
			self.author_broken = author
		end
	end
	
	def store
		if (self.modified?)
			self.lastupdated = Time.now.to_i
		end
		super
	end
	
	def ===(object)
		return false unless object.kind_of?(TestStatus)
		return self.test == object.test && self.testclass == object.testclass && self.testerror == object.testerror
	end
	
	def fix(revision, author)
		self.revision_fixed = revision
		self.author_fixed = author
	end
	
	#return the class object for the test
	def test_class
		return Object.const_get(self.testclass)
	end

	class << self
		def failures
			return self.find(:all, :conditions => ["revision_fixed = 0 && type = ?", self.enums[:type][:failure]])
		end
		
		def errors
			return self.find(:all, :conditions => ["revision_fixed = 0 && type = ?", self.enums[:type][:error]])			
		end
		
		def broken
			return self.find(:all, :conditions => ["revision_fixed = 0"])			
		end
	end
end