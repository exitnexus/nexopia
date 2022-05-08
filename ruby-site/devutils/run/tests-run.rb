require_gem 'rmail'
require 'net/smtp'
require 'test/unit'
require 'stringio'
lib_require :Devutils, 'tests', 'test_status'

Test::Unit.run = true
tests = Tests.instance();
tests.revision = ENV['COMMIT_REV']
tests.author = ENV['COMMIT_AUTHOR']
tests.run_all
tests.process_results