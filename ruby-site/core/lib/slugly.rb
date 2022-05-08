require 'unicode'

module Slugly
	def Slugly.slugify(str)
		slug_str = Unicode::normalize_KD(str).
			gsub(/&/,' and ').
			gsub(/['"`]/,'').
			gsub(/[^a-zA-Z0-9\s]/,'')
		slug_str = slug_str.
			gsub(/\W+/, '-').
			gsub(/^-+/,'').
			gsub(/-+$/,'').downcase

		return slug_str
	end
end

class Storable
	class << self
		def make_slugable(column_sym, slug_sym=:"#{column_sym.to_s}_slug")
			define_method slug_sym, lambda {
				return Slugly::slugify(self.send(column_sym))
			}
		end
	end
end