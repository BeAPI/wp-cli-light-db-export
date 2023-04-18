# wp-cli-light-db-export

Light DB export for WP-CLI

Allows you to export big databases with all the tables but not all the data.

Sometimes you need the structure and not the data like log tables or tracking tables.

By default, it ignores tables for theses plugins :
* Broken Link Checker
* Redirection
* SearchWP 3.x & 4.x
* Stream
* Log HTTP requests
* WP Security Audit Log
* WP Mail Log
* WP Mail Logging
* WPcerber
* Yop Polls
* ThirstyAffiliates
* GDPR Cookie Consent

## Installing

Installing this package requires WP-CLI v0.23.0 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with `wp package install BeAPI/wp-cli-light-db-export`

## Usage

Export all without plugins data tables :

`wp light_db export export.sql`

Advanced export without plugins data, and without postmeta,posts tables also

`wp light_db export export.sql --tables-to-filter=postmeta,posts`

With this command will export all the data from your database but no data from all databases postmeta or posts even with the prefixes

## Credits

Based on https://github.com/petenelson/wp-cli-size for the table size and row count
