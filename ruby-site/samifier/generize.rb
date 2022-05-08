require 'rubygems'
require 'hpricot'
require 'yaml'
require 'fileutils'
require 'samifier/lib/samifier'



#
# Two Responsibilities need representation
#
# "SAM" Construct
# => Language specific String Index file
# "SAT" Construct
# =>  Template that has been correlated to a String Index.
#
#  Over all Behavior that Needs Representation
#
# Re-Generation of Samified Site 
# => Converting all un Samilized Text on Site to Samified Nodes
#
# Update of language Sam File
# => For every language currently being managed new Samified Content needs to be appended

#
# Convert the HTML files hanging around into Saml Template Chunks
#
# For now we are going to put everything into one file SamFactory::SAM_FILE
#
# If it gets bigger we can organize them according to what files the strings were
# pulled from
#



class SiteSamifier
	BACKUP_DIR = '.sam_backups/'
	#
	# testing aid
	attr_reader :site_lang_hash 
	def initialize(factory = SamFactory)
		@factory = factory		
		@languages = factory.languages
		@site_lang_sam = {}
		base_dir = ENV['SITE_BASE_DIR']
		
		@factory.storage_dir = base_dir + "/" + @factory.storage_dir
		@files = Dir["#{base_dir}/**/*.html"]		
	end
	

	#
	#
	# Main Entry Point.
	#
	# When the Templates are Changed.. Update the Site
	#
	def update_site(replace = false)
		#
		# Extract Strings first
		#
		store_site_sam
		#
		# Overwrite tempates with stringless Templates
		#
		store_site_templates() if replace
	end
	
	def storage_dir
		@factory.storage_dir
	end
	
	def storage_dir=(dir)
		@factory.storage_dir = dir
	end
	
	
	def each_template_samify(&block)
			@files.each {|file|
				File.open(file){|io|
					gs = TemplateSamifier.new(io)
					if block.arity == 2
						yield gs,file
					else
						yield gs
					end
					
				}
			}
	end
	private :each_template_samify

	def store_site_templates()
		each_template_samify {|gs,filepath|	
			move_aside_template(filepath)
			overwrite_template(gs,filepath) 
		}
	end	
	
	def sam_filename(lang)
		filename = @factory.sam_path(lang)
	end	

	def load_site_sam()	
		@languages.each {|lang|
			 load_site_lang(lang)
		}
	end	

	def load_site_lang(lang)
		sam = Samifier.new(lang)
		@site_lang_sam[lang] = sam
	end

	def store_site_sam()
		@languages.each {|lang| 	
			load_site_lang(lang)
			#
			# Ensure that we have converted the Sam to a Site_hash
			#
			site_hash = @site_lang_sam[lang].to_hash
			each_template_samify {|gs,filepath|		
				site_hash = site_hash.merge(gs.to_hash) {|key, oldval, newval|
					# in the event that there is a merge conflict keep the value
					# that is coming out of the site_lang_sam
					# See unit-test 'test_conflict_detection'
					# 
					oldval
				}			
			}
						
			@factory.store_sam(lang,site_hash)
		}
	end
	
	#
	#
	# for testing
	def site_lang_hash(lang)
		@site_lang_sam[lang].to_hash
	end
	
	private
	

	def move_aside_template(filepath)
		FileUtils.mkdir_p "#{File.dirname(filepath)}/#{BACKUP_DIR}"
		FileUtils.mv filepath,"#{File.dirname(filepath)}/#{BACKUP_DIR}#{File.basename(filepath)}.#{Time.now.hash}"
	end

 	def overwrite_template(gs,filepath)	
			sat = gs.to_sam_template	
		#	puts "--> Replacing #{filepath}"					
			File.open(filepath,File::CREAT|File::TRUNC|File::RDWR) {|f|
				 f << sat
			}
	end

end



if __FILE__ == $0
		replace = ARGV.shift
		replace =  unless replace.nil? then  true else false end
		gh = SiteSamifier.new()
		gh.update_site replace
end