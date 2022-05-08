module Legacy
	class WebRequestHandler < PageHandler
		declare_handlers("") {
			area :Public
			rewrite(:GetRequest, "galleries", remain) {|remain| url/"gallery.php"/remain }
			
			rewrite(:GetRequest, 'manage', 'pictures', remain) {|remain| url/"managepicture.php"/remain }
			rewrite(:GetRequest, 'wiki', remain) {|remain| url/"wiki.php"/remain }
			rewrite(:GetRequest, 'help', remain) {|remain| url/"help.php"/remain }
			rewrite(:GetRequest, 'skincontest', remain) {|remain| url/"skincontest.php"/remain }
			rewrite(:GetRequest, 'contest', remain) {|remain| url/"contests.php"/remain }
			rewrite(:GetRequest, 'googlesearch', remain) {|remain| url/"googlesearch.php"/remain }
			rewrite(:GetRequest, 'capitalex', remain) {|remain| url/"capitalex.php"/remain }
			rewrite(:GetRequest, 'careers', remain) {|remain| url/"careers.php"/remain }
			rewrite(:GetRequest, 'about', remain) {|remain| url/"aboutus.php"/remain }
			rewrite(:GetRequest, 'advertis(e|ing)', remain) {|remain| url/"advertise.php"/remain }
			rewrite(:GetRequest, 'plus', remain) {|remain| url/"plus.php"/remain }
		
			#rewrite(:GetRequest, 'video', remain) {|remain| url/"ruby_passthru.php"/remain }
			#rewrite(:GetRequest, 'test_stuff', remain) {|remain| url/"ruby_passthru.php"/remain }
			#rewrite(:GetRequest, 'pastebin', remain) {|remain| url/"ruby_passthru.php"/remain }
			#rewrite(:GetRequest, 'music', remain) {|remain| url/"ruby_passthru.php"/remain }
			#rewrite(:GetRequest, 'content', remain) {|remain| url/"ruby_passthru.php"/remain }
			#rewrite(:GetRequest, 'admin', remain) {|remain| url/"ruby_passthru.php"/remain }
			#rewrite(:GetRequest, 'accountcreate', remain) {|remain| url/"ruby_passthru.php"/remain }
			#rewrite(:GetRequest, 'my', remain) {|remain| url/"ruby_passthru.php"/remain }
			rewrite(:GetRequest, 'terms', remain) {|remain| url/"terms.php"/remain }
			rewrite(:GetRequest, 'googleprofile', remain) {|remain| url/"googleprofile.php"/remain }

		}
	end
end