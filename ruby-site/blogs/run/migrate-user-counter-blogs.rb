lib_require :Blogs, "blog_post", "blog_comment";

php_blog_post_type_id = 1;
php_blog_comment_type_id = 2;

db_list = $site.dbs[:usersdb].get_split_dbs();
db_list << $site.dbs[:anondb];

db_list.each{|user_db|
	user_db.query("UPDATE usercounter SET area=? WHERE area=?", Blogs::BlogPost.typeid, php_blog_post_type_id);
	user_db.query("UPDATE usercounter SET area=? WHERE area=?", Blogs::BlogComment.typeid, php_blog_comment_type_id);
};
