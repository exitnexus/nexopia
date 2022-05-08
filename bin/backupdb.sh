#!/bin/sh

MYSQL=/usr/local/mysql/bin/mysql
PASSWORD='pRlUvi$t'
DIR=/data
THISHOST=`hostname`
DATE=`date +%Y.%m.%d`
DAY=`date +%a`
FILES="master.info relay-log.info nex*"

BACKUPDIR=/backups/daily/$DAY/dbs/$THISHOST/
WEEKLYDIR=/backups/weekly/$DATE/dbs/$THISHOST/


echo "STOP SLAVE SQL_THREAD;" | $MYSQL -uroot --password=$PASSWORD

rm -rf $BACKUPDIR*
mkdir -p $BACKUPDIR
#cp -r $DIR $BACKUPDIR

for i in $FILES
do
	cp -r $DIR/$i $BACKUPDIR
done

if [ `date +%w` == '0' ]
then
	mkdir -p $WEEKLYDIR
	cp -al $BACKUPDIR* $WEEKLYDIR
fi


echo "START SLAVE SQL_THREAD;" | $MYSQL -uroot --password=$PASSWORD

