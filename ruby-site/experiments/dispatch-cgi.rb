#!/bin/env ruby

file = File.new("/var/log/lighttpd/r.log", "w+");
$debugout = file;

require 'cgi';
require 'erb';
require "config";
require "pagehandler";
require "var_dump";
require "dispatch";

dispatch(file, CGI.new);
