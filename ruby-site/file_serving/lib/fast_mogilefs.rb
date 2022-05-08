lib_require :FileServing, 'mogilefs'
lib_require :Core, 'http_fast'

module MogileFS
	# This makes the mogfs get_file_data function use the Net::HTTPFast to fetch instead of 
	# the slower and more broken built-in url fetching.
	class MogileFS
		def get_file_data(key)
			paths = get_paths(key)
			if (paths)
				paths.each do |path|
					next unless path
					case path
					when /^http:\/\// then
						begin
							url_obj = URI.parse path
							req = Net::HTTPFast::Get.new(url_obj.path)
							res = Net::HTTPFast.start(url_obj.host, url_obj.port) {|http|
								http.read_timeout = 0.5;
								http.request(req)
							}
							case res.code.to_i
							when 200
								return res.body
							end
						rescue Net::HTTPError, SystemCallError
							next
						end
					else
						next unless File.exist? path
						return File.read(path)
					end
				end
			end
			return nil
		end	
	end
end