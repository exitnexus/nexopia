lib_require :Core, "template/default_view"

module Template
	class DefaultView
		#
		# Translate Sam Nodes back to text nodes
		#
		def self.translate_sam(node,code,&block)
			sam = Samifier.new('en')
			sam_id = node.attributes['sam_id'].to_i
			node.text = sam[sam_id]
			## remove Sam Comments
			node.delete_if() { |child|
				child.kind_of? REXML::Comment
			}
			yield node
		end
	end
end