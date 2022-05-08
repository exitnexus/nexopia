lib_require :Core, "pagehandler", "pagerequest"
require 'rap/pagehandlers/RAPHandler.rb'
ENV['ADMINHOURLY_ENABLED'] = '1'
begin
	PageRequest.new(:GetMethod, :Public, "/adminhourly.php:Body", {}, Hash.new([]), {}, nil, PageReply.new(StringIO.new)) {|req|
		$log.info("Running php daily script", :info, :cron);
		PageHandler.execute(req);
		req.reply.out.rewind
		req.reply.out.each {|line|
			$log.info("adminhourly.php: #{line}", :info, :cron)
		}
	}
ensure
	ENV.delete('ADMINHOURLY_ENABLED')
end
