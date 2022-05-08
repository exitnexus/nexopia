require 'pathname'
class Environment
  FILE='nxenv'

  def initialize(ident = false)
    @ident = if ident then ident + '.' else '' end
    @shell = ENV['SHELL']
    @envExp,@envSep = if /csh$/ =~ @shell
                        ['setenv',' ']
                      else
                        ['export','=']
                      end
  end

  def getFilename       
    nodeConfigFile = FILE + '.' + @ident + File.basename(@shell)
  end
  def openEnvFile()
    filename = self.getFilename
    @file = File.open(filename,"w")
    @file.puts "#!#{@shell}"
    @file.puts "#### Nx Env File, Created #{Time.now} ####"
  end

  def writeEnv(var,value)
    @file.puts "#{@envExp} #{var.upcase}#{@envSep}#{value.dump}"
  end
  def closeEnvFile()
    @file.puts "#### Nx Env File, Closed #{Time.now} ####"
    @file.flush
    @file.close
  end
end
if $0 == __FILE__ 
  
  config_name = if ARGV[0].nil? then ENV['USER'] || 'dev' else ARGV.shift end
   # any dirs that are expected in the code to be in the general rubypath space
   dirs = [
     'config'         
   ]
   
  site_dir = Pathname.new(File.dirname(__FILE__)).realpath.to_s + "/"
  
  nxEnv = Environment.new(config_name)
  nxEnv.openEnvFile
  rubylib = [site_dir]
  rubylib << dirs.collect do | comp|
      "#{site_dir}/#{comp}/"
   end
  nxEnv.writeEnv('SITE_CONFIG_NAME',config_name)
  nxEnv.writeEnv('SITE_BASE_DIR',site_dir)
  nxEnv.writeEnv('RUBYLIB', rubylib.join(':'))
  nxEnv.closeEnvFile
end