#!/bin/sh

if [ ! -f "/home/nexopia/servers/$1"  ]
then
	if [ "$1" != "*" ]
	then
		echo "must specify server class";
		exit;
	fi
fi

for i in `cat /home/nexopia/servers/$1`
do
echo -e "\e[36;1m$i:\e[0m ";
ssh root@$i "$2";
done

