# wp-cli-light-db-export
Light DB export for WP-CLI

Allows you to export big databases with all the tables but not all the data.

Sometimes you need the structure and not the data like log tables or tracking tables.

By default it ignores tables :
* swp_log
* redirection_logs
* redirection_404
* yop2_poll_logs
* wsal_metadata
* wsal_occurrences

## Usage
`wp light_db export export.sql --tables-to-filter=postmeta,posts`

With this command will export all the data from your database but no data from all databases postmeta or posts even with the prefixes

## Credits
Based on https://github.com/petenelson/wp-cli-size for the table size and row count
