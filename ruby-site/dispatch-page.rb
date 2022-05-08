require 'site_initialization';


initialize_site();


$after_request = {}
$page = $page.split('/', 2);
PageRequest.new(:GetMethod, $page[0].to_sym, "/#{$page[1]}", {}, Hash.new([]), {}, nil, PageReply.new($stdout)) {|req|
	$log.info("Running Page #{req.uri} with selector #{req.selector}");
	PageHandler.execute(req);
}
$after_request.each {|name, item|
	item.call();
}

