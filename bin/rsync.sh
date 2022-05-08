#!/bin/sh
rsync -rWogtv --stats --size-only --delete-excluded nexopia.com::public_html /home/nexopia/public_html/
