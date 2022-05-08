# lib_require :Core, 'storable/relation', 'storable/relation_manager', 'storable/relation_prototype'
# 
# module RelationIntegrationSpecHelper
# 	def build_pic(attributes={})
# 		pic = Gallery::Pic.new
# 		pic.stub!(:validate!)
# 		pic.stub!(:after_create)
# 		pic.stub!(:after_update)
# 		pic.stub!(:before_delete)
# 		attributes.each_pair {|key, val|
# 			pic.send(:"#{key}=", val)
# 		}
# 		return pic
# 	end
# 	
# 	def build_gallery(attributes={})
# 		gallery = Gallery::GalleryFolder.new
# 		gallery.stub!(:validate!)
# 		gallery.stub!(:after_create)
# 		gallery.stub!(:after_update)
# 		gallery.stub!(:before_delete)
# 		attributes.each_pair {|key, val|
# 			gallery.send(:"#{key}=", val)
# 		}
# 		return gallery
# 	end
# end
# 
# describe "Relation Integration Tests ->", "Gallery Caching Tests:" do
# 	include RelationIntegrationSpecHelper
# 	
# 	before do
# 		@gallery = build_gallery(:id => 1, :ownerid => 1)
# 		@pic1 = build_pic(:userid => 1, :galleryid => 1, :id => 1, :description => "Pic 1", :priority => 1)
# 		@pic2 = build_pic(:userid => 1, :galleryid => 1, :id => 2, :description => "Pic 2", :priority => 2)
# 		
# 		@gallery.store
# 		@pic1.store
# 		@pic2.store
# 	end
# 	
# 	it "should invalidate cache entries for pics when a new pic is added to a gallery" do
# 		$log.log_minlevel_lower([:general, :sql, :memcache], :debug) {
# 			@gallery.pics.length.should == 2
# 			new_pic = build_pic(:userid => 1, :galleryid => 1, :id => 3, :description => "Pic 3", :priority => 3)
# 			new_pic.store
# 			@gallery.pics.length.should == 3
# 		}
# 	end
# 	
# 	after do
# 		Gallery::GalleryFolder.test_reset
# 		Gallery::Pic.test_reset
# 	end
# end