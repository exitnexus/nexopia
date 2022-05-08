#!/bin/sh
/home/nexopia/serverscmd.sh php "rm -f /home/nexopia/cache/*"
/home/nexopia/serverscmd.sh lb  "rm -f /home/nexopia/cache/*"
/home/nexopia/serverscmd.sh mail "rm -f /home/nexopia/cache/*"

