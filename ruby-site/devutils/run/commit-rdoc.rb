if false
depends_on "svnupdate"; # make sure all the svn changes for db stuff are committed.

# change to the right directory
savedir = Dir.getwd;
Dir.chdir($site.config.svn_base_dir);

# figure out relative paths
doc_dir = '.' + $site.config.doc_base_dir.sub($site.config.svn_base_dir, '');
site_dir = '.' + $site.config.site_base_dir.sub($site.config.svn_base_dir, '');

# run rdoc, add its files, revert the created.rid file (changes every run), and
# set eol-style.
%x{rdoc -S -o #{doc_dir} --template=/usr/local/allison/allison.rb -t "NexoDocs" #{site_dir}};
%x{sh -c 'svn add `find #{doc_dir} | grep -v \\\\\\.svn`'};
%x{svn revert #{doc_dir}/created.rid};
%x{sh -c 'svn propset svn:eol-style LF `find #{doc_dir} -type f | grep -v \\\\\\.svn`'};

# commit.
%x{svn ci -m "Changes to Documentation (NOMAIL)" #{doc_dir}};

Dir.chdir(savedir);
end
