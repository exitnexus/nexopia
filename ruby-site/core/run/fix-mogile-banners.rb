lib_require :Core, 'users/user'
lib_want :Userpics, 'pics'

lib_require :Core, 'benchmark', 'rangelist'
lib_require :Core, 'pagerequest','pagehandler'
lib_require :Banner, 'banner'

$mog = MogileFS::MogileFS.new(:hosts => $site.config.mogilefs_hosts, :domain => 'nexopia.com')

puts("stat\t{type}\t{serverid}\t{mogpath}\t{OK|FAIL}\t{Reason}")

Dir["#{$site.config.banners_dir}/**/*"].each {|file|
	if (File.file?(file))
		mog_file = file.gsub(/^#{$site.config.banners_dir}/, '')
		mog_path = "#{BannerFileType.typeid}#{mog_file}"
		print("stat\tbanners\tN/A\t#{mog_path}\t")
		if ($mog.get_paths(mog_path))
			puts("OK\tAlready done")
			next
		end
		begin
			$mog.store_file(mog_path, "source", file)
		rescue
			puts("FAIL\tError pushing to mogile: #{$!}")
			next
		end
		puts("OK\tPushed to mogile.")
	end
}