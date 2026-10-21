# wp-cli-light-db-export

Light DB export for WP-CLI

Allows you to export big databases with all the tables but not all the data.

Sometimes you need the structure and not the data like log tables or tracking tables.

By default, it exports the structure but not the rows of these tables (matched as a substring of the full table name, whatever the prefix):
* Action Scheduler: logs only (`actionscheduler_logs`)
* Audit Trail
* Broken Link Checker
* BEA Sender
* Cavalcade
* Contact Form 7: CF7 to Database entries (`cf7dbplugin_submits`)
* FacetWP: cache and index
* FormidableForms: entries
* GDPR Cookie Consent: visitor details and scans
* GravityForms: entries and form views (`gf_entry*`, `gf_form_view`)
* Log HTTP requests
* Matomo
* Redirection: logs and 404s
* Relevanssi: logs
* SearchWP 3.x & 4.x: logs and index
* Simple History
* Stream
* TA Links / ThirstyAffiliates: link clicks
* WP All Export / Import
* WP Cerber: traffic and logs
* WP Forms: entries
* WP Mail Log
* WP Mail Logging
* WP Rocket: cache table
* WP Security Audit Log
* WooCommerce: sessions only (`woocommerce_sessions`)
* Yoast SEO: links and meta (`yoast_seo_links`, `yoast_seo_meta`)
* Yop Polls: logs

The full and authoritative list is `$tables_to_filter` in [`src/wp-cli-light-db-export.php`](src/wp-cli-light-db-export.php).

### Not filtered on purpose

Some tables hold personal data but are kept with their rows, because the site does not work without them:

* WooCommerce orders and customers: HPOS tables (`wc_orders`, `wc_order_addresses`, `wc_orders_meta`, `wc_customer_lookup`) and legacy orders in `posts` / `postmeta`
* Action Scheduler jobs (`actionscheduler_actions`), whose arguments may contain e-mails or order IDs
* `users`, `usermeta`, `comments`, `commentmeta`, and multisite `signups`
* Form entries stored as posts (Flamingo, Ninja Forms) and Gravity Forms tables outside `gf_entry*` (`gf_draft_submissions`)

A light export is therefore **not an anonymized export**. When the dump leaves production, anonymize these data after import, or add the tables you do not need with `--tables-to-filter`.

## Installing

Installing this package requires WP-CLI v0.23.0 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with `wp package install BeAPI/wp-cli-light-db-export`
You also can update your package with `wp package update`

## Usage

Export everything without the contents of tables containing heavy data or user data :

`wp light_db export export.sql`

The dump is compressed with gzip by default and written to `export.sql.gz`. Use `--no-compress` to keep a plain `export.sql`.

Advanced export without plugins data, AND without postmeta,posts tables also

`wp light_db export export.sql --tables-to-filter=postmeta,posts`

With this command will export all the data from your database but no data from all databases postmeta or posts even with the prefixes

`--tables-to-filter` adds to the default list, it does not replace it. Each value matches every table whose name contains it: `posts` also matches `wp_2_posts` or `pmxi_posts`.

### Truncate filtered tables (bash + `mysql` client)

A separate helper script truncates—in place on the server—the tables that a light export would dump **without** row data, using only the `mysql` binary (no WP-CLI). Documentation: **[bin/README.md](bin/README.md)**.

## Credits

Based on https://github.com/petenelson/wp-cli-size for the table size and row count
