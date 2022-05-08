#!/bin/sh

if [ ! -f "/home/nexopia/servers/$1"  ]
then
	if [ "$1" != "*" ]
	then
		echo "must specify server class";
		exit;
	fi
fi

DELAY=10;

for i in `cat /home/nexopia/servers/$1`
do
echo -e "\e[36;1m$i:\e[0m ";
ssh root@$i "reboot";
sleep $DELAY;
done

