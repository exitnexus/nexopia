lib_require :Comments, "comment_post_result";

module Comments
	class CommentDeleteResult < CommentPostResult
		attr_accessor :author_id, :removed_comment_id;
		
		ERROR_STRINGS = {
			:not_found => "was not found",
			:deleted => "was already deleted",
			:not_allowed => "was not allowed to be deleted",
			:block_not_exist => "was not provided with a valid block"
		}
		
		def to_json(*a)
			{
				:json_class => self.class.name,
				:success => @success,
				:comment_id => @comment_id,
				:removed_comment_id => @removed_comment_id,
				:comment_content => self.render_comment(),
				:error => self.json_translate_error()
			}.to_json(*a);
		end
		
		def json_translate_error()
			err_str = self.translate_error();
			if(err_str.length < 1)
				return "";
			end
			
			return "The comment could not be deleted because it #{err_str}."
		end
		
		def translate_error()
			if(@error.nil?())
				return "";
			end
			
			return ERROR_STRINGS[@error];
		end
	end
end