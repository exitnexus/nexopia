#!/bin/sh


for i in `cat /home/nexopia/servers/php`
do
echo -e "\e[36;1m$i:\e[0m ";
echo "stats" | nc -q 1 $i 8435
done

