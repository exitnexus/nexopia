lib_require :Core, 'acts_as_uri';
module Acts #:nodoc:
  module As #:nodoc:
    module FileURI
#--
      def	self.append_features(base)
        super

        base.extend ClassMethods
        base.extend Acts::As::URI::ClassMethods
      end
#++
      module InstanceMethods

        #
        # use hash_id to construct directory
        #
        def hash_dir
          "#{hash_id/1000}/#{hash_id}"
        end

      end

      module ClassMethods
        #
        # This is a special case of acts_as_uri
        #
        #* It expects the following options
        #[:site_address]
        #[:file_group] Identifier
        #[:hash_id] Used for "#{hash_id/1000}/#{hash_id}"
        #[:file_id] Identifier for file in the hash and file_group ex :10
        #[:extention] the extention for this id, ex "jpg"
        #
        #
        #* assumed options are
        #* options[:uri_spec] = [:site_address,:file_group,:hash_dir,"file_id.to_s + '.' + extention".intern]
        #* options[:protocol] ="http:"
        #
        #==Example
        #
      	# acts_as_file_uri(:hash_id => :userid,
      	# :site_address => $site.image_url,:file_group =>'userpics',
      	# :file_id => :id, :extention => 'jpg')
        #
        #* Creates a uri like .. http://static.nexopia.com/pineapples/0/5/190.jpg
        #
        #
        def acts_as_file_uri(options = {})
          options[:uri_spec] = [:site_address,:file_group,:hash_dir,"file_id.to_s + '.' + extention".intern]
          options[:protocol] ="http:"

          #
          # Intialize as uri first with all the options that belong to that
          #
          acts_as_uri(options)
          #
          # This needs to be done explicitly here in this module space
          # or the methods will not be visible.
          # (i havn't figured out why yet) I suspect it has to do with name conflicts
          # and who knows.. posted a message to ruby talk
          class_eval <<-EOV
             include InstanceMethods
          EOV

        end

      end #ClassMethods

    end #FileURI
  end
end

class Storable
  include Acts::As::FileURI
end
