<?php
$login = 1;
$devutil = true;

require_once "include/general.lib.php";

function delete_files($dirname)
{ // recursive function to delete
  // all subdirectories and contents:
  if(is_dir($dirname))
	$dir_handle=opendir($dirname);

  while($file=readdir($dir_handle))
  {
    if($file!="." && $file!="..")
    {
      if(!is_dir($dirname."/".$file))
		unlink ($dirname."/".$file);
      else {
		delete_files($dirname."/".$file);
		rmdir($dirname."/".$file);
	  }
    }
  }
  closedir($dir_handle);
  return true;
}

delete_files($cache->basedir);
