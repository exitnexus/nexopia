require 'set'
#require "test/unit/ui/console/testrunner"
lib_require :Core, 'inheritance'
lib_require :Devutils, 'quiz', 'test_status', 'defect_tag'

module Kernel
	def test_require(mod, *libs)
		return lib_require_type(:test, mod, *libs)
	end

	def test_want(mod, *libs)
		return lib_want_type(:test, mod, *libs)
	end
end

class Tests
	POSITIVE_MESSAGES = [
	"You will find 72 virgins in heaven.",
	"A thousand blessings be upon you.",
	":)",
	"May you use this lightsaber well young jedi. o--o>>>>>>>>>>>>>>>>>>",
	"You're good enough, you're smart enough, and gosh darnit, people like you!"
	]

	NEGATIVE_MESSAGES = [
		"YOU BROKE IT YOU JERK!",
		"May you be forced to use internet explorer for eternity.",
		"May the fleas of a thousand camels infest your harem!",
		"I bite my thumb at thee!",
		"You're just about as sharp as a marble.",
		"You're as bright as mud!",
		"Why you litte maggot, you make me want to vomit!"
	]
	
	attr_accessor(:base_dir, :tests, :errors, :files, :revision, :author, :defects, :new_defects, :fixed_defects)

	def initialize(base_dir)
		self.base_dir = base_dir
		self.errors = [];
		@test_results = [];
		initialize_tests
	end

	#run a test, test_class can be a string or class object, output is optional and can be any iostream
	def run(test_class, output=$>)
		unless (test_class.kind_of?(Class))
			@found_classes.each{|found_class|
				if (found_class.name.upcase == test_class.upcase)
					test_class = found_class
					break
				end
			}
		end
		unless (test_class.kind_of?(Class) && test_class.descendant_of?(Test::Unit::TestCase))
			return nil
		end
		begin
			@test_results << Test::Unit::UI::Console::TestRunner.new(test_class, 2, output).start();
			@test_results.last.meta.test_class = test_class
			return @test_results.last
		rescue Exception
			self.errors << $!
			return nil
		end
	end

	#run every test, clears any stored test results before starting
	def run_all(output=$>)
		clear_results #if we're going to run all the tests lets start with a clean slate
		@found_classes.each{|test|
			run(test, output)
		}
		begin
			return @test_results
		rescue Exception
			self.errors << $!
			return nil
		end
	end
	
	
	def process_results
		self.defects = convert_to_test_status(@test_results)
		
		#find the preexisting unfixed TestStatus objects
		previous_defects = TestStatus.broken

		self.new_defects = [];
		self.fixed_defects = [];

		#determine which defects are new and which defects have been fixed
		defects.each {|defect|
			previous_defects.each {|previous_defect|
				if (defect === previous_defect)
					previous_defect.meta.still_exists = true
					defect.meta.previously_existed = true
					if (previous_defect.content != defect.content)
						previous_defect.content = defect.content
						previous_defect.store #update any previous defects error messages
					end
				end
			}
			unless (defect.meta.previously_existed)
				new_defects << defect
			end
		}
		previous_defects.each {|defect|
			unless (defect.meta.still_exists)
				fixed_defects << defect
			end
		}
		
		process_new_defects
		process_fixed_defects
		clear_results
	end
	
	#clear all stored test results, automatically happens before running all tests
	def clear_results
		@test_results = [];
	end
	
	#return an html string of all the recorded errors
	def html_errors
		output = ""
		self.errors.each {|error|
			output += "<div style=\"background-color:FFDDDD;color:red;font-weight:bold\"><hr/>" + error.to_s + "<ol>"
			error.backtrace.each {|trace|
				output += "<li style='color:black;font-weight:normal'>#{trace}</li>"
			}
			output += "</ol><hr/></div>"

		}
		return output
	end

	class << self
		alias hidden_new new
		undef_method :new
		def instance(path=".")
			@@loaded_tests ||= Hash.new
			return @@loaded_tests[path] ||= Tests.hidden_new(path)
		end
	end

	private
	def initialize_tests
		build_files_hash()
		@found_classes ||= Set.new
		self.tests ||= Hash.new;
		@reverse_tests ||= Hash.new
		files.each_pair {|current_module, files_list|
			files_list.each {|file|
				begin
					require file
				rescue Exception
					errors << $!
				end
			}
			self.tests[current_module] ||= []
			ObjectSpace.each_object(Class) {|test_class|
				if (test_class.descendant_of?(Quiz) && !@found_classes.include?(test_class))
					self.tests[current_module] << test_class
					@reverse_tests[test_class] = current_module
					@found_classes.add(test_class)
				end
			}
			self.tests[current_module] = self.tests[current_module].sort_by {|a_class| a_class.name}
			self.tests.delete(current_module) if (self.tests[current_module].empty?)
		}
	end

	def build_files_hash()
		self.files ||= Hash.new
		Dir[self.base_dir + "/*"].each {|directory|
			self.files[File.basename(directory)] ||= [];
		}
		files.keys.each {|directory|
			files[directory] = find_files(Dir["#{self.base_dir}/#{directory}/tests/*"]);
		}
		return self.files
	end

	def find_files(list)
		ruby_files = [];
		list.each{|file|
			if (File.ftype(file) == 'directory')
				if (file.split('/').last == 'lib')
					next #skip directories in the tests directory named lib
				end
				ruby_files.concat(find_files(Dir["#{file}/*"]))
			elsif (file =~ /.*\.rb$/)
				ruby_files << file
			end
		}
		return ruby_files
	end
	
	def convert_to_test_status(results)
		#find all the defects from the TestResult objects
		test_defects = []
		results.each {|result|
			test_defects += result.instance_variable_get(:@failures) + result.instance_variable_get(:@errors)
		}
		#build TestStatus objects out of the defects
		test_defects = test_defects.map {|defect|
			TestStatus.new(defect)
		}
		return test_defects
	end
	
	def process_new_defects
		emails = {}
		
		#group new defects by email address
		new_defects.each {|defect|
			defect.test_class.emails.each {|email|
				emails[email] ||= []
				emails[email] << defect unless (email == "#{self.author}@nexopia.com")
			}
			test_module = site_module_get(@reverse_tests[defect.test_class])
			module_emails = DefectTag.users_for_tags(test_module.tags).map {|user| user.email}
			module_emails.each {|email|
				emails[email] ||= []
				emails[email] << defect unless (email == "#{self.author}@nexopia.com")
			}
			emails["#{self.author}@nexopia.com"] ||= []
			emails["#{self.author}@nexopia.com"] << defect #whoever is doing the breaking gets notified for all of the breaks
			defect.revision_broken = self.revision
			defect.author_broken = self.author
			defect.store
		}
		emails.each_pair {|email, defects|
			if (defects.length > 0)
				send_new_defect_email(email, defects)
			end
		}
	end
	
	def process_fixed_defects
		emails = {}
		
		#group fixed defects by email address
		fixed_defects.each {|defect|
			defect.test_class.emails.each {|email|
				emails[email] ||= []
				emails[email] << defect unless (email == "#{self.author}@nexopia.com")
			}
			test_module = site_module_get(@reverse_tests[defect.test_class])
			module_emails = DefectTag.users_for_tags(test_module.tags).map {|user| user.email}
			module_emails.each {|email|
				emails[email] ||= []
				emails[email] << defect unless (email == "#{self.author}@nexopia.com")
			}
			emails["#{self.author}@nexopia.com"] ||= []
			emails["#{self.author}@nexopia.com"] << defect #whoever is doing the fixing gets notified for all of the breaks
			defect.revision_fixed = self.revision
			defect.author_fixed = self.author
			defect.store
		}
		
		emails.each_pair {|email, defects|
			if (defects.length > 0)
				send_fixed_defect_email(email, defects)
			end
		}
	end
	
	def send_new_defect_email(address, defects)
		message = RMail::Message.new
		sio = StringIO.new
		sio.puts get_random_negative_message if (address == "#{self.author}@nexopia.com")
		sio.puts("#{defects.length} new test failures:\n")
		defects.each_with_index {|defect, index|
			sio.puts("#{index+1}) #{defect.content}")
		}
		message.body = sio.string

		message.header.to = address
		message.header.from = 'test-runner@nexopia.com'
		message.header.subject = "Revision #{self.revision} broke #{defects.length} test cases"
		message_text = RMail::Serialize.write("", message)
		Net::SMTP.start($site.config.mail_server, $site.config.mail_port) {|smtp|
			smtp.send_message(message_text, 'test-runner@nexopia.com', address)
		}
	end
	
	def send_fixed_defect_email(address, defects)
		message = RMail::Message.new
		sio = StringIO.new
		sio.puts get_random_positive_message if (address == "#{self.author}@nexopia.com")
		sio.puts("#{defects.length} tests were fixed:\n")
		defects.each_with_index {|defect, index|
			sio.puts("#{index+1}) #{defect.content}")
		}
		message.body = sio.string

		message.header.to = address
		message.header.from = 'test-runner@nexopia.com'
		message.header.subject = "Revision #{self.revision} fixed #{defects.length} test cases"
		message_text = RMail::Serialize.write("", message)
		Net::SMTP.start($site.config.mail_server, $site.config.mail_port) {|smtp|
			smtp.send_message(message_text, 'test-runner@nexopia.com', address)
		}
	end
	
	def get_random_array_entry(array)
		return array[rand(array.length)]
	end
	
	def get_random_positive_message
		return get_random_array_entry(POSITIVE_MESSAGES)
	end
	
	def get_random_negative_message
		return get_random_array_entry(NEGATIVE_MESSAGES)
	end

end
