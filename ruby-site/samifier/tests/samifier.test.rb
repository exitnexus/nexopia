require 'rubygems'
require 'hpricot'
require 'yaml'
require 'fileutils'
require 'samifier/lib/samifier' # causes unit tests to be run
require 'samifier/generize'
require 'mocha'

lib_require :Devutils, 'quiz'

module SamTestHelpers
	STORAGE_DIR =Time.now.to_i.to_s
	PRETEND_LANGUAGES=%w{ pine spunky fish klingon fr gr en pie sauce}.sort
	#

	def build_temp_lang_dirs
		SamFactory.storage_dir = STORAGE_DIR
		PRETEND_LANGUAGES.each {|dir|
			FileUtils.mkdir_p "#{SamFactory.storage_dir}/#{dir}"
		}
	end
	def remove_temp_lang_dirs
		FileUtils.rm_rf SamFactory.storage_dir
	end
end


class SamFactoryTest < Quiz
	include SamTestHelpers
	def setup		

		#
		build_temp_lang_dirs()
	end
	def teardown

		remove_temp_lang_dirs
	end
	def test_load_store
		site_hash = {:a => 'lskdlkasdf', :b => 'dsfasdfasdf', :c => 'sdfasdkf23'}
		expected_site_hash = {:a => 'lskdlkasdf', :b => 'dsfasdfasdf', :c => 'sdfasdkf23',
						 :a.hash => 'lskdlkasdf', :b.hash => 'dsfasdfasdf', :c.hash => 'sdfasdkf23'}
		SamFactory.store_sam(:pie.to_s,site_hash)
		actual_site_hash = SamFactory.load_sam(:pie.to_s)
		assert_equal(expected_site_hash,actual_site_hash)
	
	end
	
	def test_known_languages
	
		assert_equal(PRETEND_LANGUAGES,SamFactory.languages)
	
	end
end

class SamifierTests < Quiz
		include SamTestHelpers
	
		def setup
		
			SamFactory.storage_dir = STORAGE_DIR
			
		end
		def teardown

		 	FileUtils.rm_rf SamFactory.storage_dir
		end
		
		def _input_yaml
			#
			# These strings must begin with no whitespace		
			%q|
--- 
There are rabbits: There are rabbits
NO RABBITS: NO RABBITS
Hello: Hello
YES BUNNY!: The Bunny is a Spunky sort of Rabbit. Very Cute and Made of Bannanas
|
		end

		
		
		def load_from_disk_filename(language)
			SamFactory.sam_path language
		end

		def setup_load_from_disk(language)
			filename = load_from_disk_filename(language)
			FileUtils.mkdir_p File.dirname(filename)
			File.open(filename, File::RDWR|File::CREAT|File::TRUNC) {|f|
				f << %Q|
---
- - 0
  - The mystery of sugar
  - SUGAR AND ALMOND HALVA MAKE LOVELY FRESH KISSES
- - 1
  - Friends
  - Mah Hommies
				|
			}
		end

		def teardown_load_from_disk(language)
			FileUtils.rm_rf(File.dirname(load_from_disk_filename(language)))
		end
		#
		# Test
		#
		def test_load_from_disk
			language = :test_load_from_disk
			setup_load_from_disk(language)
			sammy = Samifier.new(language)
			assert_equal("Mah Hommies",sammy["Friends"])
			teardown_load_from_disk(language)
		end	
		# 
		# Test Factory
		# load method with mock factory
		#
		def test_load_spunky

			factory = mock()
			factory.expects(:load_sam).returns(YAML::load(_input_yaml))
			spunky = Samifier.new(:spunky,factory)
			key = ""
			expected = 'The Bunny is a Spunky sort of Rabbit. Very Cute and Made of Bannanas'
			actual = spunky["YES BUNNY!"]		
			assert_equal(expected,actual)
		end
	
end

