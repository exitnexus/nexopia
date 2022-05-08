lib_require :Wiki, "wikipages"
lib_require :Wiki, "wikipagedata"

# A Wiki object represents all of the revisions of a particular page.
# Use "get_revision()" to load the latest revision.
class Wiki

	attr :parent, true;
	attr :header, true;
	
	def Wiki.from_address(addr)
		addr = Wiki.clean_addr(addr);
		name = Wiki.parse_page_name(addr);
		parent_addr = Wiki.parse_parent_addr(addr);
		
		parent = nil;
		if (parent_addr.length > 0)
			parent = Wiki.from_address(parent_addr);
		end
		
		return Wiki.new(name, parent);
	end
	
	def initialize(name, parent = nil)
		@header = Wiki.load_page_header(name, parent)
		@parent = parent;
	end

	def active_revision()
		return @header.activerev	
	end
	
	#gets the page data, nil if the page doesn't exist
	def get_revision(rev = :latest)
		if(!@header)
			return nil;
		end

		if (rev == :latest)
			rev = @header.activerev;
		end
		return WikiPageData.get_page_rev(@header.id, rev);
	end
	
	# Commit a new revision of a wiki page to the database with the specified
	# text and description.  The commit will belong to the specified user.
	def commit_revision(uid, desc, text)

		current_rev = WikiPageData.get_page_rev(@header.id, @header.maxrev);
		
		new_rev = current_rev.dup();
		new_rev.id = nil;

		new_rev.clear_modified!;
		new_rev.pageid = @header.id;
		new_rev.revision = @header.maxrev + 1;
		new_rev.time = Time.now.to_i;
		new_rev.userid = uid;
 		new_rev.changedesc = desc;
		new_rev.content = text;
		new_rev.update_method = :insert;
		new_rev.name = @header.name;
		
		new_rev.store();

		@header.maxrev += 1;
		@header.activerev = @header.maxrev;
		@header.store();

	end

	
	def get_hist()
		if(!@header)
			return nil;
		end

		return WikiPageData.get_page_hist(@header.id)
	end

	def edit(data)


	end

	def delete()
		children = get_children();

		if(children.length > 0)
			return false;
		end

		header = get_page_header();
		hist = get_hist();

		hist.delete();
		header.delete();

		return true;
	end

	def get_perm()
	end

	def get_level()
	end

	def children()
		if (@cached_children)
			return @cached_children;
		else
			@cached_children = Array.new();
		end
		
		WikiPage.get_children(@header.id).each{|child|
			@cached_children << Wiki.new(child, self);
		}
		return @cached_children;
	end

	def Wiki.load_page_header(name, parent = nil)
		if(name == '')
			return WikiPage.new(); #blank wiki page, where id = 0
		end

		if (parent)
			return WikiPage.find(:first, :conditions => ["name = ? && parent = ?", name, parent.header.id]);
		else
			return WikiPage.find(:first, :conditions => ["name = ? && parent = 0", name]);
		end
	end

	def uri_info
		if (!@parent)
			return [@name, "/wiki/#{@name}"]
		else
			parent_url = @parent.uri_info[1]
			return [@name, "#{parent_url}/#{@name}"]
		end
	end

	def Wiki.parse_page_name(addr)
		return addr[addr.rindex('/')+1..-1]; #start from the last /, and take the rest of the string
	end

	def Wiki.parse_parent_addr(addr)
		return addr[0, addr.rindex('/')];
	end

	def Wiki.clean_addr(addr)
		return '/' + addr.squeeze('/').gsub(/[^A-Za-z0-9_\/]/, '').gsub(/^\//, '').gsub(/\/$/, '').downcase;
	end

=begin
	todo: setactive, setinactive

	function getPerm($addr)
	function getLevel()
	function editPage($curaddr, $newparent, $name, $display, $edit, $edithtml, $active, $delete, $create, $allowhtml, $parse, $autonl, $width, $changedesc, $content, $comment) //returns the new rev num, used to create a page (set $curaddr = '')
	function setActive($id, $rev) //sets a certain revision of a page as active
	function setInactive($id) //equiv to deleting, but keeps history
	function deletePage($addr) //removes page with all its history, needs activate permission, fails if has sub-pages
	function getPage($addr) //can be done in the form array($addr, $rev) for a specific revision
	function parsePage($page)
	function getPageHist($addr, $page, $pagelen = 25) //returns all the revs of the page, but no content
	function getPageChildren($addr, $useperm = true)
	function getParentAddr($addr)
	function cleanAddr($addr)
	function cleanName($name)
	function getPageID($addr)
	function getPageAddr($pageid)
=end

end
