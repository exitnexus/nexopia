#!/usr/bin/env ruby

require "mkmf"
# 
# $LIBPATH.push(Config::CONFIG['libdir'])
# 
# def crash str
#   print " extconf failure: %s\n", str
#   exit 1
# end
# 
# # unless have_header 'php_embed'
# #   crash('need php_embed')
# # end
# 
# $LDFLAGS << ' ' + `xml2-config --libs`.chomp
# 
# $CFLAGS << ' ' + `xml2-config --cflags`.chomp
# $CFLAGS = '-g -Wall ' + $CFLAGS
# 

base = with_config('php-dir')

# exit 1 unless base.strip.length > 0
# system "echo \"bob was here\" > /tmp/bob"

#  then
# 	system "echo \"bob was here >>> #{mc}\" > /tmp/bob"
# 	base = php_dir
# else
# 	exit 1
# end

# base = "/home/shealy/vmware-share/php_dir"
cflags = "-I#{base}/lib -I#{base}/include/php -I#{base}/include/php/main -I#{base}/include/php/Zend -I#{base}/include/php/TSRM"
ldflags = "-L#{base}/lib -lphp5"

# Config::MAKEFILE_CONFIG['CFLAGS'].sub! /^/, "#{cflags} "
# Config::MAKEFILE_CONFIG['LDFLAGS'].sub! /^/, "#{ldflags} "

$CFLAGS = cflags
$LDFLAGS = ldflags

create_header
create_makefile "RAP_clib"