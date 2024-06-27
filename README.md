# wp-cli-light-db-export

Light DB export for WP-CLI

Allows you to export big databases with all the tables but not all the data.

Sometimes you need the structure and not the data like log tables or tracking tables.

By default, it ignores tables for theses plugins :
* Action Scheduler
* Broken Link Checker
* Cavalcade
* Contact Form 7
* FacetWP
* FormidableForms
* GDPR Cookie Consent
* GravityForms
* Log HTTP requests
* Matomo
* Redirection
* SearchWP 3.x & 4.x
* Stream
* TA Links
* ThirstyAffiliates
* WP All Export
* WP Cerber
* WP Forms
* WP Mail Log
* WP Mail Logging
* WP Rocket
* WP Security Audit Log
* WooCommerce
* Yoast SEO
* Yop Polls

## Installing

Installing this package requires WP-CLI v0.23.0 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with `wp package install BeAPI/wp-cli-light-db-export`
You also can update your package with `wp package update`

## Usage

Export everything without the contents of tables containing heavy data or user data :

`wp light_db export export.sql`

Advanced export without plugins data, AND without postmeta,posts tables also

`wp light_db export export.sql --tables-to-filter=postmeta,posts`

With this command will export all the data from your database but no data from all databases postmeta or posts even with the prefixes

## Credits

Based on https://github.com/petenelson/wp-cli-size for the table size and row count
