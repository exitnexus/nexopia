class ArchiveType
	MESSAGE=1
	COMMENT=11
	PROFILE=21
	GALLERYCOMMENT=31
	BLOGPOST=41
	BLOGCOMMENT=42

	@@type_descriptions = {
		ArchiveType::MESSAGE => "Message",
		ArchiveType::COMMENT => "Comment",
		ArchiveType::PROFILE => "Profile",
		ArchiveType::GALLERYCOMMENT => "Gallery Comment",
		ArchiveType::BLOGPOST => "Blog Post",
		ArchiveType::BLOGCOMMENT => "Blog Comment"
	}

	def initialize(type_id)
		if @@type_descriptions.has_key?(type_id)
			@type_id = type_id
		else
			@type_id = nil
		end
	end

	def ArchiveType.description(type_id)
		if @@type_descriptions.has_key?(type_id)
			@@type_descriptions.fetch(type_id)
		else
			"No textual description available for archive type ##{type_id}"
		end
	end

	def to_i()
		return @type_id
	end

	def to_s()
		self.description(@type_id)
	end
end
