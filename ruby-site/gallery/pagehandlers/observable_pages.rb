module Gallery
	class Display < PageHandler
		declare_handlers("ObservableDisplay") {
			area :Internal
			handle :GetRequest, :display_collapsed_gallery, "display", "Gallery__Pic__CollapsedPicEvent"
			handle :GetRequest, :display_pic, "display", "Gallery__Pic"
			handle :GetRequest, :display_gallery, "display", "Gallery__Gallery"
			
		}

MSG = <<EOF
	<div>
		<div>
			\#{originator.link} added {list.size} pictures to the gallery \#{list.first.object.gallery.link}.  
			<t:if t:id="list.size > 4">
				&nbsp; (Showing the first {(4 > list.size) ? list.size : 4}.)
			</t:if>
		</div>
		<br/>
		<div class="gallery_quick_view">
			<t:loop t:id="list[0...4]" t:iter="image">
				<a t:id="image.object">
					<div class="gallery_quick_view_frame">
						<img class="gallery_quick_view" t:id="image.object.thumb"/>
					</div>
				</a>
			</t:loop>
			<div class="clearit"></div>
		</div>
		<t:if t:id="list.size > 4">
			<div class="gallery_quick_view" id="pic_display_{list.first.id}" style="display: none">
				<t:loop t:id="list[4..-1]" t:iter="image">
					<a t:id="image.object">
						<div class="gallery_quick_view_frame">
							<img class="gallery_quick_view" t:id="image.object.thumb"/>
						</div>
					</a>
				</t:loop>
				<div class="clearit"></div>
			</div>
			<a href="#" onClick="show_img_pane('pic_display_{list.first.id}'); return false;">Show all</a>
		</t:if>
	</div>
EOF
Template.inline(:Gallery, "CollapsedAddEvent", MSG);

		def display_collapsed_gallery
			obj = params.to_hash["obj"];
			t = Template.instance(:Gallery, "CollapsedAddEvent");
			t.list = obj.list;
			t.originator = obj.originator;
			puts t.display
		end


MSG2 = <<EOF
	<div>
		<div>
			\#{gallery.owner.link} created the gallery \#{gallery.link}.  
			<t:if t:id="gallery.pics.length > 4">
				&nbsp; (Showing the first {(4 > gallery.pics.length) ? gallery.pics.length : 4} of {gallery.pics.length}.)
			</t:if>
		</div>
		<br/>
		<div class="gallery_quick_view">
			<t:loop t:id="pics[0...4]" t:iter="image">
				<a t:id="image">
					<div class="gallery_quick_view_frame">
						<img style="width: 90px" t:id="image.thumb"/>
					</div>
				</a>
			</t:loop>
			<div class="clearit"></div>
		</div>
		<t:if t:id="gallery.pics.length > 4">
			<div class="gallery_quick_view" id="pic_display_{gallery.id}" style="display: none">
				<t:loop t:id="pics[4..-1]" t:iter="image">
					<a t:id="image">
						<div class="gallery_quick_view_frame">
							<img style="width: 90px" t:id="image.thumb"/>
						</div>
					</a>
				</t:loop>
				<div class="clearit"></div>
			</div>
			<a href="#" onClick="show_img_pane('pic_display_{gallery.id}'); return false;">Show all</a>
		</t:if>
	</div>
EOF
Template.inline(:Gallery, "GalleryAddEvent", MSG2);
		def display_gallery
			gallery = params.to_hash["gallery"];
			t = Template.instance(:Gallery, "GalleryAddEvent");
			t.gallery = gallery
			t.pics = gallery.pics.map{|m|m}
			puts t.display
		end


MSG3 = <<EOF
	<div>
		<div>
			\#{pic.gallery.owner.link} added a picture to the gallery \#{pic.gallery.link}.  
		</div>
		<br/>
		<div class="gallery_quick_view">
			<a t:id="pic">
				<div class="gallery_quick_view_frame">
					<img style="width: 90px" t:id="pic.thumb"/>
				</div>
			</a>
			<div class="clearit"></div>
		</div>
	</div>
EOF
Template.inline(:Gallery, "GalleryPicAddEvent", MSG3);
		def display_pic
			pic = params.to_hash["pic"];
			t = Template.instance(:Gallery, "GalleryPicAddEvent");
			t.pic = pic
			puts t.display
		end

	end

end