module EnhancedTextInput
	class EnhancedTextInput < PageHandler
		declare_handlers("enhanced_text_input") {
			area :Public
			access_level :Any
		
			page   :GetRequest, :Full, :get_preview, "preview"
		}
	
		class DoPreview
			extend UserContent
			attr :str
			def initialize(str)
				@str = str
			end
			user_content :str
		end
	
		def get_preview
			print(DoPreview.new(params['source_text', String, ""]).str.parsed)
		end
	end
end

