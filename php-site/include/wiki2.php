<?
/*
<pre>

would be used for
-rules
 -forums
 -mod
 -admin
 -etc
-faq
-internal messages
 -news
 -signup message
 -plus signup message
 -etc

If an entry is edited by someone without edit permissions, this wouldn't set the new version 
of that entry as active until someone who does have edit permissions marks it active.
Anyone with view permissions (except public) can edit, but unless they have edit permission, it becomes only a proposed change.
Some form of warning that a proposed change already exists must be done to reduce the chance of changes being missed.

db layout:

wikipages
-id
-name - unique within a category, but can change. Reflects the name in the activerev. History stored in wikipagedata
-parent - Reflects the parent in the activerev. History stored in wikipagedata
-activerev - 0 represents inactive, need admin power to see
-maxrev - is this needed?

wikipagedata
-id
-pageid
-revision
-time
-userid
-name
-parent
-displaylevel - who can see             (inherit, public, mods, admins, wiki admins, private (ie page values))
-editlevel - who can edit               (inherit, public, mods, admins, wiki admins)
-activelevel - who can make revs active (inherit, public, mods, admins, wiki admins)
-parse bbcode (y/n)
-allow html (y/n)
-autonewlines (y/n)
-pagewidth
-changedesc
-content - actual content
-comment - comment about the content (in the sense, if this is parsed after the fact, tell how, etc)


-view
-edit
-edithtml
-delete
-active
-create child
-move


</pre>

*/


define("WIKI_LEVEL_INHERIT",0);
define("WIKI_LEVEL_ANON",   1);
define("WIKI_LEVEL_PUBLIC", 2);
define("WIKI_LEVEL_MOD",    3);
define("WIKI_LEVEL_FORUM",  4); //not used, as there is no easy way to define it usable by ALL forum mods. Can only check one forum at a time.
define("WIKI_LEVEL_GLOBAL", 5);
define("WIKI_LEVEL_ABUSE",  6);
define("WIKI_LEVEL_ADMIN",  7);
define("WIKI_LEVEL_WIKI",   8);
define("WIKI_LEVEL_PRIVATE",9);

define("WIKI_HTML_LEVEL", WIKI_LEVEL_ADMIN); //min level needed to edit html pages, and be able to set the allowhtml flag

class wiki {

	public $db;

	public $perms;
	public $levels;

	public $pageids;
	public $pagecache;
	public $pageaddrs;
	
	function __construct( & $db){
	
		$this->perms = array(	
								'permdisplay' => "Display",
								'permedit'    => "Edit",
								'permhtml'    => "Edit HTML",
								'permactive'  => "Activate",
								'permdelete'  => "Delete",
								'permcreate'  => "Create Child",
							);

		$this->levels = array(	WIKI_LEVEL_INHERIT => "Inherit",
								WIKI_LEVEL_ANON    => "Anonymous",
								WIKI_LEVEL_PUBLIC  => "Public",
								WIKI_LEVEL_MOD     => "Mods",
								WIKI_LEVEL_GLOBAL  => "Global Mod",
								WIKI_LEVEL_ABUSE   => "Abuse Mod",
								WIKI_LEVEL_ADMIN   => "Admins",
								WIKI_LEVEL_WIKI    => "Wiki Admins",
								WIKI_LEVEL_PRIVATE => "Private",
							);

		$this->pageids = array();
		$this->pagecache = array();
		$this->pageaddrs = array();
	
		$this->db = & $db;
	}
	
	function getPerm($addr){ // ie none, display, edit, active

		$level = $this->getLevel();

		$perms = $this->perms;
		foreach($perms as $k => $v)
			$perms[$k] = ($level > WIKI_LEVEL_ANON || $k == 'permdisplay' ? null : false); //anon people can't have anything except edit


		$perms['level'] = $level;

		while(1){
			$needed = 0;

			$page = $this->getPage($addr);
	
			foreach($perms as $perm => $val){
				if($val === null){ //not already set
					if($page[$perm]) //doesn't inherit
						$perms[$perm] = ($page[$perm] <= $level);
					else
						$needed++;
				}
			}

			if(!$needed)
				break;

			$addr = $this->getParentAddr($addr);
		}

		$perms['edit'] = ($perms['permhtml'] || ($page['allowhtml'] == 'n' && $perms['permedit']));

		return $perms;
	}
	
