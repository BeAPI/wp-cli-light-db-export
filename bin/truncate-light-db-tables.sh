#!/usr/bin/env bash
#
# Truncate tables whose *data* is omitted by `wp light_db export` (structure-only dump).
# Matching rules mirror src/wp-cli-light-db-export.php (extract_no_data_tables / strpos).
#
# Usage:
#   ./bin/truncate-light-db-tables.sh [options] <database> <user> <password>
#
# Options:
#   -n, --dry-run       print TRUNCATE SQL only, do not execute (see also DRY_RUN=1)
#   -h, --help          show usage
#
# Optional environment:
#   MYSQL_HOST          (default: 127.0.0.1)
#   MYSQL_PORT          (default: 3306)
#   TABLES_TO_FILTER    comma-separated extra substrings (same as --tables-to-filter)
#   DRY_RUN=1           same as --dry-run
#
set -euo pipefail

usage() {
	echo "Usage: $0 [-n|--dry-run] <database> <user> <password>" >&2
	echo "       $0 -h | --help" >&2
	echo "Options: -n, --dry-run  Print generated SQL only (no TRUNCATE executed)." >&2
	echo "Environment: MYSQL_HOST, MYSQL_PORT, TABLES_TO_FILTER, DRY_RUN=1" >&2
}

DRY_RUN_CLI=0
while [[ $# -gt 0 ]]; do
	case "$1" in
		-n | --dry-run)
			DRY_RUN_CLI=1
			shift
			;;
		-h | --help)
			usage
			exit 0
			;;
		*)
			break
			;;
	esac
done

if [[ $# -lt 3 ]]; then
	usage
	exit 1
fi

DB_NAME="$1"
MYSQL_USER="$2"
MYSQL_PASSWORD="$3"

MYSQL_HOST="${MYSQL_HOST:-127.0.0.1}"
MYSQL_PORT="${MYSQL_PORT:-3306}"

# Default filters: keep in sync with private $tables_to_filter in wp-cli-light-db-export.php
DEFAULT_FILTERS=(
	swp_log swp_index swp_terms
	searchwp_index searchwp_log
	redirection_logs redirection_404
	yop2_poll_logs
	wsal_metadata wsal_occurrences
	relevanssi_log
	lhr_log
	wml_entries
	wpml_mails
	blc_linkdata blc_postdata blc_instances blc_links blc_synch blc_filters
	stream stream_meta
	audit_trail
	cerber_traffic cerber_log _cerber_files
	ta_link_clicks_meta ta_link_clicks
	cli_visitor_details cli_cookie_scan_url
	facetwp_cache facetwp_index
	gf_entry gf_entry_meta gf_entry_notes gf_form_view
	frm_items frm_item_metas
	cf7dbplugin_submits
	wpforms_entries wpforms_entry_fields
	pmxe_exports pmxe_google_cat pmxe_posts pmxe_templates
	pmxi_files pmxi_history pmxi_images pmxi_imports pmxi_posts pmxi_templates
	cavalcade_jobs cavalcade_logs
	ta_link_clicks ta_link_clicks_meta
	yoast_seo_links yoast_seo_meta
	matomo_access matomo_archive_invalidations matomo_brute_force_log matomo_changes
	matomo_custom_dimensions matomo_goal matomo_locks matomo_log_action matomo_log_conversion
	matomo_log_conversion_item matomo_log_link_visit_action matomo_log_profiling matomo_log_visit
	matomo_logger_message matomo_option matomo_plugin_setting matomo_privacy_logdata_anonymizations
	matomo_report matomo_report_subscriptions matomo_segment matomo_sequence matomo_session
	matomo_site matomo_site_setting matomo_site_url matomo_tracking_failure matomo_twofactor_recovery_code
	matomo_user matomo_user_dashboard matomo_user_language matomo_user_token_auth
	matomo_tagmanager_container matomo_tagmanager_container_release matomo_tagmanager_container_version
	matomo_tagmanager_tag matomo_tagmanager_trigger matomo_tagmanager_variable
	matomo_archive_
	wpr_rocket_cache
	actionscheduler_logs
	woocommerce_sessions
	bea_s_
)

FILTERS=( "${DEFAULT_FILTERS[@]}" )
if [[ -n "${TABLES_TO_FILTER:-}" ]]; then
	IFS=',' read -r -a EXTRA <<< "${TABLES_TO_FILTER}"
	for p in "${EXTRA[@]}"; do
		[[ -z "${p// /}" ]] && continue
		FILTERS+=( "$(echo "$p" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')" )
	done
fi

matches_filter() {
	local table="$1"
	local f
	for f in "${FILTERS[@]}"; do
		[[ -z "$f" ]] && continue
		if [[ "$table" == *"$f"* ]]; then
			return 0
		fi
	done
	return 1
}

mysql_exec() {
	mysql --protocol=tcp -h"$MYSQL_HOST" -P"$MYSQL_PORT" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" \
		-N -B --default-character-set=utf8mb4 "$@"
}

if ! mysql_exec -e "SELECT 1" >/dev/null 2>&1; then
	echo "Error: cannot connect to MySQL (host=$MYSQL_HOST port=$MYSQL_PORT user=$MYSQL_USER)." >&2
	exit 1
fi

# List base tables (same scope as WP-CLI wp_get_table_names with all-tables).
ALL_TABLES=()
while IFS= read -r line; do
	[[ -n "$line" ]] || continue
	ALL_TABLES+=( "$line" )
done < <(mysql_exec "$DB_NAME" -e "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='${DB_NAME//\'/\'\'}' AND TABLE_TYPE='BASE TABLE'")

TO_TRUNCATE=()
for t in "${ALL_TABLES[@]}"; do
	if matches_filter "$t"; then
		TO_TRUNCATE+=( "$t" )
	fi
done

if [[ ${#TO_TRUNCATE[@]} -eq 0 ]]; then
	echo "No tables matched the light-export filter list in database '$DB_NAME'."
	exit 0
fi

echo "Matched ${#TO_TRUNCATE[@]} table(s) to truncate (light-export / no-data tables)."

quote_ident() {
	local s="$1"
	s="${s//\`/``}"
	printf '`%s`' "$s"
}

build_sql() {
	echo "SET FOREIGN_KEY_CHECKS = 0;"
	local tbl q
	for tbl in "${TO_TRUNCATE[@]}"; do
		q="$(quote_ident "$tbl")"
		echo "TRUNCATE TABLE ${q};"
	done
	echo "SET FOREIGN_KEY_CHECKS = 1;"
}

SQL="$(build_sql)"

if [[ "${DRY_RUN:-}" == "1" || "$DRY_RUN_CLI" -eq 1 ]]; then
	echo "$SQL"
	exit 0
fi

echo "$SQL" | mysql_exec "$DB_NAME"
echo "Done."
