begin
	require 'ruby-prof'
rescue LoadError
end


class Class
	alias_method :orig_new, :new

	def Class.counter_init
		@@count = 0
		@@stoppit = false
		@@class_caller_count = Hash.new{|hash,key| hash[key] = Hash.new(0)}
	end

	Class.counter_init()

	def count_new(*arg,&blk)
		unless @@stoppit
			@@stoppit = true
			@@count += 1
			@@class_caller_count[self][caller[0]] += 1
			@@stoppit = false
		end
		orig_new(*arg,&blk)
	end

	def mark_new(*arg,&blk)
		s = orig_new(*arg,&blk)
		s.instance_variable_set(:"@__creation_marker__", caller) if self != Lazy::Promise
		return s
	end
	
	def Class.report_final_tally
		@@stoppit = true
		puts "Number of objects created = #{@@count}"

		total = Hash.new(0)

		@@class_caller_count.each_key do |klass|
			caller_count = @@class_caller_count[klass]
			caller_count.each_value do |count|
				total[klass] += count
			end
		end

		klass_list = total.keys.sort{|klass_a, klass_b| 
			a = total[klass_a]
			b = total[klass_b]
			if a != b
				-1* (a <=> b)
			else
				klass_a.to_s <=> klass_b.to_s
			end
		}
		klass_list.each do |klass|
			puts "#{total[klass]}\t#{klass} objects created."
			caller_count = @@class_caller_count[ klass]
			caller_count.keys.sort_by{|call| -1*caller_count[call]}.each do |call|
				puts "\t%5i - %s" % [caller_count[call], call]
			end
			puts ""
		end
	end
end


module Devutils
	class ProfileHandler < PageHandler
		declare_handlers("webrequest") {
			area :Internal

			access_level :Any
			handle :GetRequest, :profile_page,      input(String), "profile-page", remain
			handle :GetRequest, :profile_page_text, input(String), "profile-page-text", remain
			handle :GetRequest, :profile_page_tree, input(String), "profile-page-tree", remain

			handle :GetRequest, :profile_alloc,      input(String), "profile-alloc", remain
			handle :GetRequest, :profile_alloc_text, input(String), "profile-alloc-text", remain
			handle :GetRequest, :profile_alloc_tree, input(String), "profile-alloc-tree", remain

			handle :GetRequest, :profile_memory,      input(String), "profile-memory", remain
			handle :GetRequest, :profile_memory_text, input(String), "profile-memory-text", remain
			handle :GetRequest, :profile_memory_tree, input(String), "profile-memory-tree", remain

			handle :GetRequest, :profile_create,      input(String), "profile-create", remain
			handle :GetRequest, :profile_leak,      input(String), "profile-leak", remain
		}


		#Add profiling to count the number and types of objects created.
		def profile_create(host, remain)
			request.reply.headers['Content-type'] = 'text/text';

			#enable profiling!
			Class.counter_init();
			Class.send(:alias_method, :new, :count_new);

			subrequest(StringIO.new(), request.method, "/webrequest/#{host}/#{remain.join '/'}",
					   nil, :Internal);

			#disable profiling!
			Class.send(:alias_method, :new, :orig_new);

			Class.report_final_tally
		end

		def local_scope(host, remain)
			$site.cache.use_context({}){
				subrequest(StringIO.new(), request.method, "/webrequest/#{host}/#{remain.join '/'}",
					   nil, :Internal);
				Storable.internal_cache().clear
			}
		end

		#Add profiling to count the number and types of objects created.
		def profile_leak(host, remain)
			request.reply.headers['Content-type'] = 'text/text';

			#enable profiling!
			Class.counter_init();
			Class.send(:alias_method, :new, :mark_new);
			local_scope(host, remain)
			
			#disable profiling!
			Class.send(:alias_method, :new, :orig_new);

			GC.start
			counter = 0
			ObjectSpace.each_object{|o|
				if (o.instance_variable_get(:"@__creation_marker__"))
					puts o.class
					str = o.instance_variable_get(:"@__creation_marker__").join(", ")
					puts str if str !~ /storable\.rb/
					puts "\n\n\n\n"
					counter += 1
				end
			}
			$log.info "#{counter} leaks detected"
		end

		#Profile the time taken to run a page
		def profile_page(host, remain)
			profiler(host, remain, RubyProf::GraphHtmlPrinter)
		end
		def profile_page_text(host, remain)
			profiler(host, remain, RubyProf::FlatPrinter)
		end
		def profile_page_tree(host, remain)
			request.reply.headers['Content-disposition'] = 'attachment; filename=page_calltree.out';
			profiler(host, remain, RubyProf::CallTreePrinter)
		end

		#Profile the number of allocations taken to run a page, needs a patched GC
		def profile_alloc(host, remain)
			profiler(host, remain, RubyProf::GraphHtmlPrinter, RubyProf::ALLOCATIONS)
		end
		def profile_alloc_text(host, remain)
			profiler(host, remain, RubyProf::FlatPrinter, RubyProf::ALLOCATIONS)
		end
		def profile_alloc_tree(host, remain)
			request.reply.headers['Content-disposition'] = 'attachment; filename=alloc_calltree.out';			
			profiler(host, remain, RubyProf::CallTreePrinter, RubyProf::ALLOCATIONS)
		end

		#Profile the amount of memory taken to run a page, needs a patched GC
		def profile_memory(host, remain)
			profiler(host, remain, RubyProf::GraphHtmlPrinter, RubyProf::MEMORY)
		end
		def profile_memory_text(host, remain)
			profiler(host, remain, RubyProf::FlatPrinter, RubyProf::MEMORY)
		end
		def profile_memory_tree(host, remain)
			request.reply.headers['Content-disposition'] = 'attachment; filename=memory_calltree.out';			
			profiler(host, remain, RubyProf::CallTreePrinter, RubyProf::MEMORY)
		end

		# profile-page runs a subrequest and outputs profiler information about it.
		# Requires that you install the ruby-prof gem.
		def profiler(host, remain, printer_type = RubyProf::GraphHtmlPrinter, measure_mode = RubyProf::PROCESS_TIME)
#			GC.start
#			GC.disable

			RubyProf.measure_mode = measure_mode

			# Profile the code
			result = RubyProf.profile do
				subrequest(StringIO.new(), request.method, "/webrequest/#{host}/#{remain.join '/'}",
						   nil, :Internal);
			end

#			GC.enable

			# Print a graph profile to text
			printer = printer_type.new(result)
			#request.reply.send_headers(); # not sure why I have to do this?
			printer.print($stdout, :min_percent => 0.1)
		end
	end
end

