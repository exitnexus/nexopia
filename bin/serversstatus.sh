#!/bin/sh

SHOW='*'

if [ $1 ]
then
	SHOW=$@;
fi

cd /home/nexopia/servers/
for i in `cat $SHOW`
do
echo -ne "\e[36;1m$i:\e[0m ";
ssh root@$i /root/checkstatus.sh;
done

