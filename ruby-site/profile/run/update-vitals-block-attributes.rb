# Does not need to be run!
# This was a patch script for the beta server. The fix has been merged into the migration script (migrate-user-profile-blocks).
$log.info("Nothing done. This was a patch script.")
exit;

lib_require :Profile, "profile_display_block";

db_list = $site.dbs[:usersdb].get_split_dbs();
db_list << $site.dbs[:anondb];

profile_module_type_id = TypeID.get_typeid("ProfileModule");

db_list.each{|user_db|
	user_db.query("UPDATE profiledisplayblocks SET visibility=5, path='admin_info' WHERE path='vitals' AND moduleid=?", profile_module_type_id);
}