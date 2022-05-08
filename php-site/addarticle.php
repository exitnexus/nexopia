<?
	$login = 1;

	require_once('include/general.lib.php');

	$cats = new category($articlesdb, "cats");
	$branch = $cats->makebranch();

	switch ($action) {
		case 'Preview':
		case 'Post':
			$category = getPOSTval('category');
			$title = getPOSTval('title');
			$msg = getPOSTval('msg');
			$parse_bbcode = getPOSTval('parse_bbcode', 'bool');

			if($category && $title && $msg)
				addArticle($category, $title, $msg, $action, $parse_bbcode);
			break;


	}

	$template = new template('articles/addarticle/index');
/*	if(!isset($parse_bbcode))
		$template->set("checkbox_parsebbcode", makeCheckBox('parse_bbcode', 'Parse BBcode', $userData['parse_bbcode']));
	else
		$template->set("checkbox_parsebbcode", makeCheckBox('parse_bbcode', 'Parse BBcode', $parse_bbcode));*/
	$template->set("checkbox_parsebbcode", '<input type="hidden" name="parse_bbcode" value="y"/>');


	if(!isset($msg))
		$msg = '';
	ob_start();
	editBox($msg);
	$template->set('editBox', ob_get_contents());
	ob_end_clean();

	$template->setMultiple(array(
		'catSel'	=> makeCatSelect($branch)
	));
	$template->display();


	function addArticle($category, $title, $msg, $action, $parse_bbcode) {
		global $branch, $cats, $msgs, $userData, $articlesdb, $mods;

		if(!isset($title) || strlen($title) < 1) {
			$action = 'Preview';
			$msgs->addMsg('Needs a Title');
		}
		if(strlen($title) > 255) {
			$action = 'Preview';
			$msgs->addMsg('Title is too short');
		}
		if(!isset($msg) || strlen($msg) < 1) {
			$action = 'Preview';
			$msgs->addMsg('No text');
		}
		if(strlen($msg) > 65535) {
			$action = 'Preview';
			$msgs->addMsg('Text is too long');
		}
		if(!isset($category) || !$cats->isValidCat($category)) {
			$action = 'Preview';
			$msgs->addMsg('Bad category');
		}

		$ntitle = removeHTML($title);
		$ntitle = trim($ntitle);


		$narticle = html_sanitizer::sanitize($msg);


		if($parse_bbcode)
		{
			$narticle2 = parseHTML($narticle);
			$narticle3 = smilies($narticle2);
			$narticle3 = nl2br($narticle3);
		}
		else
			$narticle3 = $narticle;

		if ($action == 'Preview' || $ntitle == '') {
			$template = new template('articles/addarticle/preview');

			ob_start();
			editBox($narticle);
			$template->set('editBox', ob_get_contents());
			ob_end_clean();

			$template->setMultiple(array(
				'ntitle'	=> $ntitle,
				'narticle3'	=> $narticle3,
				'selCat'	=> makeCatSelect($branch, $category),
				'title'		=> $title
			));
/*			if(!isset($parse_bbcode))
				$template->set("checkbox_parsebbcode", makeCheckBox('parse_bbcode', 'Parse BBcode', $userData['parse_bbcode']));
			else
				$template->set("checkbox_parsebbcode", makeCheckBox('parse_bbcode', 'Parse BBcode', $parse_bbcode));*/
			$template->set("checkbox_parsebbcode", '<input type="hidden" name="parse_bbcode" value="y"/>');


			$template->display();
			exit;
		}

		$res = $articlesdb->prepare_query(
			"SELECT id FROM articles WHERE authorid = ? && title = ? && text = ?",
			$userData['userid'], $ntitle, $narticle
		);

		$parse_bbcode = $parse_bbcode ? 'y': 'n';


		if (!$res->fetchrow()) { //dupe detection
			$articlesdb->prepare_query(
				"INSERT INTO articles SET authorid = ?, submittime = ?, category = ?, title = ?, text = ?, parse_bbcode = ?", //, ntext = ?",
				$userData['userid'], time(), $category, $ntitle, $narticle , $parse_bbcode//, $narticle3
			);

			$articleID = $articlesdb->insertid();
			$mods->newItem(MOD_ARTICLE, $articleID);
		}

		header ("Location: /articlelist.php");
		exit;
	}

