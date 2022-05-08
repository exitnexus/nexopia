module Gallery
	
	# ImageScience version
	if (false)
		require 'image_science'
		ImageManipulate = ImageScience
	else
		gem RUBY_PLATFORM == 'java' ? 'rmagick4j' : 'rmagick'
		require 'RMagick'
		
		# ImageMagick versions
		class ImageManipulate
			# Needs to implement:
			# - with_image
			# - save
			# - resize
			# - thumbnail
			# - with_crop
			# - cropped_thumbnail
			# - width
			# - height
			
			def self.with_image(filename, &block)
				$log.info("ImageManipulate loading image #{filename}", :spam, :image_manipulate)
				if (!File.file?(filename))
					raise TypeError, "No file."
				end
				img = Magick::Image.read(filename).first
				if (img)
					ImageManipulate.new(img).yield_and_destroy!(&block)
				else
					raise TypeError, "Unknown format."
				end
			end
			
			def initialize(magick_image)
				@img = magick_image
				$log.info("ImageManipulate object created with #{magick_image} (#{width}x#{height})", :spam, :image_manipulate)
			end
			
			def yield_and_destroy!()
				begin
					yield self
				ensure
					@img.destroy! if (@img.respond_to?(:destroy!)) # rmagick1 doesn't have the destroy method.
				end
			end
						
			def width()
				return @img.columns
			end
			def height()
				return @img.rows
			end
			
			def save(filename)
				$log.info("Storing image at #{filename}, dimensions #{width}x#{height}", :spam, :image_manipulate)
				@img.format = "JPG"
				@img.write(filename)
			end
			
			def resize(width, height, &block)
				$log.info("Resizing to #{width}x#{height}", :spam, :image_manipulate)
				ImageManipulate.new(@img.resize(width, height)).yield_and_destroy!(&block)
			end
			
			def cropped_thumbnail(size, &block)
				$log.info("Cropping thumbnail to size #{size}", :spam, :image_manipulate)
				begin
					min_edge = [self.width, self.height].min
					img = @img.crop(Magick::CenterGravity, min_edge, min_edge, true)
					img.resize!(size, size)
				rescue
					img.destroy! if (img.respond_to?(:destroy!)) # rmagick1 doesn't have the destroy method.
					raise
				end
				ImageManipulate.new(img.crop_resized(size, size)).yield_and_destroy!(&block)
			end
			
			def with_crop(x1, y1, x2, y2, &block)
				$log.info("Cropping to #{x1},#{y1},#{x2},#{y2}", :spam, :image_manipulate)
				ImageManipulate.new(@img.crop(x1, y1, x2 - x1, y2 - y1)).yield_and_destroy!(&block)
			end
			
			def thumbnail(longest_size, &block)
				if (width > height)
					$log.info("Creating thumbnail of width-oriented picture to ratio #{width.to_f/longest_size}", :spam, :image_manipulate)
					ImageManipulate.new(@img.resize(longest_size.to_f / width)).yield_and_destroy!(&block)
				else
					$log.info("Creating thumbnail of height-oriented picture to ratio #{height.to_f/longest_size}", :spam, :image_manipulate)
					ImageManipulate.new(@img.resize(longest_size.to_f / height)).yield_and_destroy!(&block)
				end
			end
		end
	end
	
	class ImageManipulate
		def resize_max(max_width, max_height, &block)
			if (width < max_width && height < max_height)
				return block.call(self)
			end

			$log.info("Resizing image (#{width},#{height}) based on max size of #{max_width},#{max_height}", :spam, :gallery)

			# do the math for both kinds of resize and choose the one
			# with the largest surface area that still fits within the
			# max size.
			width_ratio = max_width.to_f / width
			height_ratio = max_height.to_f / height
			$log.info("Ratios: width=#{width_ratio}, height=#{height_ratio}", :spam, :gallery)

			width_based_height = height * width_ratio
			height_based_width = width * height_ratio

			# make sure neither of those are zero
			if (width_based_height < 1)
				width_based_height = 1
			end
			if (height_based_width < 1)
				height_based_width = 1
			end
			$log.info("Opposites: width-based-height=#{width_based_height},height-based-width=#{height_based_width}", :spam, :gallery)

			width_based_area = max_width * width_based_height
			height_based_area = max_height * height_based_width
			$log.info("Areas: width=#{width_based_area},height=#{height_based_area}", :spam, :gallery)

			preference = if (width_based_area > height_based_area)
				[:width_based, :height_based]
			else
				[:height_based, :width_based]
			end
			$log.info("Preference: #{preference.join(',')}", :spam, :gallery)

			preference.each {|pref|
				case pref
				when :width_based
					if (width_based_height < max_height)
						$log.info("Chose width based (#{width_based_height} < #{max_height})", :spam, :gallery)
						return resize(max_width, width_based_height, &block)
					end
				when :height_based
					if (height_based_width < max_width)
						$log.info("Chose height based (#{height_based_width} < #{max_width})", :spam, :gallery)
						return resize(height_based_width, max_height, &block)
					end
				end
			}

			# This is a last resort and should never be called
			return thumbnail([max_width, max_height].max, &block)
		end
	end
end

