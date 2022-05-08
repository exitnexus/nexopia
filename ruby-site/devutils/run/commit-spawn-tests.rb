if false
depends_on "svnupdate"
depends_on "generate-testdb"

require "rbconfig"
include Config

# spawn off the test runner as a seperate process
rcovbin = CONFIG["bindir"] + "/rcov";
system("#{rcovbin} -x '/generated/' -o ../ruby-cov --html nexopia.rb -- -c #{$site.config.class.config_name} -r tests");

%x{sh -c 'svn add `find ../ruby-cov | grep -v \\\\\\.svn`'};
%x{sh -c 'svn propset svn:eol-style LF `find ../ruby-cov -type f | grep -v \\\\\\.svn`'};
%x{svn ci -m "Changes to Coverage (NOMAIL)" ../ruby-cov};
end
