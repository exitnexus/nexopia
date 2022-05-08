#!/bin/sh

#if [ ! -f "/home/nexopia/servers/$1"  ]
#then
#	if [ "$1" != "*" ]
#	then
#		echo "must specify server class";
#		exit;
#	fi
#fi

#DELAY=0;

for i in `cat /home/nexopia/servers/php`
do
echo -e "\e[36;1m$i:\e[0m ";
ssh root@$i "
kill \`ps auxf | grep php | grep -v grep | grep -v "\\_" | awk '{print $2}'\`
until /etc/init.d/php start; do sleep 10; done;
";

#sleep $DELAY;
done