class TemplateSamifierTest < Quiz
	

	def _output_xml
		input = %Q|
			<a>
			<b>
				<c> <t:sam sam_id='681567962' /> <!-- Hello --> </c>
				<d> <t:sam sam_id='139939817' /> <!-- There are rabbits --> <w> <t:sam sam_id='-199649415' /> <!-- NO RABBITS --> </w> <t:sam sam_id='-934942714' /> <!-- YES BUNNY! --></d>
			</b>
			</a>
		|
	end
	
	def test_store_sam_template
		ts = TemplateSamifier.new(_input_xml)
		actual = ts.to_sam_template
		actual_doc = Hpricot(actual)
		expected_doc = Hpricot::XML(_output_xml)
		match = (actual_doc/"//sam")
		assert match.size > 0
		match.each {|el|
			assert_equal((expected_doc/"//sam[@sam_id=#{el['sam_id']}]").first['sam_id'],el['sam_id'])
		}
	end
	
	def _input_xml
		input = %Q|
		<!-- Hi  -->
			<script>
				/*<![CDATA[ */
					
					THE PANTS ARE SAFE?
					
				/*]]>*/
			</script>
			<a>
				<b>
					<c> Hello </c>
					<d> There are rabbits <w> NO RABBITS </w> YES BUNNY!</d>
				</b>
			</a>
		|
	end
	def test_comment_keep
		exp_comment = _input_xml.scan("<!-- Hi  -->")
		ts = TemplateSamifier.new(_input_xml)
		template =	ts.to_sam_template
		act_comment =template.scan("<!-- Hi  -->")
		assert_equal(exp_comment,act_comment)
	end
	def test_cdata_keep
		exp_start_cdata = _input_xml.scan("<![CDATA[")
	   exp_end_cdata = _input_xml.scan("]]>")
			ts = TemplateSamifier.new(_input_xml)
		template =	ts.to_sam_template
		actual_start_cdata =template.scan("<![CDATA[")
		actual_end_cdata = template.scan("]]>")
		assert_equal(exp_start_cdata,actual_start_cdata)
		assert_equal(exp_end_cdata,actual_end_cdata)
	end
	
	def test_store_yaml
			expected_file_contents = {
				'Hello' => "Hello",
				'There are rabbits' => "There are rabbits",
				'YES BUNNY!' => "YES BUNNY!",
				'NO RABBITS' => "NO RABBITS",
			}.to_a.sort.inject("--- \n") {|memo,elm|
				memo << "- - #{elm[0].hash}\n"
				memo << "  - #{elm[0]}\n"
				memo << "  - #{elm[1]}\n"
			}
			filename = "template.sam.yaml"
			gs = TemplateSamifier.new(_input_xml)
			
			gs.store_yaml(filename)
			assert(File.exists?(filename))
			assert_equal(expected_file_contents,IO.read(filename))
			FileUtils.rm filename
	end
	
	def test_list
		expected = [%q|Hello|,%q|There are rabbits|,%q|YES BUNNY!|,%q|NO RABBITS|].sort		
		ts = TemplateSamifier.new(_input_xml)
		yaml_list = ts.to_a.to_yaml
		assert_equal(expected.to_yaml,yaml_list)
	end
	def test_hash_english
		
		expected = {
			'Hello' => "Hello",
			'There are rabbits' => "There are rabbits",
			'YES BUNNY!' => "YES BUNNY!",
			'NO RABBITS' => "NO RABBITS",		
		}		
		ts = TemplateSamifier.new(_input_xml)
		yaml_hash = ts.to_hash.to_yaml
		assert_equal(expected.to_yaml,yaml_hash)
	end
