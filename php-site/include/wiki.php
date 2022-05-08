<? ?>

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

wikicats
-id
-parent
-name
-displaylevel - who can see             (inherit, public, mods, admins, wiki admins, private (ie page values))
-editlevel - who can edit               (inherit, public, mods, admins, wiki admins)
-activelevel - who can make revs active (inherit, public, mods, admins, wiki admins)

wikilog
-id
-userid
-action - edit/set active/set inactive/delete page, add/edit/delete cat, 
-beforeid
-afterid

wikipages
-id
-name - unique within a category, but can change. Reflects the name in the activerev. History stored in wikipagedata
-category - similar to name
-maxrev - is this needed?
-activerev - 0 represents inactive, need admin power to see

wikipagedata
-id
-pageid
-revision
-time
-userid
-name
-category
-parse bbcode (y/n)
-autonewlines (y/n)
-pagewidth
-content - actual content
-comment - comment about the content (in the sense, if this is parsed after the fact, tell how, etc)

</pre>


<?

define("WIKI_DISPLAY",1);
define("WIKI_EDIT",   2);
define("WIKI_ACTIVE", 3);


class wiki {

	public $db;

	function __construct( & $db){
	
		$this->db = & $db;
	}
	
	function getPerm($catid){ // ie none, display, edit, active
	
	}

//all category stuff needs wiki edit admin powers
	function addCat($name, $parent, $edit, $active, $view){
	
	}
	
	function getCat($id){
	
	}
	
	function getCatID($name){
	
	}
	
	function editCat($id, $name, $parent, $edit, $active, $view){
	
	}
	
	function deleteCat($id){ //fails if non-empty
	
	}
	
	function listCatCats($id){
	
	}
	
	function listCatPages($id){
	
	}
	
	function editPage($id, $name, $category, $parse, $autonl, $width, $content, $comment){ //returns the new rev num, used to create a page (set $id = 0)

	}

	function setActive($id, $rev){ //sets a certain revision of a page as active
	
	}

	function deactivate($id){ //equiv to deleting, but keeps history
	
	}

	function deletePage($id){ //removes page with all its history, needs activate permission
		
	}

	function getPage($catid, $name, $rev = 0){ //if rev = 0, get active
	
	}
	
	function getPageHist($catid, $name){ //returns all the revs of the page, but no content
	
	}
}