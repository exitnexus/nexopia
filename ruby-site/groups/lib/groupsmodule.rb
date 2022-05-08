lib_require :Core, 'visibility';

class GroupsModule < SiteModuleBase
	Visibility.set_module_specific_options(GroupsModule, [:none, :friends, :friends_of_friends, :logged_in]);
end
