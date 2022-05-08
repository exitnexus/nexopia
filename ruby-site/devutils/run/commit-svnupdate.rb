# Update and then restart stuff
`svn up --username autopush --password CoicjebFa --ignore-externals -r #{ENV['COMMIT_REV']} #{$site.config.svn_base_dir}`;
`sudo sv restart ruby-devsite ruby-queue`
