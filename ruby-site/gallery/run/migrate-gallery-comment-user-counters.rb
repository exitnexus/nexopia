lib_require :Gallery, "gallery_comment";

result = TypeIDItem.db.query("SELECT `typeid` FROM `typeid` WHERE typename = 'Comments::GalleryComment'");
old_type_id = nil;
result.each{|row|
	old_type_id = row['typeid'].to_i();
};

if(old_type_id.nil?())
	$log.info("The old type id for Comments::GalleryComment was nil", :critical)
end

db_list = $site.dbs[:usersdb].get_split_dbs();
db_list << $site.dbs[:anondb];

db_list.each{|user_db|
	user_db.query("UPDATE usercounter SET area=? WHERE area=?", Gallery::GalleryComment.typeid, old_type_id);
};