end
class SiteSamifierTest < Quiz
	
	TEST_HTML_DIR = "test_sam_and_html_dir"
	
	
	#
	# Get
	# directory creation abilty
	# and mulit-languages
	include SamTestHelpers
	
	def setup
		build_temp_lang_dirs
		@old_base = ENV['SITE_BASE_DIR']
		ENV['SITE_BASE_DIR'] = "." # build and look for test dirs off the current directory. 
	end
	
	def teardown
		remove_temp_lang_dirs
		ENV['SITE_BASE_DIR'] = @old_base
	end
		
	def load_file_html_after
	"\n\t\t<html><body><pre></pre>\n\t\t<p><t:sam sam_id=\"-780432187\"><!-- This is a Sentance --></t:sam><b><t:sam sam_id=\"181338500\"><!-- And this is Bold --></t:sam></b><t:sam sam_id=\"461096601\"><!-- And Then we Talk about Fish --></t:sam></p>\n\t\t<ul>\n\t\t<li><t:sam sam_id=\"225845186\"><!-- List 1 --></t:sam></li>\n\t\t<li><t:sam sam_id=\"225845187\"><!-- List 2 --></t:sam></li>\n\t\t</ul>\n\t\t</body></html>\n\t\t"
	end
	
	def load_file_html
		%q|
		<html><body><pre></pre>
		<p> This is a Sentance <b> And this is Bold </b> And Then we Talk about Fish</p>
		<ul>
		<li> List 1</li>
		<li> List 2</li>
		</ul>
		</body></html>
		|
	end

	def setup_test_load_filename
		"#{TEST_HTML_DIR}/test_load.html"
	end
	def setup_test_load_file
		FileUtils.mkdir_p TEST_HTML_DIR
		File.open(setup_test_load_filename,File::CREAT|File::TRUNC|File::RDWR){|f|
			f << load_file_html
		}		
	end
	
	def teardown_test_load_file
		FileUtils.rm_rf TEST_HTML_DIR
	end
	
	def test_replace_template
		setup_test_load_file
		expected_content = load_file_html_after
		g = SiteSamifier.new()
		g.store_site_templates		
		actual_content = 	IO.read(setup_test_load_filename)
		assert_equal(expected_content,actual_content)
		teardown_test_load_file
	end
	def test_load_site_sam
		langs = ['en','gr','jp','zu']
		expected_en = {:pie => "yum"}
		expected_gr = {:pie => 'yummitag'}
		expected_jp = {:pie => 'tastu goodu'}
		expected_zu = {:pie => "FRAMBOOO_ZU"}
		langs.each {|lang|
			instance_eval %Q|
				SamFactory.store_sam(lang,expected_#{lang})		
				g = SiteSamifier.new()
				g.load_site_sam()				
				assert_equal(expected_#{lang},g.site_lang_hash(lang), "for language #{lang}")
			|
		}
	end

	def test_store_site_sam
		setup_test_load_file
		g = SiteSamifier.new()		
		
		g.store_site_sam()	
		SamFactory.languages.each {|lang|
			assert(File.exist?(g.sam_filename(lang)))				
		}
		teardown_test_load_file
	end
	
	
	#
	# How does samifier handle the case where 
	# there is a existing site.sam.yaml file 
	# that file is modified directly. 
	# Then the Generizer runs on a template which
	# has a text node that corresponds to the original
	# value of the Sam file.
	# 
	# The Site Sam should be considered the master copy.
	# If there is a record in it for a string and the samifier
	# is run on the site over again, then the original Site.Sam
	# value should remain. 
	def test_conflict_template
		# First Generate and Store the Template
		setup_test_load_file
		g = SiteSamifier.new()
		g.update_site true
		# 
		# Get the English name so we can alter the file on disk
		# emulating a change via an editor
		en_site_sam_path = SamFactory.sam_path('en')
		#
		# The Array_hash is currently in this form:
		# [  
		#  [id, orig_text, Internationalized ]
		# ]
		#
		#
		#
		array_hash = YAML::load(IO.read(en_site_sam_path))
		#
		# Now modify the last internationalize component of the
		# the array hash and write the array_hash
		# back to disk.
		modified_string = 'Miso gravy for your Heart!'
		original_string = array_hash.last[1]
		array_hash.last[2] = modified_string
		
		File.open(en_site_sam_path,File::CREAT|File::TRUNC|File::RDWR) { |f|
			f << array_hash.to_yaml
		}
		#
		# Check that the modification happend
		sam = Samifier.new('en')
		#
		# The original string forms an id from its hashed value
		#
		# This value should matched the modified value to be proper.
		assert_equal(modified_string,sam[original_string.hash])
	
		# Now after writing out the Array Hash. 
		# Re-Run the Samifier on the Original unmodified Template file
		# Then acces the Samifier and compare the modified_string to
		# the actual_string.
		# First Generate and Store the Template
		setup_test_load_file
		g = SiteSamifier.new()
		g.update_site true
		
		sam = Samifier.new('en')
		#
		# The original string forms an id from its hashed value
		#
		# This value should matched the modified value to be proper.
		assert_equal(modified_string,sam[original_string.hash])
	end
	
	#
	#
	# Test modifying templates
	#
	def test_modify_template
		setup_test_load_file
		g = SiteSamifier.new()

		#
		# Store the Template
		#
		g.store_site_templates()			
		# Store the same
		template_filename = setup_test_load_filename
		#
		# Add a text node
		inserted_string = "I LIKE CAKE AND BREAD!!!"
		# now modify the sam document
		doc = Hpricot(IO.read(template_filename))
		doc.at("pre").swap("<pre>#{inserted_string}</pre>")
		
		#
		# Overwrite the test file with the new modified template
		#
		File.open(template_filename,File::CREAT|File::TRUNC|File::RDWR) {|f|
			f << doc.to_html
		}
		# Re-Run the Generizer
		#
		g2 = SiteSamifier.new()
		g2.store_site_templates()

		#
		# Now Check to make sure things worked
		#						
		inserted_string_id = inserted_string.hash
		
		#
		# Open the document up again and look for the id
		doc = Hpricot(IO.read(template_filename))
		
		#
		# 1 element should match
		match = (doc/ "//sam[@sam_id=#{inserted_string_id}]")
	 	assert_equal(1,match.size)		
		teardown_test_load_file
	end
end