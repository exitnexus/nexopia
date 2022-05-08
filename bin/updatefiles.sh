#!/bin/sh

FILE=/home/nexopia/commands.`date +%s.%N`.sh

wget -O $FILE http://www.nexopia.com/getfiles.php
chmod 744 $FILE
$FILE
rm -f $FILE
