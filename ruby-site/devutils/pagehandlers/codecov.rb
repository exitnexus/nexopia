class CodeCov < PageHandler
	declare_handlers("cov") {
		handle :GetRequest, :forward_cov, remain
	}

	def forward_cov(remain)
		realpath = "../ruby-cov/#{remain.join '/'}";
		if (File.exists?(realpath));
			if (File.directory?(realpath))
				site_redirect("/cov/#{remain.join '/'}index.html");
			end
			if (File.file?(realpath))
				if (realpath =~ /\.css$/)
					reply.headers["Content-Type"] = PageRequest::MimeType::CSS;
				end

				File.foreach(realpath) { |line|
					puts(line);
				}
				return;
			end
		end
		raise PageError.new(404), "Not Found";
	end
end