	function getLevel(){
		global $userData, $mods, $forums;

		static $level = null;

		if($level !== null)
			return $level;

		$level = WIKI_LEVEL_ANON;

		if($userData['loggedIn']){
			$level = WIKI_LEVEL_PUBLIC;

			if($mods->isMod($userData['userid'], MOD_PICS))
				$level = WIKI_LEVEL_MOD;

			if($forums->getModPowers($userData['userid'], array(0)))
				$level = WIKI_LEVEL_GLOBAL;

			if($mods->isAdmin($userData['userid'])){
				$level = WIKI_LEVEL_ABUSE;
			
				if($mods->isAdmin($userData['userid'], 'visible'))
					$level = WIKI_LEVEL_ADMIN;
			
				if($mods->isAdmin($userData['userid'], 'wiki'))
					$level = WIKI_LEVEL_WIKI;
			}
		}
		
		return $level;
	}

	function editPage($curaddr, $newparent, $name, $display, $edit, $edithtml, $active, $delete, $create, $allowhtml, $parse, $autonl, $width, $changedesc, $content, $comment){ //returns the new rev num, used to create a page (set $curaddr = '')
		global $userData, $cache;

	//check data
		if($curaddr){
			$pageid = $this->getPageID($curaddr);
			if(!$pageid)
				return "Unknown page";
				
			$perms = $this->getPerm($curaddr);
			
			if(!$perms['edit'])
				return "You don't have permission to edit this page";
		}else{
			$pageid = 0;
			
			$perms = $this->getPerm($newparent);
			
			if(!$perms['permcreate'])
				return "You don't have permission to create this page";
		}
		
		if($newparent == '/'){
			$parentid = 0;
		}else{
			$parentid = $this->getPageID($newparent);
			
			if(!$parentid)
				return "Invalid parent";
		}

		$parentaddr = $this->getParentAddr($curaddr);
		
		if($this->cleanAddr($parentaddr) != $this->cleanAddr($newparent)){
			$newparentperms = $this->getPerm($newparent);
			
			if(!$newparentperms['permcreate'] || !$perms['permdelete']) //must be able to remove from here and create there.
				return "You don't have permission to move this page";
		}
		

		$newname = $this->cleanName($name);
		if($name != $newname)
			return "Invalid name: Only use letters and numbers";
		
		if(strlen($name) < 3)
			return "Name must be at least 3 chars long";
		if(strlen($name) > 32)
			return "Name must be a max of 32 chars long";


		$newaddr = '/' . trim($newparent, '/') . '/' . $name;

		$newpageid = $this->getPageID($newaddr);
		
		if($newpageid != $pageid){
			return "Page already exists";
		}
		
		
		if($perms['level'] >= WIKI_LEVEL_WIKI){
			if(!isset($this->levels[$display]))		return "Invalid display permission";
			if(!isset($this->levels[$edit]))		return "Invalid edit permission";
			if(!isset($this->levels[$edithtml]))	return "Invalid html permission";
			if(!isset($this->levels[$active]))		return "Invalid activate permission";
			if(!isset($this->levels[$delete]))		return "Invalid delete permission";
			if(!isset($this->levels[$create]))		return "Invalid create permission";
		}else{
			if($curaddr){ //ie keep old ones
				$page = $this->getPage($curaddr);
				
				$display  = $page['permdisplay'];
				$edit     = $page['permedit'];
				$edithtml = $page['permhtml'];
				$active   = $page['permactive'];
				$delete   = $page['permdelete'];
				$create   = $page['permcreate'];
			}else{ //inherit all perms
				$display  = WIKI_LEVEL_INHERIT;
				$edit     = WIKI_LEVEL_INHERIT;
				$edithtml = WIKI_LEVEL_INHERIT; 
				$active   = WIKI_LEVEL_INHERIT;
				$delete   = WIKI_LEVEL_INHERIT;
				$create   = WIKI_LEVEL_INHERIT;
			}

			if($perms['level'] < WIKI_HTML_LEVEL)
				$allowhtml = false;
		}

		if($width != 0 && ($width < 600 || $width > 1200))
			return "Invalid width";
		
//		if($changedesc == '')
//			return "Please describe the changes";


	//add the changes

		if($pageid){ //edit
			$res = $this->db->prepare_query("SELECT maxrev FROM wikipages WHERE id = #", $pageid);
			$rev = $res->fetchfield() + 1;
		}else{ //add
			$rev = 1;
			
			$this->db->prepare_query("INSERT INTO wikipages SET name = ?, parent = #, maxrev = #, activerev = #", $name, $parentid, $rev, $rev);
			
			$pageid = $this->db->insertid();
		}

		$this->db->prepare_query("INSERT INTO wikipagedata SET pageid = #, revision = #, time = #, userid = #, name = ?, parent = #, permdisplay = #, permedit = #, permhtml = #, permactive = #, permdelete = #, permcreate = #, allowhtml = ?, parsebbcode = ?, autonewlines = ?, pagewidth = #, changedesc = ?, content = ?, comment = ?",
				$pageid, $rev, time(), $userData['userid'], $name, $parentid, $display, $edit, $edithtml, $active, $delete, $create, ($allowhtml ? 'y' : 'n'), ($parse ? 'y' : 'n'), ($autonl ? 'y' : 'n'), $width, $changedesc, $content, $comment);

		$this->db->prepare_query("UPDATE wikipages SET name = ?, maxrev = #, activerev = # WHERE id = #", $name, $rev, $rev, $pageid);

		if($curaddr){
			$cache->remove("wikipage-$pageid");
			$cache->remove("wikichildren-$pageid");
		}
		
		$cache->remove("wikichildren-$parentid");

		if(isset($this->pagecache[$pageid]))
			unset($this->pagecache[$pageid]);

		return $rev;
	}

