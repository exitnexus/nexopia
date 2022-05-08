lib_want :FileServing, "type"
require 'RMagick'

if (site_module_loaded? :FileServing)
	class RecolouredSiteIcon < FileServing::Type
		register "recolour"
		immutable
		mog_class :generated
		
		def initialize(colour, rev, modname, *path)
			if (match = /^(aqua|black|blue|fuchsia|gray|green|lime|maroon|navy|olive|purple|red|silver|teal|white|yellow)$/i.match(colour))
				@colour_name = match[1].downcase()
				colour = colour.downcase()
			elsif (match = /^([0-9a-fA-F])([0-9a-fA-F])([0-9a-fA-F])$/.match(colour))
				@red, @green, @blue = "#{match[1]}#{match[1]}".to_i(16), "#{match[2]}#{match[2]}".to_i(16), "#{match[3]}#{match[3]}".to_i(16)
				colour = "#{match[1]}#{match[1]}#{match[2]}#{match[2]}#{match[3]}#{match[3]}"
			elsif (match = /^([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/.match(colour))
				@red, @green, @blue = match[1].to_i(16), match[2].to_i(16), match[3].to_i(16)
			else
				raise PageError.new(404), "Invalid colour code."
			end
			@mod = SiteModuleBase.get(modname)
			if(@mod.nil?())
				SiteModuleBase.site_modules(){|site_mod|
					if(modname.to_s.downcase() == site_mod.to_s.downcase())
						@mod = site_mod;
					end
				}
			else
				raise PageError.new(404), "No such module"
			end
			@remain = path
			super(colour, $site.static_number, modname, *path)
		end
		
		def not_found(out)
			static_path = @mod.static_path()
			file_path = "#{static_path}/#{@remain.join('/')}"

			if (File.file?(file_path))
				img = Magick::Image.read(file_path).first
				if (img && img.palette?)
					(0...img.colors).each {|idx|
						colormap = img.colormap(idx)
						$log.info("Palette entry: #{colormap}", :spam, :file_serving)
						if (!/rgba\([0-9]+,[0-9]+,[0-9]+,1\)/.match(colormap)) # no opacity information
							newcolormap = @colour_name || "rgb(#{@red},#{@green},#{@blue})"
							$log.info("Altering pixel #{idx}: #{colormap}->#{newcolormap}", :spam, :file_serving)
							img.colormap(idx, newcolormap)
						end
					}
					img.write(out.path)
				else
					raise PageError.new(404), "Image can't be palette-swapped."
				end
			else
				raise PageError.new(404), "No Such Module or File"
			end		
		end
	end
end