class CodeDoc < PageHandler
	declare_handlers("doc") {
		handle :GetRequest, :forward_doc, remain
	}

	def forward_doc(remain)
		realpath = "../ruby-doc/#{remain.join '/'}";
		if (File.exists?(realpath));
			if (File.directory?(realpath))
				site_redirect("/doc/#{remain.join '/'}index.html");
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
