require 'mkmf'

extension_name = 'bbcode'

# The destination
dir_config(extension_name)

Config::MAKEFILE_CONFIG['LDSHARED'] = Config::MAKEFILE_CONFIG['LDSHARED'].gsub("$(CC)", "$(CXX)").gsub("cc", "g++")

# Do the work
create_makefile(extension_name)
