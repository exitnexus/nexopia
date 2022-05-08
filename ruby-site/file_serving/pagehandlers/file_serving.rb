lib_require :FileServing, "type"

module FileServing
	class Handlers < PageHandler
		declare_handlers("/") {

			area :Images
			access_level :Any
			handle :GetRequest, :file_unsafe, input(String), remain
			
			area :UserFiles
			handle :GetRequest, :file_safe, input(String), remain
		}
		
		def file(safe, type, remain)
			type = Type::type_registry[type]
			if (!type)
				raise PageError.new(404), "No filetype #{type}"
			end
			if (remain.length == 0)
				raise PageError.new(404), "No path specified."
			end
			
			if (type.secure_domain? && !safe)
				raise PageError.new(404), "Not found."
			end
			
			reply.headers["Content-Type"] = request.extension_content_type(remain.last)
			
			file = type.new_external_url(*remain)
			out = StringIO.new
			file.http_get_contents(request.headers, out, reply.headers)
			if (file.class.immutable?)
				reply.headers["Expires"] = (Time.now + 365*24*60*60).httpdate
			end
			print(out.string)
		end
		def file_unsafe(type, remain)
			file(false, type, remain)
		end
		def file_safe(type, remain)
			file(true, type, remain)
		end
	end
end