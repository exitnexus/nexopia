# Create the tables.

# This table serves to provide a unique identifier to
# a metric by usertype.  See below for examples.
$site.dbs[:masterdb].query("CREATE TABLE IF NOT EXISTS `metriclookup` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `categoryid` INT(11) NOT NULL,
  `metric` INT(11) NOT NULL,
  `usertype` ENUM('na', 'all', 'plus', 'active') NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1")

# - `metricid` is an identifier for a particular metric, from
# the metriclookup table.
# For example, the 'number of users' is one metric, the 'number of
# users by gender' is another.
# - `col` identifies which column we are looking at.  For some
# metrics, this is simply an integer which is mapped by the code
# to some meaning.  For example, gender, where '0' may map to 'male'
# and '1' to 'female'.  For other metrics, this is a foreign key to
# another table.  For example, it may store which location we are
# looking at.
# - `date` represents the date for this particular datum.
# - `value` represents the datum itself.  The total number of users
# on this date, the number of male users, whatever.
$site.dbs[:masterdb].query("CREATE TABLE IF NOT EXISTS `metricdata` (
  `metricid` INT(11) NOT NULL,
  `col` INT(11) NOT NULL,
  `date` INT(11) NOT NULL,
  `value` INT(11) NOT NULL,
  PRIMARY KEY (`metricid`, `col`, `date`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1")


# Examples:

# Number of users (all users):
#   metriclookup:
#     (id = 1, categoryid = 1, metric = 1, usertype = 'all')
#   metricdata:
#     metricid => 1 (metriclookup id)
#     col => 0 (only one column)
#     date => whatever
#     value => number of users

# Number of users (plus users):
#   metriclookup:
#     (id = 2, categoryid = 1, metric = 1, usertype = 'plus')
#   metricdata:
#     metricid => 2 (metriclookup id)
#     col => 0 (only one column)
#     date => whatever
#     value => number of users

# Number of users by gender:
#   metriclookup:
#     (id = 3, categoryid = 1, metric = 2, usertype = 'all')
#   metricdata:
#     metricid => 3
#     col => 0 (specified in code as 'male')
#     date => whatever
#     value => number of users who are male
# And then a second row with column => 1, specified in code as 'female'

# Number of users by location:
#   metriclookup:
#     (id = 4, categoryid = 1, metric = 3, usertype = 'all')
#   metricdata:
#     metricid => 4
#     col => used as location_id, so, say, 1 for Canada
#     date => whatever
#     value => number of users who live in Canada
# There would be one row for each row in the locations table, with the
# column changing.
