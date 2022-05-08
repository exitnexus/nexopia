require 'rubygems'
require 'hpricot'
require 'yaml'
require 'fileutils'	
#
# Samifier is a String Internationalizer built around Yaml
# 

class SamFactory
	SAM_EXT=".sam"
	SAM_FILE="site#{SAM_EXT}.yaml"
	DEFAULT_LANGS = %w{en} 
	

	
	def self.storage_dir
		@@storage_dir ||= 'i18n'		
	end
	
	def self.storage_dir=(dir)
		@@storage_dir = dir
	end
	
	def self.languages
		langs = DEFAULT_LANGS
		Dir["#{storage_dir}/*"].each {|dir|
			
				langs << File.basename(dir) if File.directory?(dir)
		}
		langs.uniq
	end
	def self.sam_path(language)		
		"#{storage_dir}/#{language.to_s}/#{SAM_FILE}"
	end
	
	#
	#
	#
	def self.load_sam(language)
		filename = self.sam_path(language)
		 array_hash = filename_to_array_hash(filename)
		 site_hash = array_hash.map {|e| {e[0] => e[2], e[1] => e[2].strip} }.inject({}) {|memo,e| memo.merge(e)}
	end
	
	def self.store_sam(language,site_hash)
	
		filename = self.sam_path(language)
		FileUtils.mkdir_p File.dirname(filename)
			#
			# Store as Sorted yaml Array
			#
			File.open(filename,File::CREAT|File::TRUNC|File::RDWR) { |f|				
				#
				# Ensure site_hash is converted to proper hash
				#
				f << hash_to_yaml(site_hash)
			}
			site_hash
	end
	
	def self.filename_to_array_hash(filename)
		begin	
			array_hash = YAML::load(IO.read(filename))
		rescue
			array_hash = []
		end
	end

	def self.hash_to_yaml(hash)
		array = []
		#  Using to_s to cause
		#  Lexigraphical sort
		
		hash.sort {|a,b| a[0].to_s <=> b[0].to_s }.map {|elm|		
			original = elm[0]
			id = original.hash
			translated = elm[1]
			array << [id,original,translated]
		}
		array.to_yaml
	end
end

#
#
# Samifier is an Access point to the Sam Data
#
#
#

class Samifier
	attr_accessor :factory
	def initialize(language,_factory = SamFactory)
		@language = language
		@factory = _factory
		@sam_hash = {}
		load
		self
	end
	
	def load()		
	 	@sam_hash = factory.load_sam(@language)
		self
	end
	def method_missing(method,*args,&block)
		if @sam_hash.respond_to? method
			that =  @sam_hash.send(method,*args,&block) 
		else
			raise NoMethodError.new(method.to_s, *args)
		end
		that
	end
	def [](key)
		@sam_hash[key]
	end
	#
	#
	# filter out all non Numberic hash Keys.. Each numeric
	# hash key corresponds to a unique non numeric hash key with
	# the same value.
	def to_hash
		@sam_hash.select {|key,value|
			not key.kind_of? Numeric
		}.inject({}) {|memo,e| memo.merge({e[0] => e[1]})}
	end
	#
	# Return a slice of the hash that is only numeric ids to the
	# value
	#
	def to_id_hash
		@sam_hash.select {|key,value|
			key.kind_of? Numeric
		}.inject({}) {|memo,e| memo.merge({e[0] => e[1]})}
	end	
end


#
#
# the purpose of this class is to look in all nodes for
# text.
#
# These nodes are potential candidates for becoming "Samified"
#
#
class Hpricot::Doc
	#
	# Iterate over nodes that have text in them
	# that are dummy stupid head nodes like script
	# and style
	#
	# TODO: Don't include other stuff
	#
	def each_text_node()
			(self/'//*').each {|e|

				e.children.each {|c|
			 		if c.text?							
						yield c 	unless c.content.to_s.strip == nil.to_s
					end
				} unless e.name =~ /script|style/
			}
	end
end
class TemplateSamifier
	
	def initialize(io,factory = SamFactory)
		@factory = SamFactory 
		@doc = Hpricot::XML(io)
	end
	
	def to_a
		list = []
		@doc.each_text_node {|node|
			list << node.content.strip
		}
		list.uniq.sort
	end
	
	
	#
	# this should goto factory
	#
	def to_yaml
		@factory.hash_to_yaml(self.to_hash)
	end
	
	def to_hash
		list = to_a
		hash = {}
		list.each {|e|
				hash[e] = e
		}	
		hash
	end	
	
	def to_sam_template
		sat_doc = Hpricot::XML(@doc.to_html)	
		sat_doc.each_text_node() {|node|
			content = node.content.strip	
			node.swap("<t:sam sam_id='#{content.hash}'><!-- #{content} --></t:sam>")
		}
		sat_doc.to_original_html
		sat_doc.to_html
	end
	
	#
	# for testing
	def store_yaml(filename)
		Object.class_eval "
			class TempFactory < #{@factory.to_s}
				def self.sam_path(blah)
					'#{filename}'
				end				
			end
			"
		TempFactory.store_sam("NOTHING",to_hash)
	end
end
