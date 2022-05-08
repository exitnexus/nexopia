lib_require :Banner, 'banner_client', 'banner'

class BannerPage < PageHandler
	declare_handlers("banner") {
		handle :GetRequest, :show_banner, input(Integer)
	}

	def show_banner(id)
		b = Banner.find(:first, id)
		self.send(:"show_banner_#{b.bannertype}", b)
	end

	def show_banner_image(banner)
		if (banner.image.empty?)
			banner.image = banner.id + ".jpg"
		end
		if (banner.image[0,7] != "http://")
			banner.image = $site.banner_url  + banner.image;
		end
		t = Template::instance('banner', 'image')
		t.banner = banner
		puts t.display
	end

	def show_banner_flash(banner)
		if(banner.image[0,7] != "http://")
			banner.image = $site.banner_url + banner.image
		end
		if(banner.alt[0,7] != "http://")
			banner.alt = $site.banner_url + banner.alt
		end
		banner.alt.gsub!("%link%", "/bannerclick.php?id={id}")

		t = Template::instance("banner", "flash")
		t.banner = banner
		puts t.display
	end

	def show_banner_iframe(banner)
		if(banner.image[0,7] != "http://")
			banner.image = $site.banner_url + banner.image
		end
		t = Template::instance('banner', 'iframe')
		t.banner = banner
		puts t.display
	end

	def show_banner_html(banner)
		if (banner.alt.index("%"))
			rand = rand(1000000000);
			banner.alt.gsub!("%rand%", rand.to_s)
			banner.alt.gsub!("%page%", PageHandler.top.request.uri.to_s)
			banner.alt.gsub!("%age%", request.session.user.age.to_s)
			banner.alt.gsub!("%sex%", request.session.user.sex.to_s)
			banner.alt.gsub!("%skin%", request.session.user.skin.to_s)
			banner.alt.gsub!("%id%", banner.id.to_s)
			banner.alt.gsub!("%size%", banner.size.to_s)
			banner.alt.gsub!("%server%", $site.config.banner_domain.to_s)
			banner.alt.gsub!("%link%", "/bannerclick.php?id=#{banner.id}")
			banner.alt.gsub!("%passback%", $site.www_url / "bannerview.php" & {:size => banner.size, :pass => banner.id})
			banner.alt.gsub!("%passbackjs%", $site.www_url / "bannerview.php" & {:size => banner.size, :pass => banner.id, :js => 1})
		end

		t = Template::instance('banner', 'html')
		t.banner = banner
		puts t.display
	end

	def show_banner_text(banner)
		t = Template::instance('banner', 'text')
		t.banner = banner
		puts t.display
	end

end
