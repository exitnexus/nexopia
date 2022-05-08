require "test/unit";
require "test/unit/ui/console/testrunner"
require "stringio";
lib_require :Devutils, 'tests', 'test_status'

class TestInterface < PageHandler
	declare_handlers("tests") {
		handle :GetRequest, :run_suite, input(String);
		handle :GetRequest, :run_test, input(String), input(String);
		handle :GetRequest, :xml_tests, 'xml';
		page :GetRequest, :Full, :test_status, 'status'

		handle :GetRequest, :tree_view
	}

	def page_initialize
		@tests = Tests.instance
	end

	def tree_view
		puts "<html><head><title>Test Interface</title>"
		puts "<script type=\"text/javascript\" src=\"#{$site.static_files_url}/Yui/build/yahoo/yahoo.js\"> </script>"
		puts "<script type=\"text/javascript\" src=\"#{$site.static_files_url}/Yui/build/dom/dom.js\"> </script>"
		puts "<script type=\"text/javascript\" src=\"#{$site.static_files_url}/Yui/build/event/event.js\"> </script>"
		puts "<script type=\"text/javascript\" src=\"#{$site.static_files_url}/Yui/build/animation/animation.js\"> </script>"
		puts "<script type=\"text/javascript\" src=\"#{$site.static_files_url}/Yui/build/connection/connection.js\"> </script>"
		puts "<script type=\"text/javascript\" src=\"#{$site.static_files_url}/Yui/build/treeview/treeview.js\"> </script>"
		puts "<script type=\"text/javascript\" src=\"#{$site.script_url}/devutils.js\"> </script>"
		puts "<link rel=\"stylesheet\" href=\"#{$site.static_files_url}/Yui/build/grids/grids.css\" type=\"text/css\">"
		puts "<link rel=\"stylesheet\" href=\"#{$site.style_url}/nexoskel/newblue.css\">"

		puts %Q|
			<body>
				<div id="doc3" class="yui-t7" style="background-color:CornflowerBlue;height:100%;border-style:solid;border-width:1px">
						<div id="bd">
						<div class="yui-gd">
			    			<div class="yui-u first" style="background-color:AliceBlue;overflow:auto;height:100%">
								<strong>Tests</strong>
								<div id="navTree"></div>
				    		</div>
			    			<div class="yui-u" style="background-color:#FFFFFA;overflow:auto;height:100%">
								<div>
									<input type="button" onclick="document.getElementById('test-results').innerHTML = ''" style="float:right" value="Clear"/>
									<strong>Results</strong>
									</div>
								<div id="test-results">#{@tests.html_errors}</div>
					    	</div>
						</div>
					</div>
				</div>|
	 	puts generate_node_insertion_javascript()
	end

	def run_test(module_name, class_name)
		@tests = Tests.instance()
		sio = StringIO.new;
		@tests.run(class_name, sio)
		puts parse_test(module_name + "#" + class_name, sio);
	end

	def test_status
		t = Template.instance('devutils', 'test_status')
		broken_tests = TestStatus.find(:conditions => 'revision_fixed = 0')
		t.broken_tests = broken_tests.sort_by {|test_status|
			[-test_status.revision_broken, test_status.testclass, test_status.test, test_status.testerror]
		}
		puts t.display
	end

	private
	def parse_test(test_name, sio)
		string = CGI::escapeHTML(sio.string)
		error = false;
		string.gsub!(/(\d) (errors|failures)/) {
			if ($1 != "0")
				"<strong>#{$1} #{$2}</strong>"
			else
				"#{$1} #{$2}"
			end
		}
		string.gsub!(/^[E\.F]*[EF][E\.F]*$/) {
			error = true;
			"<strong style='color:red'>FAILED</strong>"
		}
		string.gsub!(/^\.+$/,'');
		string.gsub!(/^Started$/,'');
		string.gsub!(/^Loaded suite.*$/,'');
		string.gsub!(/Error:\n/, 'Error: ')
		string.gsub!(/^\n+/,'');
		string.gsub!(/\n+/, '<br>');
		string.gsub!(/(\.?\.\/.*?:)(\d+)/) {"<u>#{$1}<strong>#{$2}</strong></u>"}
		string.gsub!(/`([^\s]+)'/) {"<strong>#{$1}</strong>"}
		string.gsub!(/(\d+\.\d+) seconds/) {"<em>#{$1.to_f*1000}</em> ms"}
		if (error)
			string = "<div style='background-color:#FFDDDD'><hr/><div style=\"padding-left:5px\">" + "<strong>#{test_name}</strong>: " +
			   string + "</div><hr/></div>";
		else
			string = "<div style='background-color:#DDFFDD'><hr/><div style=\"padding-left:5px\">" + "<strong>#{test_name}</strong>: " +
			    "<strong style='color:green'>PASSED</strong><br>"+string+"</div><hr/></div>";
		end
		return string;
	end

	def generate_node_insertion_javascript
		tree = @tests.tests
		keys = tree.keys.sort
		sio = StringIO.new;
		sio.puts "<script>"
		sio.puts 'function bleep(node) {alert(node.label + \'-\' + node.parent.label);}'
		sio.puts 'var tree = new YAHOO.widget.TreeView("navTree");'
		sio.puts 'var root = tree.getRoot();'
		sio.puts "tree.subscribe(\"labelClick\", TestHarness.runTest)"
		keys.each { |key|
			sio.puts "var currentMod = new YAHOO.widget.TextNode(\"#{key}\", root, false);"
			tree[key].each {|test|
				sio.puts "var leafNode = new YAHOO.widget.TextNode(\"#{test}\", currentMod, false);"
				sio.puts "leafNode.labelStyle = 'not_run';"
			}
		}
		sio.puts 'tree.draw();'
		sio.puts "</script>"
		return sio.string;
	end
end
