#!/bin/sh

#rsync -rW --stats --size-only 216.194.67.199::mod /home/nexopia/public_html/mod
rsync -rW --stats --size-only 216.194.67.199::banners /home/nexopia/public_html/banners
rsync -rW --stats --size-only 216.194.67.199::skins /home/nexopia/public_html/skins
rsync -rW --stats --size-only 216.194.67.199::images /home/nexopia/public_html/images
#rsync -rW --stats --size-only 216.194.67.199::users /home/nexopia/public_html/users
#rsync -rW --stats --size-only 216.194.67.199::gallery /home/nexopia/public_html/gallery

