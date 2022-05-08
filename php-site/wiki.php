<?

	$login=1;

	require_once("include/general.lib.php");

	

	$isAdmin = $mods->isAdmin($userData['userid']);
	$wikiAdmin = $mods->isAdmin($userData['userid'],'wiki');

	class wikipage extends pagehandler {
		function __construct() {

			$this->registerSubHandler('/wikilog',
				new urisubhandler($this, 'wikilog', REQUIRE_ANY
				)
			);

			$this->registerSubHandler('/wikilog',
				new urisubhandler($this, 'wikilog', REQUIRE_ANY,
					uriargs('addr', 'string')
				)
			);

			$this->registerSubHandler('/wiki',
				new urisubhandler($this, 'varhandler', REQUIRE_ANY,
					uriargs('addr', 'string')
				)
			);

			$this->registerSubHandler('/wiki',
				new urisubhandler($this, 'varhandler', REQUIRE_ANY
				)
			);

			
			$this->registerSubHandler('/wiki.php',
				new urisubhandler($this, 'varhandler', REQUIRE_ANY,
					uriargs('addr', 'string')
				)
			);

			$this->registerSubHandler('/wiki.php',
				new urisubhandler($this, 'varhandler', REQUIRE_ANY
				)
			);
		}
		
		function varhandler($addr = '/'){
			global $wiki, $msgs;

			if($addr == '' || $addr{0} != '/')
				$addr = "/$addr";

			$action = getREQval('action');

			if($addr == '/' && !$action)
				$action = 'children';

			if(strpos($addr, '?')){

				list($addr, $querystr) = explode('?', $addr);

				if(!$action){
					$action = $querystr;
	
					if($pos = strpos($action, '&'))
						$action = rtrim(substr($querystr, 0, $pos),'=');
					else
						$action = rtrim($action,'=');
				}
			}

			switch($action){
			
				case "hist":
					$this->viewHist(rtrim($addr, '/'));
		
				case "add":
					$this->editPage("$addr/");
				case "edit":
					$this->editPage($addr);
			
				case "Create":
				case "Update":
				
					$curaddr = getPOSTval('curaddr');
					$parentaddr = getPOSTval('parentaddr');
					$name = getPOSTval('name');

					$display  = getPOSTval('permdisplay', 'int');
					$edit     = getPOSTval('permedit', 'int');
					$edithtml = getPOSTval('permhtml', 'int');
					$active   = getPOSTval('permactive', 'int');
					$delete   = getPOSTval('permdelete', 'int');
					$create   = getPOSTval('permcreate', 'int');
					
					$allowhtml = getPOSTval('allowhtml', 'bool');
					$parse = getPOSTval('parsebbcode', 'bool');
					$autonl = getPOSTval('autonewlines', 'bool');
					$width = getPOSTval('width', 'int');
				
					$changedesc = getPOSTval('changedesc');
					$content = getPOSTval('content');
					$comment = getPOSTval('comment');
					
					$response = $wiki->editPage($curaddr, $parentaddr, $name, $display, $edit, $edithtml, $active, $delete, $create, $allowhtml, $parse, $autonl, $width, $changedesc, $content, $comment);
		
					if(is_int($response)){
						$this->view('/' . trim($parentaddr, '/') . '/' . $name);
					
					}else{ //returning an error code
						$page = array(
								'curaddr' => $curaddr,
								'parentaddr' => $parentaddr,
								'name' => $name,
								
								'permdisplay' => $display,
								'permedit' => $edit,
								'permhtml' => $edithtml,
								'permactive' => $active,
								'permdelete' => $delete,
								'permcreate' => $create,
								
								'allowhtml'    => ($allowhtml ? 'y' : 'n'),
								'parsebbcode'  => ($parse ? 'y' : 'n'),
								'autonewlines' =>($autonl ? 'y' : 'n'),
								'pagewidth' => $width,
								'changedesc' => $changedesc,
								'content' => $content,
								'comment' => $comment,
							);
						
						$msgs->addMsg($response);
						$this->editPage($page);
					}
			
				case "delete":
					if(!checkKey($addr, getREQval('k')))
						$this->view($addr);
				
					$parentaddr = $wiki->getParentAddr($addr);
					
					$ret = $wiki->deletePage($addr);
				
					if($ret === true){
						if($parentaddr == '' || $parentaddr == '/')
							$this->viewchildren($parentaddr);
						else
							$this->view($parentaddr);
					}else{
						$msgs->addMsg($ret);
						$this->view($addr);
					}

				case "diff":
				case "Show Differences":
					$rev1 = getREQval('rev1','int');
					$rev2 = getREQval('rev2','int');
				
					if($rev1 || $rev2){
						if($rev1 > $rev2 || $rev1 == 0)
							swap($rev1, $rev2);

						$this->viewdiff($addr, $rev1, $rev2);
					}
				
				case "children":
					$this->viewchildren($addr);
			
				case "view":
				default:
					$rev = getREQval('rev','int');
					$this->view($addr, $rev);
			}
		}

		function wikimenu($addr, $perms){

			if($addr == '')
				$addr = '/';

			$template = new template('wiki/wikimenu');
			$template->set('addr', $addr);
			$template->set('perms', $perms);
			$parents =explode('/', trim($addr, '/'));
			$template->set('parents', $parents);
			$template->set('key', makeKey($addr));
			$i = -1;
			foreach($parents as $part){
				$i++;
				if ($i > 0)
					$base[$i] = $base[$i-1] . "/$part";
				else
					$base[$i] = "/$part";
			}
			$template->set('base', $base);

			$template->display();

		}
		
		function checkPerm($addr, $level = false){
			global $wiki;
		
			$perms = $wiki->getPerm($addr);
			
			if($level && !$perms[$level])
				die("You don't have permission to see this");
		
			return $perms;
		}
		
		function view($addr, $rev = 0){
			global $wiki;
		
			$addr = rtrim($addr, '/');
		
			$perms = $this->checkPerm($addr, 'permdisplay');
		
			$entry = $wiki->getPage(array($addr, $rev));
		
			incHeader();
		
			$this->wikimenu($addr, $perms);
		
			echo $entry['output'];
			
			incFooter();
			exit;
		}
		
		function viewChildren($addr){
			global $wiki;
			
			$addr = rtrim($addr, '/');

			$perms = $this->checkPerm($addr, 'permdisplay');
			
			$children = $wiki->getPageChildren($addr);
			
			$template = new template('wiki/viewChildren');
			$template->set('addr', $addr);
			$template->set('children', $children);
			ob_start();
			$this->wikimenu($addr, $perms);
			$template->set('wikimenu', ob_get_clean());
			$template->display();
			exit;
		}
		
		function viewHist($addr){
			global $wiki;
			
			$pagelen = 25;
			
			$addr = rtrim($addr, '/');
			
			$perms = $this->checkPerm($addr, 'permdisplay');
			
			$page = getREQval('page','int');
			
			$hist = $wiki->getPageHist($addr, $page, $pagelen);
			
			$latestrev = 0;
			$numpages = 0;

			$uids = array();
			foreach($hist as $item){
				$uids[] = $item['userid'];
				
				if(!$latestrev){
					$latestrev = $item['revision'] + $page*$pagelen;
					$numpages = ceil($latestrev/$pagelen);
				}
			}
			
			$users = getUserName($uids);
		
			$template = new template('wiki/viewHist');
			ob_start();
			$this->wikimenu($addr, $perms);
			$template->set('wikimenu', ob_get_clean());
			$template->set('addr', $addr);
			$template->set('hist', $hist);
			$template->set('users', $users);
			
			$i = -1;
			foreach($hist as $item){
				$i++;
				$previousRevision[$i] = $item['revision']-1;
			}
			$template->set('previousRevision', $previousRevision);
			$template->set('pageList', pageList("/wiki$addr?hist", $page, $numpages, 'header'));
			$template->display();
			exit;
		}
		
		function editPage($addr){
			global $wiki;
			
			
			if(is_array($addr)){ //passed back from a failed attempt, assume permissions are fine, they were checked before, and will be after as well
				$page = $addr;
				$addr = $page['curaddr'];
				$parentaddr = $page['parentaddr'];
				$changedesc = $page['changedesc'];
				$new = ($addr == '');

				$perms = $this->checkPerm($addr);
			}else{
				$perms = $this->checkPerm($addr);

				$new = (substr($addr, -1) == '/');
				$addr = '/' . trim($addr, '/');
				$changedesc = '';
				
				if($new){ //new page
					if(!$perms['permcreate'])
						die("You can't create a new page");
				
					$parentaddr = $addr;
				
					$parentpage = $wiki->getPage($parentaddr);
				
					$page = array(	'name' => '',
									'permdisplay' => WIKI_LEVEL_INHERIT,
									'permedit'    => WIKI_LEVEL_INHERIT,
									'permhtml'    => WIKI_LEVEL_INHERIT,
									'permactive'  => WIKI_LEVEL_INHERIT,
									'permdelete'  => WIKI_LEVEL_INHERIT,
									'permcreate'  => WIKI_LEVEL_INHERIT,
									'allowhtml'   => 'n',
									'parsebbcode' => 'y',
									'autonewlines'=> 'y',
									'pagewidth' => 0,
									'content' => '',
									'comment' => '',
								);
			
				}else{
					if(!$perms['edit'])
						die("You can't create a new page");

					$parentaddr = $wiki->getParentAddr($addr);
		
					$page = $wiki->getPage($addr);
				}
			}
			$template = new template('wiki/editPage');
			ob_start();
			$this->wikimenu($addr, $perms);
			$template->set('wikimenu', ob_get_clean());
			$template->set('new', $new);
			$template->set('page', $page);
			$template->set('parentaddr', $parentaddr);
			$template->set('addr', $addr);
			
			if($perms['level'] >= WIKI_LEVEL_WIKI){
				$template->set('displayPerms', true);
				
				$template->set('selectPermDisplay', make_select_list_key($wiki->levels, $page['permdisplay']));
				$template->set('selectPermEdit', make_select_list_key($wiki->levels, $page['permedit']));
				$template->set('selectPermHTML', make_select_list_key($wiki->levels, $page['permhtml']));
				$template->set('selectPermActivate', make_select_list_key($wiki->levels, $page['permactive']));
				$template->set('selectPermDelete', make_select_list_key($wiki->levels, $page['permdelete']));
				$template->set('selectPermCreate', make_select_list_key($wiki->levels, $page['permcreate']));
			}
			
			$template->set('HTMLAllowed', ($perms['level'] >= WIKI_HTML_LEVEL));
			$template->set('checkAllowHTML', makeCheckBox('allowhtml', "Allow HTML", $page['allowhtml'] == 'y'));
			$template->set('checkParseBBCode', makeCheckBox('parsebbcode', "Parse as forumcode", $page['parsebbcode'] == 'y'));
			$template->set('checkAutoNewLines', makeCheckBox('autonewlines', "Automatically add line breaks", $page['autonewlines'] == 'y'));
					
		//TODO: page width
			$template->set('changedesc', $changedesc);
			
			$template->display();
			exit;
			
		}
		
		function viewdiff($addr, $rev1, $rev2){
			global $wiki;

			$perms = $this->checkPerm($addr, 'permedit');
		
			$page1 = $wiki->getPage(array($addr, $rev1));
			$page2 = $wiki->getPage(array($addr, $rev2));
			
			$template = new template('wiki/viewdiff');
			ob_start();
			$this->wikimenu($addr, $perms);
			$template->set('wikimenu', ob_get_clean());
			$diff = diff($page1['content'], $page2['content']);
			$template->set('diff', $diff);
			$template->set('addr', $addr);
			$template->set('page1', $page1);
			$template->set('page2', $page2);
			
			$r1 = 0;
			$r2 = 0;

			$i=-1;
			foreach($diff as $row){
				$i++;
				list($line1[$i], $line2[$i]) = $row;
				
			
				$class1[$i] = 'normal';
				$class2[$i] = 'normal';
			
				$linediff[$i] = diff($line1[$i], $line2[$i], array(' ', "\t"));
				
				if($line1[$i] === null){
					$class1[$i] = 'missing';
					$class2[$i] = 'new';
				}elseif($line2[$i] === null){
					$class1[$i] = 'new';
					$class2[$i] = 'missing';
				}elseif($line1[$i] !== $line2[$i]){
					$class1[$i] = 'changed';
					$class2[$i] = 'changed';
				}
				
			//str1
				if ($line1[$i] !== null)
					$row1[$i] = ++$r1;
				else
					$row1[$i] = '';
				
				if ($line2[$i] !== null)
					$row2[$i] = ++$r2;
				else
					$row2[$i] = '';
			
				if($line1[$i]){
					$j = -1;
					foreach($linediff[$i] as $word){
						$j++;
						$changedWord1[$i][$j] = false;
						if($class1[$i] == 'changed' && (!$word[1] || $word[1] != $word[0])){
							$changedWord1[$i][$j] = true;
						}
						$formattedWord1[$i][$j] = str_replace("\t", "&nbsp; ", htmlentities($word[0]));
					}
				}
				if($line2[$i]){
					$j=-1;
					foreach($linediff[$i] as $word){
						$j++;
						$changedWord2[$i][$j] = false;
						if($class2[$i] == 'changed' && ($word[0] === null || $word[0] !== $word[1])) {
							$changedWord2[$i][$j] = true;
						}
						$formattedWord2[$i][$j] = str_replace("\t", "&nbsp; ", htmlentities($word[1]));
					}
				}
			}
			$template->set('class1', $class1);
			$template->set('class2', $class2);
			$template->set('row1', $row1);
			$template->set('row2', $row2);
			$template->set('linediff', $linediff);
			$template->set('line1', $line1);
			$template->set('line2', $line2);
			$template->set('changedWord1', $changedWord1);
			$template->set('changedWord2', $changedWord2);
			$template->set('formattedWord1', $formattedWord1);
			$template->set('formattedWord2', $formattedWord2);
			$template->display();
			exit;
		}
		
		
		function wikilog(){
			global $wiki, $config;

			$level = $wiki->getLevel();
			
			if($level < WIKI_LEVEL_ADMIN)
				die("You don't have permission to see this.");

			$page = getREQval('page', 'int');
			
			$res = $wiki->db->prepare_query("SELECT SQL_CALC_FOUND_ROWS pageid, revision, time, userid, name, changedesc, parent FROM wikipagedata ORDER BY id DESC LIMIT #, #", $page*$config['linesPerPage'], $config['linesPerPage']);
			
			$changes = array();
			$uids = array();
			while($line = $res->fetchrow()){
				$changes[] = $line;
				$uids[$line['userid']] = $line['userid'];
			}

			$numrows = $res->totalrows();
			$numpages =  ceil($numrows / $config['linesPerPage']);

			$users = getUserName($uids);

			$template = new template('wiki/wikilog');

			$i = -1;
			foreach($changes as $item){
				$i++;
				$addr[$i] = $wiki->getPageAddr($item['pageid']);
			}
			$template->set('addr', $addr);
			$template->set('pageList', pageList("/wikilog/", $page, $numpages, 'header'));
			$template->set('users', $users);
			$template->set('changes', $changes);
			$template->display();
			exit;
		}		
		
	}



	$wikipage = new wikipage();
	return $wikipage->runPage();
