#!/bin/bash

if [ `cat /proc/mounts | grep "/home/nexopia/public_html_ram" | wc -l` = 0 ]
then
	echo "mounting /home/nexopia/public_html_ram";
	mount /home/nexopia/public_html_ram
	mkdir /home/nexopia/public_html_ram/include
	mkdir /home/nexopia/public_html_ram/skins
	ln -s /home/nexopia/public_html/users   /home/nexopia/public_html_ram/users
	ln -s /home/nexopia/public_html/uploads /home/nexopia/public_html_ram/uploads
	ln -s /home/nexopia/public_html/gallery /home/nexopia/public_html_ram/gallery
	ln -s /home/nexopia/public_html/images  /home/nexopia/public_html_ram/images
fi

if [ `cat /proc/mounts | grep "/home/nexopia/cache" | wc -l` = 0 ]
then
	echo "mounting /home/nexopia/cache";
	mount /home/nexopia/cache
fi
chmod 777 /home/nexopia/cache

rsync -rWogt /home/nexopia/public_html/include/ /home/nexopia/public_html_ram/include;
rsync -rWogt /home/nexopia/public_html/skins/ /home/nexopia/public_html_ram/skins;
rsync -Wogt  /home/nexopia/public_html/*.php /home/nexopia/public_html_ram/;
