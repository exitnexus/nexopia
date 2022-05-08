class RubyInfo < PageHandler
	declare_handlers("/") {
		handle :GetRequest, :info, "rubyinfo"
	}
	
	def info
		require "markaby";
		request.reply.headers['X-width'] = 600
		mab = Markaby::Builder.new;
		table_info = {:border=>0, :cellpadding=>3, :cellspacing=>1, :width=>600}
		header_row_info = {:valign => "bottom"}
		data_row_info = {:valign => "baseline"}
		puts mab.html {
			head {
				title "Ruby Info"
				style %Q{<!--
A { text-decoration: none; }
A:hover { text-decoration: underline; }
H1 { font-family: arial,helvetica,sans-serif; font-size: 18pt; font-weight: bold;}
H2 { font-family: arial,helvetica,sans-serif; font-size: 14pt; font-weight: bold;}
BODY,TD { font-family: arial,helvetica,sans-serif; font-size: 10pt; }
TH { font-family: arial,helvetica,sans-serif; font-size: 11pt; font-weight: bold; }
TR.header { background-color: #9999CC; }
TR.data { background-color: #CCCCCC; }
TD.header { background-color: #CCCCFF; }
TD.data { text-align: center; }
//-->}
			}
			body {
				h1 "Table of Contents"
				a(:name => "toc")
				ol {
					li { h2 { a("Ruby", :href=>"#rbconfig") } }
					li { h2 { a("Gems", :href=>"#gems") } }
					li { h2 { a("Site Config", :href=>"#siteconfig") } }
					li { h2 { a("Site Object", :href=>"#siteobj") } }
					li { h2 { a("Site Modules", :href=>"#sitemodules") } }
					li { h2 { a("Databases", :href=>"#databases") } }
				}
				
				center {
					a(:name => "rbconfig")
					h2 "Ruby"
					require "rbconfig"
					table(table_info) {
						tr.header(header_row_info) {
							th "Constant"
							th "Data"
						}
						
						tr.data(data_row_info) {
							td.header { b "RUBY_VERSION" }
							td.data { i RbConfig.const_get(:RUBY_VERSION) } 
						}
						tr.data(data_row_info) {
							td.header { b "TOPDIR" }
							td.data { i RbConfig.const_get(:TOPDIR) } 
						}
						tr.data(data_row_info) {
							td.header { b "DESTDIR" }
							td.data { i RbConfig.const_get(:DESTDIR) }
						}
						unset_keys = []
						RbConfig.const_get(:CONFIG).each {|key, val|
							if (!val || val.to_s.length < 1)
								unset_keys.push(key)
							else
								tr.data(data_row_info) {
									td.header { b key }
									td.data { i val }
								}
							end
						}
						tr.data(data_row_info) {
							td.header { b "Unset Keys" }
							td.data { i unset_keys.join(", ") }
						}
					}
					a(:name => "rubygems")
					h2 "Ruby Gems"
					table(table_info) {
						tr.header(header_row_info) {
							th "Gem Name"
							th "Version"
							th "Description"
						}
						ObjectSpace.each_object(Gem::Specification) {|gem|
							if (gem.loaded?)
								tr.data(data_row_info) {
									td.header { b gem.name }
									td.data { i gem.version }
									td.data { i gem.description }
								}
							end
						}
					}
					
					a(:name => "siteconfig")
					h2 "Site Config"
					table(table_info) {
						tr.header(header_row_info) {
							th "Knob"
							th "Data"
						}
						$site.config.get_config_data {|key, val|
							if (!val.nil?)
								tr.data(data_row_info) {
									td.header { b key }
									if (val.kind_of?(String))
										td.data { i val }
									else
										td.data { val.html_get }
									end
								}
							end
						}
					}
					
					a(:name => "siteobj")
					h2 "Site Object"
					table(table_info) {
						tr.header(header_row_info) {
							th "Key"
							th "Data"
						}
						$site.get_config_data {|key, val|
							if (!val.nil?)
								tr.data(data_row_info) {
									td.header { b key }
									if (val.kind_of?(String))
										td.data { i val }
									else
										td.data { val.html_get }
									end
								}
							end
						}
					}
					
					a(:name => "sitemodules")
					h2 "Site Modules"
					table(table_info) {
						tr.header(header_row_info) {
							th "Module"
							th "Implicit?"
						}
						SiteModuleBase.loaded {|name, object|
							implicit = !$site.config.modules_include || !$site.config.modules_include.include?(name.to_sym)
							tr.data(data_row_info) {
								td.header { b name }
								td.data { implicit ? "y" : "n" }
							}
						}
					}

					a(:name => "databases")
					h2 "Databases"
					p "Coming Soon"
				}				
			}
		}		
	end
end
