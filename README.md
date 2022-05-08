# Nexopia Core Site Code Release

## History

This repository contains a simplified history of the nexopia core site codebase,
from its humble origins written entirely by Timo Ewalds to a regional social
network that was more popular than MySpace in western Canada, to its decline 
in the face of Facebook's utter domination of the space.

The points chosen to be represented in this history are intended to represent
inflection points in the code's trajectory. Core elements changed a lot at first, then
more slowly/incrementally as more people were working on it, so the dates on
these commits follow a kind of logarithmic progression.

The major inflection points intended to be represented include (each is represented 
in the repository by a commit):

- The earliest revision of the code available, from a zip file that appears to have
been created in October 2004.
- The first version imported into subversion source control, which represents mostly
the work of Timo Ewalds, with Megan Batty beginning to make more contributions.
- Regularizing how the database was managed, since there were two people working
on it now. We did this by automatically storing the schema in the repository
as we edited it in phpMyAdmin, allowing for us to easily determine what needed
to be migrated when we did a deploy. Some tooling starts to appear here for
managing the server as well.
- The implementation of LiveJournal-style soft sharding, where instead of shuffling
hot tables off their own database, we started moving userdata into a shared virtual
database and then using our SQL query formatter to detect userid, which it would then
pick a database that user 'belonged to'. The sharding was dynamic, with users being
placed in a database based on load levelling. Memcached was introduced here to speed
up repeated lookups for shard information. At this point, there were several engineers
working on the code and a couple of ops people.
- The beginnings of The Great Rewrite. In this commit you can see the beginnings of the
ruby-site experiment, that would eventually become the primary mechanism of the site.
This was extremely ambitious, but we dominated our local market and we thought we could
pull off a "flip the switch" rewrite. At this point there were perhaps a dozen of us
working on this, and there is significantly more code overall.
- Unfortunately, we were wrong. As Facebook's public launch loomed, and our site stagnated,
we realized we needed to make a hybrid and put some new functionality out. This commit
represents the point where we rewired things thanks to a thing called RAP, which
embedded a PHP interpreter into a ruby gem. RAP allowed us to build the site logic in
ruby and gradually replace bits. Eventually this led to the launch of Profiles 2.0,
which were extremely ambitious and not at all well received.
- The final version before this archive was made. There would have been work done after
this, but it's probably gone now.

## Things of Note

There's a lot of interesting ideas in here that are still not well represented in modern web 
frameworks. The modular pagehandler system allowed for easily composing different kinds of 
content into single pages, and the storable quasi-ORM was built to bias you towards fast, 
indexed queries.

At the time we started the ruby rewrite project, Rails was not yet really mature enough for
our needs. For the most part we needed requests to complete in less than 50ms, ideally more
like 10ms. Rails produced far too much garbage collector pressure to produce this kind of
performance regularly.

Some other parts of Nexopia's infrastructure have been released in the 
[same github organization as this one](https://github.com/exitnexus), sometimes with
a more complete history attached.

## Legal Notes

Please Note: This source code is released as-is, and is not intended to be usable
to produce a fully working site like Nexopia. Many assets and supporting code
have been omitted, and much of this code can't be used without the supporting
infrastructure. It is also extremely out of data and likely full of security issues
that we weren't aware of at the time.

It is for historical reference only. However, if code in here is useful and you are
responsible with it, it has been licensed under a permissive license so it can maybe
have some new life elsewhere.

It has been released with the permission of the current managers of Nexopia. If 
any code in this repository claims to be licensed differently than the root Apache2
license, that takes precedence. If you believe your code or assets have been wrongfully
included in this or any other repository in the [exitnexus github organization](https://github.com/exitnexus), 
please email megan@stormbrew.ca and/or source@nexopia.com  and we can discuss it.
