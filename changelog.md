# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)

## [Unreleased]

## [1.1.0] - 2026-11-03
### Added
- Export structure only for Complianz consent statistics (`cmplz_statistics`)
- Export structure only for the WP Cerber tables holding personal data: `cerber_acl`, `cerber_blocks`, `cerber_lab`, `cerber_lab_ip`, `cerber_qmem` and `cerber_uss` (user sessions with WordPress session tokens). `cerber_lab_net` is matched by `cerber_lab` too
- Export structure only for Simple History (`simple_history`, `simple_history_contexts`)
- Export structure only for BEA Sender (`bea_s_*`)
- `bin/truncate-light-db-tables.sh` helper to truncate the filtered tables in place with the `mysql` client, with a dry-run mode

### Fixed
- `bin/truncate-light-db-tables.sh` now truncates the Simple History tables too, as the export already filtered them

### Changed
- Run both `db export` calls with `--single-transaction --quick --lock-tables=false`, so the export no longer locks tables on the source database
- README: list the filtered tables accurately and state which personal data are still exported

## [1.0.1] - 2025-03-06
### Fixed
- Fall back to `SELECT DATABASE()` when `$wpdb->dbname` is not defined

## [1.0.0] - 2024-06-27
### Added
- `wp light_db export <file>` command: exports every table, with structure only for log, tracking and personal data tables
- `--tables-to-filter` option to add table name substrings to the default list
- Gzip compression of the dump by default, `--no-compress` to keep plain SQL
- Default filters for SearchWP 3.x and 4.x, Redirection, YOP Poll, WP Security Audit Log, Relevanssi, Log HTTP Requests, WP Mail Log, WP Mail Logging, Broken Link Checker, Stream, Audit Trail, WP Cerber, ThirstyAffiliates, GDPR Cookie Consent, FacetWP, Gravity Forms, Formidable Forms, Contact Form 7 (CF7 to Database), WPForms, WP All Export / Import, Cavalcade, Yoast SEO, Matomo, WP Rocket, Action Scheduler and WooCommerce sessions
