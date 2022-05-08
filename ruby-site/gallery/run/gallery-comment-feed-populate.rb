lib_require :Gallery, 'gallery_comment'
lib_require :Scoop, 'event'

comments = Gallery::GalleryComment.find(:all, :scan)
comments.each {|comment|
	if (!comment.owner.nil? && !comment.author.nil? && Scoop::Event.find(:first, :reporter, Gallery::GalleryComment.typeid, comment.userid, comment.id).nil?)
		comment.generate_event(:create)
	end
}