	function setActive($id, $rev){ //sets a certain revision of a page as active
		$this->db->prepare_query("UPDATE wikipages SET activerev = # WHERE id = #", $rev, $id);
		$cache->remove("wikipage-$pageid");
	}

	function setInactive($id){ //equiv to deleting, but keeps history
		$this->setActive($id, 0);
	}

	function deletePage($addr){ //removes page with all its history, needs activate permission, fails if has sub-pages
		global $cache;
		
		$pageid = $this->getPageID($addr);
		
		if(!$pageid)
			return "Page doesn't exist";
		
		$children = $this->getPageChildren($addr);
		
		if($children)
			return "This page has children, so can't be deleted";
		
		$parentid = $this->getPageID($this->getParentAddr($addr));
		
		$cache->remove("wikipageID-$addr");
		$cache->remove("wikipage-$pageid");
		
		$cache->remove("wikichildren-$pageid");
		$cache->remove("wikichildren-$parentid");
		
		
		$this->db->prepare_query("DELETE FROM wikipages WHERE id = #", $pageid);
		$this->db->prepare_query("DELETE FROM wikipagedata WHERE pageid = #", $pageid);
		
		return true;
	}

	function getPage($addr){ //can be done in the form array($addr, $rev) for a specific revision
		global $cache;

		if(is_array($addr)){
			$rev = $addr[1];
			$addr = $addr[0];
		}else{
			$rev = 0;
		}

		$pageid = $this->getPageID($addr);	


		if($pageid == 0){
			$page = array(
					'id' => 0,
					'name' => '',
					'parentaddr' => '',
					'permdisplay' => WIKI_LEVEL_PUBLIC,
					'permedit'    => WIKI_LEVEL_WIKI,
					'permhtml'    => WIKI_LEVEL_WIKI,
					'permactive'  => WIKI_LEVEL_WIKI,
					'permdelete'  => WIKI_LEVEL_WIKI,
					'permcreate'  => WIKI_LEVEL_WIKI,
					'allowhtml' => 'n',
					'parsebbcode' => 'y',
					'autonewlines' => 'y',
					'pagewidth' => 0,
					'content' => '',
					'output' => '',
					'comment' => '',
				);

			return $page;
		}


		if($rev){
			$res = $this->db->prepare_query("SELECT * FROM wikipagedata WHERE pageid = # && revision  = #", $pageid, $rev);

			$page = $res->fetchrow();
			
			$page = $this->parsePage($page);
		}else{
			if(isset($this->pagecache[$pageid])){
				$page = $this->pagecache[$pageid];
			}else{
				$page = $cache->get("wikipage-$pageid");
			
				if(!$page){
					$res = $this->db->prepare_query("SELECT wikipagedata.* FROM wikipages, wikipagedata WHERE wikipages.id = wikipagedata.pageid && wikipages.activerev = wikipagedata.revision && wikipages.id = #", $pageid);
					
					$page = $res->fetchrow();
					
					$page = $this->parsePage($page);
					
					$cache->put("wikipage-$pageid", $page, 86400*7);
				}
				$this->pagecache[$pageid] = $page;
			}
		}
		
		return $page;
	}
	
