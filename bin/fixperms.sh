#!/bin/sh

OWN=nobody
GOWN=nogroup
DPERM=0777
FPERM=0666

echo "This fixes the permissions for the full directory structure, but takes a LONG time";

DIR=/home/nexopia/public_html/gallery
echo "Starting $DIR";
chown -R $OWN:$GOWN $DIR
chmod $DPERM $DIR
for i in `ls -1 $DIR | sort -n`
do
	echo -n "$i ";
	chmod $DPERM $DIR/$i
	chmod -R $FPERM $DIR/$i/*
done
echo "";

DIR=/home/nexopia/public_html/gallery/thumbs
echo "Starting $DIR";
chown -R $OWN:$GOWN $DIR
chmod $DPERM $DIR
for i in `ls -1 $DIR | sort -n`
do
	echo -n "$i ";
	chmod $DPERM $DIR/$i
	chmod -R $FPERM $DIR/$i/*
done
echo "";

exit;

DIR=/home/nexopia/public_html/uploads
echo "Starting $DIR";
chown -R $OWN:$GOWN $DIR
chmod $DPERM $DIR
for i in `ls -1 $DIR | sort -n`
do
	chmod $DPERM $DIR/$i
	echo -n "$i ";

	for j in `ls -1 $DIR/$i | sort -n`
	do
		chmod $DPERM $DIR/$i/$j
		if [ -n "`ls -1 $DIR/$i/$j/`" ]
		then
			chmod -R $FPERM $DIR/$i/$j/*
		fi
	done
done
echo "";


DIR=/home/nexopia/public_html/users
echo "Starting $DIR";
chown -R $OWN:$GOWN $DIR
chmod $DPERM $DIR
for i in `ls -1 $DIR | sort -n`
do
	echo -n "$i ";
	chmod $DPERM $DIR/$i
	chmod -R $FPERM $DIR/$i/*
done
echo "";

DIR=/home/nexopia/public_html/users/thumbs
echo "Starting $DIR";
chmod $DPERM $DIR
for i in `ls -1 $DIR | sort -n`
do
	echo -n "$i ";
	chmod $DPERM $DIR/$i
	chmod -R $FPERM $DIR/$i/*
done
echo "";
exit;

#slow recursive version

for i in `ls -1 $1 | -n`
do
	#is directory
	if [ -d $1/$i ]
	then
		echo -n "$i ";
		chown $OWN:$GOWN $1/$i;
		chmod $DPERM $1/$i;
			#call self for directory recursively
		$0 $1/$i;
	else
		chown $OWN:$GOWN $1/$i;
		chmod $FPERM $1/$i;
	fi
done


