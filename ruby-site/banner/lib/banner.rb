lib_require :FileServing, "type"

class Banner < Storable
	set_enums(:bannersize => {
		:none => 0,
		:banner => 1, 
		:leaderboard => 2, 
		:bigbox => 3,
		:sky120 => 4,
		:sky160 => 5,
		:button60 => 6,
		:vulcan => 7,
		:link => 8
	}, :bannertype => {
		:none => 0,
		:image => 1,
		:flash => 2,
		:iframe => 3,
		:html => 4,
		:text => 5
	})
	
	SIZES = {
		:banner => {
			:height => 60,
			:width => 468
		}, 
		:leaderboard => {
			:height => 90,
			:width => 728
		}, 
		:bigbox => {
			:height => 250,
			:width => 300
		},
		:sky120 => {
			:height => 600,
			:width => 120
		},
		:sky160 => {
			:height => 600,
			:width => 160
		},
		:button60 => {
			:height => 60,
			:width => 120
		},
		:vulcan => {
			:height => nil,
			:width => nil
		},
		:link => {
			:height => nil,
			:width => nil
		},
	}
	init_storable(:bannerdb, 'banners')
	
	def size
		return self.bannersize!.value
	end
	
	def height

		return SIZES[self.bannersize][:height]
	end

	def width
		return SIZES[self.bannersize][:width]
	end
		
end

class BannerFileType < FileServing::Type
	register "banner"
	register "banners"
	
	# This pulls from the legacy mogile instance if it's set up 
	# by pulling from the mogile paths that the old site used in order.
	def not_found(out_file)
		if (legacy = $site.mogile_connection(:legacy))				
			if (data = legacy.get_file_data([self.class.typeid, *self.path].join('/')))
				out_file.write(data)
				return true
			end
		end
		return super(out_file) # let it throw a 404
	end
end