	function parsePage($page){
		$page['output'] = $page['content'];
		
		if($page['allowhtml'] == 'n')
			$page['output'] = removeHTML($page['output']);
		
		if($page['parsebbcode'] == 'y')
			$page['output'] = parseHTML($page['output']);
	
		if($page['autonewlines'] == 'y')
			$page['output'] = nl2br($page['output']);
		
		return $page;
	}
	
	function getPageHist($addr, $page, $pagelen = 25){ //returns all the revs of the page, but no content
		$pageid = $this->getPageID($addr);
		
		$res = $this->db->prepare_query("SELECT id, revision, time, userid, name, changedesc FROM wikipagedata WHERE pageid = # ORDER BY revision DESC LIMIT #, #", $pageid, $page*$pagelen, $pagelen);
		
		return $res->fetchrowset();
	}

	function getPageChildren($addr, $useperm = true){
		global $cache;

		$addr = $this->cleanAddr($addr);
	
		$pageid = $this->getPageID($addr);

		$pages = $cache->get("wikichildren-$pageid");
		
		if($pages === false){ //can be empty array
			$res = $this->db->prepare_query("SELECT id, name FROM wikipages WHERE parent = # ORDER BY name", $pageid);
			
			$pages = $res->fetchrowset();
			
			$cache->put("wikichildren-$pageid", $pages, 86400*7);
		}
		
		$ret = array();
		foreach($pages as $line){
			$this->pageids["$addr/$line[name]"] = $line['id'];
			
			if($useperm){
				$page = $this->getPerm("$addr/$line[name]");
				if($page['permdisplay'])
					$ret[] = $line['name'];
			}else{
				$ret[] = $line['name'];
			}
		}
		
		return $ret;	
	}

	function getParentAddr($addr){
		$addr = trim($addr, '/');
		return '/' . substr($addr, 0, strrpos($addr, '/'));
	}

	function cleanAddr($addr){
		return trim(preg_replace("/[^a-zA-Z0-9_\/]/", "", str_replace("//","/",$addr)), '/');
	}
	
	function cleanName($name){
		return preg_replace("/[^a-zA-Z0-9_]/", "", $name);
	}
	
	function getPageID($addr){
		global $cache;
	
		$addr = $this->cleanAddr($addr);

		if($addr == '')
			return 0;

		$addr = strtolower($addr);

		if(isset($this->pageids[$addr])){
			$id = $this->pageids[$addr];
		}else{
			$id = $cache->get("wikipageID-$addr");

			if(!$id){
				$names = explode('/', $addr);
				
				$name = array_pop($names);
				
				$parentid = $this->getPageID(implode('/', $names));

				$res = $this->db->prepare_query("SELECT id FROM wikipages WHERE parent = # && name = ?", $parentid, $name);
				$wikipage = $res->fetchrow();

				if(!$wikipage)
					return 0;
				
				$id = $wikipage['id'];
	
				$cache->put("wikipageID-$addr", $id, 86400*7);
			}
			
			$this->pageids[$addr] = $id;
		}
		
		return $id;
	}
	
	function getPageAddr($pageid){
		if(!$pageid)
			return '/';

		if(isset($this->pageaddrs[$pageid])){
			$addr = $this->pageaddrs[$pageid];
		}else{
			$res = $this->db->prepare_query("SELECT name, parent FROM wikipages WHERE id = #", $pageid);

			$line = $res->fetchrow();

			if(!$line)
				return false;
			
			if($line['parent']){
				$addr = $this->getPageAddr($line['parent']) . '/';
			}else{
				$addr = '/';
			}
			$addr .= $line['name'];

			$this->pageaddrs[$pageid] = $addr;
		}
		
		return $addr;
	}
}
