# Update and then restart stuff
`svn up --ignore-externals -r #{ENV['COMMIT_REV']} #{$site.config.svn_base_dir}`;
`sudo sv restart ruby-devsite ruby-queue`
