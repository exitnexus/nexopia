
require 'stringio'
=begin rdoc

 This class fills a role to generate a script to
 create and drop databases as well as creates the
 tables that belong in the database
 
 It has two files as output. One that is a module
 that has a method to set up the config class and
 another one that is the load_schema script.
 
 # 
 # This was written as throw away code.
 #
 
 
=end
class SchemaGen
  
  #
  # include Drop DB Statement as part of load script 
  #

  DROP_ONLY = false
  # modify the struct.sql file that is poop
  # into not poop.

  DBSERV = 'dbserv'
  HOST = '127.0.0.1'
  LOGIN = 'root'
  PASS = 'Hawaii'


  DB_TABLE_SQL_EXTRACT= /--\s(\w+)\.(\w+)\s*\n+(.+?)\n+(?:^--[-]+)?\n/m
  
  def initialize(file)
    @input_file = file
    @sql_script = StringIO.new
    @db_config = StringIO.new
  end
  
  def writeDBConfig
    File.open("localdb.rb",File::CREAT|File::RDWR|File::TRUNC) {|f|
      f << @db_config.string
    }
  end
  
  def writeSchemaScript    
    File.open("load_schema.sql",File::CREAT|File::RDWR|File::TRUNC) {|f|
      f << @sql_script.string
    }
  end
  
  def process
    prefixDBConfig    
    file = IO.read(@input_file)
    matches = file.scan(DB_TABLE_SQL_EXTRACT)
    matches.each {|match|
        #
        # Special case for userdb
        # becomes three differnt logical databases
        # with all of the tables of 'userdb'        
        if match[0] === 'usersdb'
          dbs = ['newusersanon','newusers','newusers1']
          dbs.each {|db|            
            updateSchemaScript(db,match[1],match[2])
          }
        else
          updateDBConfig(*match[0..1])        
          updateSchemaScript(*match)
        end
      }    
    
    postfixDBConfig
    
  end
  def prefixDBConfig
    @db_config << %Q^module LocalDBConfig\n
    class << self
    def load_database
      puts "Local Database Settings Requested"
    Proc.new {
    ^
    @db_config << %Q^
      database(:#{DBSERV}) {|conf|
          conf.options = {
          :host => '#{HOST}',
          :login =>'#{LOGIN}',
          :passwd =>'#{PASS}',
          :debuglevel => 2          
        }  
      }
    ^
  end
  
  def postfixDBConfig
    @db_config << %Q^
      }
    end
    end # class << self

    end
    ^
  end
  
  def updateDBConfig(db_name,table_name)

    @db_config << %Q^

      database(:#{db_name}) {|conf|
        conf.live = true
        conf.inherit = :#{DBSERV}
        conf.options = {:db => '#{db_name}'}
      }        
    ^
  end
  
  def updateSchemaScript(db_name,table_name,sql)
    @processed_dbs ||= []
    
    unless @processed_dbs.include? db_name
        @processed_dbs << db_name        
        @sql_script << "drop database #{db_name} ;\n" if DROP_ONLY            
    end
     @sql_script << "create database if not exists #{db_name} ;\n" unless DROP_ONLY
     @sql_script << "use #{db_name} ;\n" unless DROP_ONLY    
     @sql_script << "#{sql} ;\n" unless DROP_ONLY
     
  end
end

if __FILE__ == $0



s = SchemaGen.new('struct.sql')
s.process
s.writeDBConfig
s.writeSchemaScript




end