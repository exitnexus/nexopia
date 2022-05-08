lib_require :Core, "session"
lib_require :Wiki, "wiki"
lib_require :Bbcode, "bbcodemodule"

class WikiPages < PageHandler
=begin	
	declare_handlers("wiki") {
		area :Public
		access_level :Any
		page :GetRequest, :Full, :wiki_page, remain
		
		access_level :Admin, CoreModule, :wiki
		page :GetRequest, :Full, :wiki_history_page, "revision_history", remain

		page :GetRequest, :Full, :create_wiki_page, "create", remain
			form "SubmitEdit", :validate_and_submit_edit

		page :GetRequest, :Full, :add_child_wiki_page, "add_child", remain
			form "SubmitEdit", :validate_and_submit_new_wiki

		page :GetRequest, :Full, :edit_wiki_page, "edit", remain
			form "SubmitEdit", :validate_and_submit_new_wiki

	}
=end	
	def admin_wikis
		t = Template.instance("wiki", "admin")
		t.wikis = WikiPage.find(:conditions => ["parent = 0"]).collect{|wikipage|
			Wiki.new(wikipage);
		}
		puts t.display();
	end
	
	def wiki_page(remain)
		if (remain.length <= 0)
			return admin_wikis()
		end
		
		addr = "/" << remain.join("/");
		page = Wiki.from_address(addr);

		t = Template.instance('wiki', 'wiki')
		
		rev = :latest
		if (params['rev', Integer])
			rev = params['rev', Integer]
		end
		page_data = page.get_revision(rev);
		t.children = page.children();
		
		e_str = BBCode::ErrorStream.new();
		bb_scan = BBCode::Scanner.new();
		bb_scan.InitFromStr(page_data.content, e_str);
		bb_parser = BBCode::Parser.new(bb_scan);
		
		t.content = bb_parser.Parse();
		t.parent = page.parent;
		t.title = page.name;
		t.addr = addr;
		puts t.display();
	end

	def wiki_history_page(remain)
		if (remain.length <= 0)
			return admin_wikis()
		end
		
		addr = "/" << remain.join("/");

		t = Template.instance('wiki', 'history')

		page = Wiki.from_address(addr);
		page_data = page.get_revision(:latest);
		t.children = page.children();
		
		t.parent = page.parent;
		t.title = page.name;
		t.addr = addr;
		t.page = page;
		
		puts t.display();
	end
	
	def edit_wiki_page(remain)
		if (remain.length <= 0)
			return admin_wikis()
		end
		
		addr = "/" << remain.join("/");

		t = Template.instance('wiki', 'edit')

		page = Wiki.from_address(addr);
		page_data = page.get_revision(:latest);
		t.children = page.children();
		
		t.parent = page.parent;
		t.addr = addr;
		
		f = create_wiki_edit_form(page_data);
		f.load_to_form(t);
		puts t.display();		
	end

	def validate_and_submit_edit(remain)
		if (remain.length <= 0)
			return
		end

		addr = "/" << remain.join("/");
		page = Wiki.from_address(addr);

		rev = :latest
		if (params['rev', Integer])
			rev = params['rev', Integer]
		end
		page_data = page.get_revision(rev);

		f = create_wiki_edit_form(page_data);
		f.unload_from_form(request.params);
		
		page.commit_revision(session.user.userid, "", page_data.content);
		
		$log.info "Success!";
		$log.info "#{page_data.content}";
	end
	
	def create_wiki_edit_form(wikirev)
		f = Form.new()
		
		f.bind wikirev.get_ref(:name), "title"
		f.bind wikirev.get_ref(:content), "content"
		return f;
	end
	
	def create_wiki_page(remain)
		if (remain.length <= 0)
			return admin_wikis()
		end

		if (remain.length > 1)
			puts "If you want to create a child, you must edit the parent."
		end

		addr = remain[0];
		
		t = Template.instance('wiki', 'edit')

		t.content = "Insert text.";
		t.title = "Create Title";
		
		t.addr = addr;
		puts t.display();		
	end

	def add_child_wiki_page(remain)
		if (remain.length <= 0)
			return admin_wikis()
		end

		if (remain.length == 1)
			puts "If you want to create a root entry, go to the admin page."
		end

		addr = "/" << remain.join("/");
		
		t = Template.instance('wiki', 'edit')

		t.content = "Insert text.";
		t.title = "Create title";
		t.parent = addr;
		t.addr = addr;
		puts t.display();		
	end

	def validate_and_submit_new_wiki(remain)
		if (remain.length <= 0)
			return admin_wikis()
		end

		parent = nil;
		parent_addr = "/" << remain.join("/")
		
		header = WikiPage.new();
		header.parent = 0;
		
		if (remain.length > 0)
			parent = Wiki.from_address(addr);
			header.parent = parent.id;
		end
		
		page_data = WikiPageData.new();

		f = Form.new();
		f.bind page_data.get_ref(:name), "wikititle"
		f.bind page_data.get_ref(:content), "wikitxt"
		f.unload_from_form(request.params);

		addr = parent_address << page_data.name;
		
		wiki = Wiki.new(header, parent);
		wiki.commit_revision(session.user.userid, "", page_data.content);
		

	end

end
