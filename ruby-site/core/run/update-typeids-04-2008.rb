
$site.dbs[:db].query("DELETE FROM typeid WHERE typeid IN (1,2,5,10,11,12,16)");
$site.dbs[:db].query("DELETE FROM typeid WHERE typename IN ('Blog', 'Blog::BlogComments', 'Comments::Comment', 'Gallery::SourcePic', 'Gallery::GalleryFolder', 'Gallery::Pic', 'MessageFolder')");

$site.dbs[:db].query("INSERT INTO typeid SET typeid = 1, typename = 'Blog'");
$site.dbs[:db].query("INSERT INTO typeid SET typeid = 2, typename = 'Blog::BlogComments'");

$site.dbs[:db].query("INSERT INTO typeid SET typeid = 5, typename = 'Comments::Comment'");

$site.dbs[:db].query("INSERT INTO typeid SET typeid = 10, typename = 'Gallery::SourcePic'");
$site.dbs[:db].query("INSERT INTO typeid SET typeid = 11, typename = 'Gallery::GalleryFolder'");
$site.dbs[:db].query("INSERT INTO typeid SET typeid = 12, typename = 'Gallery::Pic'");

$site.dbs[:db].query("INSERT INTO typeid SET typeid = 16, typename = 'MessageFolder'");
