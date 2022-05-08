#!/usr/bin/env ruby

# run as /path/to/nexopia-ctl.rb [start|stop|zap|...] [nexopia.rb options here]
# run with just -h for help on nexopia-ctl options, look at nexopia.rb for its options.

require "rubygems"
require "daemons/daemonize"

mypath = $0;
mypath[/[^\/]+$/] = 'nexopia.rb'; # replace the filename.

mode = ARGV[0];

# find the config name and use that in the pidfile name
config = nil
ARGV.each {|arg|
	if (config)
		config = arg;
		break
	elsif (arg == '-c' || arg == '--config')
		config = true
	end
}
if (config.class == String)
	config = ".#{config}"
else
	config = ""
end

pidfname = mypath + config + '.pid';

module Daemonize
   def daemonize(logfile_name = nil, app_name = nil)
     srand # Split rand streams between spawning and daemonized process
     safefork and exit # Fork and exit from the parent

     # Detach from the controlling terminal
     unless sess_id = Process.setsid
       raise Daemons.RuntimeException.new('cannot detach from controlling terminal')
     end

     # Prevent the possibility of acquiring a controlling terminal
     #if oldmode.zero?
       trap 'SIGHUP', 'IGNORE'
       exit if pid = safefork
     #end

     $0 = app_name if app_name

#     Dir.chdir "/"   # Release old working directory
#     File.umask 0000 # Insure sensible umask

     # Make sure all file descriptors are closed
     ObjectSpace.each_object(IO) do |io|
       unless [STDIN, STDOUT, STDERR].include?(io)
         begin
           unless io.closed?
             io.close
           end
         rescue ::Exception
			$log.info $!;
			$!.backtrace.each{|line|
				$log.info line;
			}         end
       end
     end

     # Free file descriptors and
     # point them somewhere sensible
     # STDOUT/STDERR should go to a logfile

     STDIN.reopen "/dev/null" rescue nil

     if logfile_name
       begin
         STDOUT.reopen logfile_name, "a"
       rescue ::Exception
         STDOUT.reopen "/dev/null" rescue nil
       end
     else
       STDOUT.reopen "/dev/null" rescue nil
     end

     STDERR.reopen STDOUT rescue nil

     #return oldmode ? sess_id : 0   # Return value is mostly irrelevant
     return sess_id
   end
end

include Daemonize;

pid = nil;
begin # ignore errors in loading the file
	File.open(pidfname, 'r') {|pidf|
		pid = pidf.gets.to_i;
	}
rescue Errno::ENOENT;
end

case mode
when 'start'
	if (pid && `ps ax | egrep '^#{pid}'`.length > 0)
		puts "Already running?";
	else
		daemonize();
		File.open(pidfname, 'w') {|pidf|
			pidf.puts(Process.pid);
		}

		load(mypath);
	end
when 'stop'
	Process.kill('SIGTERM', pid) if pid;
when 'rehash'
	Process.kill('SIGHUP', pid) if pid;
end
