class AbuseReportFile
	def initialize(path)
		@path = path
	end

	def append(data)
		if defined? @content
			@content = @content + data
		else
			@content = data
		end
	end

	def get_content()
		@content
	end

	def get_path()
		@path
	end

	def set_content(data)
		@content = data
	end

	def set_path(path)
		@path = path
	end
end
