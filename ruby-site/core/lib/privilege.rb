lib_require :Core, "attrs/class_attr";

module Privilege
	module Storage
		class PrivilegeName < Storable
			init_storable(:moddb, 'privilegenames');
			relation_singular(:typeiditem, "moduleid", TypeIDItem);
=begin
			def self.by_name(names)
				names = names.collect {|name| name.to_sym}
				objects = find(:conditions => ["name IN ?", names]);
				found = objects.collect {|obj| obj.name.to_sym; }
				missing = names - found;
				missing.each {|name|
					obj = PrivilegeName.new();
					obj.name = name;
					obj.moduleid = CoreModule.typeid
					obj.store();
					objects.push(obj);
				}
				return objects;
			end
=end
			def module_name
				return self.typeiditem.typename.chomp("Module");
			end

			def to_s
				return self.name;
			end
		end

		class GlobalGrant < Storable
			init_storable(:rolesdb, 'globalgrant');
			relation_singular(:privilege_name, "privilegeid", PrivilegeName);

			def moduleid
				return self.privilege_name.moduleid;
			end

			def module_name
				return self.privilege_name.module_name;
			end
		end

		class TypeRoleGrant < Storable
			init_storable(:rolesdb, 'typerolegrant');
			relation_singular(:privilege_name, "privilegeid", PrivilegeName);

			def module_name
				return self.privilege_name
			end
		end
		class TypeRole < Storable
			init_storable(:rolesdb, 'typeroles');
			relation_multi(:privileges, "roleid", Privilege::Storage::TypeRoleGrant);

			def typename
				return TypeID.get_class(self.typeid).name;
			end
		end
		class TypeRoleMember < Storable
			init_storable(:usersdb, 'typerolemembers');
		end
	end

	# This class does the work of finding out if a user has a privilege
	# bit set. The way this class will probably work when implemented, subject
	# to change, is that there will be the following tables in the database:
	#  - roleaccount: Each entry in this table identifies a role account, which
	#                 a user can be a member of.
	#  - roleprivilege: Each entry in this table identifies a specific privilege that
	#                   a user in that role has.
	#  - (auxiliary tables to map string-name privileges to integers)
	# A user gets their privileges through an ORing of all the roles relevant to
	# them for the object in question, plus for their :Global-type privileges.
	class Privilege
		attr_reader :type, :object;

		# The default is to give you a global privilege set. object is any
		# object that responds to object.id with a value meaningful to look up
		# privileges on (must be an account object of some sort).
		def initialize(userobj, object = :Global)
			@userid = userobj.userid;

			if (object == :Global)
				@global = nil;
				@objectid = 0;
				@localroles = [];
				@type = :Global;
			else
				@global = Privilege.new(userobj);
				@objectid = object.id;
				localrolesmember = Storage::TypeRoleMember.find(@objectid, @userid);
				@localroles = Storage::TypeRole.find(*localrolesmember.collect {|member| member.roleid; });
				@type = object.class.to_s.to_sym;
			end

			membership = userobj.account_membership.collect {|member| member.id; };
			@globalprivs = (membership.length == 0 ? [] : Storage::GlobalGrant.find(:conditions => ['accountid IN # AND objectaccountid = ?', membership, @objectid]));
		end

		# Returns true if the user has the privilege mod.bit set on the object
		# this class represents.
		def has?(mod, bit)
			if (@global && @global.has?(mod, bit))
				return true; # they have the privilege globally, so we're done.
			end

			modid = mod.typeid;
			bitid = Storage::PrivilegeName.find(modid, bit.to_s, :module_name);
			if (bitid.first)
				bitid = bitid.first.privilegeid;
			else
				return false; # privilege has never been set on anyone, so no.
			end

			# find out if the user has this privilege as part of a global priv
			# entry.
			@globalprivs.each {|priv|
				if (priv.privilegeid == bitid)
					return true;
				end
			}

			# find out if the user has this privilege under a local role.
			@localroles.each {|roleid|
				granted = Storage::TypeRoleGrant.find(:first, roleid, modid, bitid);
				if (granted)
					return true;
				end
			}

			# nothing granted the power, so no power. Done.
			return false;
		end
	end

	# Extending with this module and calling has_privileges extends the current class
	# with this interface that defines an interface for finding out what privileges
	# a user has to an instance of that class. Example:
	#  class Gallery
	#   extend Privileged
	#   define_privileges(GalleryModule); # module gallery, object type gallery.
	#   privilege :edit, :delete, :edit_pic, :delete_pic;
	#  end
	# ...
	#  somegallery.has_edit?(session) => user in session has the right to edit
	#                                    the gallery?
	module Privileged
		attr :priv_obj;

		def define_privileges(mod, *names)
			@priv_module = mod;
			privilege(*names);
		end

		def priv_module
			return @priv_module;
		end

		def privilege(*names)
			names.each {|name|
				class_eval %Q|
					def has_#{name}?()
						if (respond_to?(:pre_has_#{name}?))
							return true if pre_has_#{name}?;
						end

						return false if (PageRequest.current.session.anonymous?);

						if (!@priv_obj)
							@priv_obj = Privilege.new(PageRequest.current.session.user, self);
						end

						return @priv_obj.has?(self.class.priv_module, :#{name});
					end

					def assert_#{name}()
						if (!has_#{name}?)
							raise PageError.new(401), "Access Denied";
						end
					end
				|;
			}
		end
	end
end
