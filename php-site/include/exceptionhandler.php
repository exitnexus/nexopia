<?

function globalExceptionHandler(Exception $e) {
	global $userData, $mods;
	if ($mods->isAdmin($userData['userid'])) {
		$template = new template('exceptionhandler/exceptionhandler_admin');
	} else {
		$template = new template('exceptionhandler/exceptionhandler');
	}
	$template->set('message', $e->getMessage());
	$template->set('code', $e->getCode());
	$template->set('file', $e->getFile());
	$template->set('line', $e->getLine());
	$template->set('trace', $e->getTrace());
	$template->set('traceAsString', $e->getTraceAsString());
	$template->display();
}

set_exception_handler("globalExceptionHandler");