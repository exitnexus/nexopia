lib_require :Core, 'memcache'

class PasteBin < PageHandler
	declare_handlers("pastebin") {
		page :GetRequest, :Full, :new_form;
		handle :PostRequest, :make_new, "new";
		handle :GetRequest, :show_paste, input(Integer), remain;
	}

	PostInfo = Struct.new('PostInfo', :language, :description, :tabs, :text);

	def new_form()
		t = Template.instance("debug", "pastebinnew");
		print t.display();
	end

	def make_new()
		post = PostInfo.new(
			params['language', /ruby/, 'ruby'],
			params['description', String, ''],
			params['tabs', Integer, 4],
			params['text', String, '']
		);

		name = rand(1000000);

		memcache = get_memcache();
		memcache.set("pastebin-#{name}", post, 7*86400);

		site_redirect("/pastebin/#{name}");
	end

	def show_paste(input, remain)
		memcache = get_memcache();
		obj = memcache.get("pastebin-#{input}");

		if(obj)
			if(remain[0] == 'original')
				reply.headers['Content-Type'] = "text/plain";
				puts obj.text;
			else
				if(obj.description == '')
					obj.description = 'No Description';
				end

				obj.text.gsub!("\t", " " * obj.tabs);

				t = Template.instance("debug", "pastebinshow");

				t.description = obj.description;
				t.input = input;
				t.lines = obj.text.split("\n");

				print t.display();
			end
		else
			puts "Paste not found. It probably expired.";
		end
	end

	def get_memcache()
		return $site.memcache
	end
end
