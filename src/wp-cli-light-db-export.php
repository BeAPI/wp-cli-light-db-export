<?php

class WP_CLI_DB_Light_Export extends WP_CLI_DB_Light_Export_Base {
	/**
	 * The tables that are commonly big
	 *
	 **/
	private $tables_to_filter = [
		// SearchWP 3.x
		'swp_log',
		'swp_index',
		'swp_terms',

		// SearchWP 4.x
		'searchwp_index',
		'searchwp_log',

		// Redirect LOG and redirection 404
		'redirection_logs',
		'redirection_404',

		// YOP Logs
		'yop2_poll_logs',

		// WSAL
		'wsal_metadata',
		'wsal_occurrences',

		// Relevanssi LOG
		'relevanssi_log',

		// Log HTTP requests
		'lhr_log',

		// WP mail log
		'wml_entries',

		// WP Mail Logging
		'wpml_mails',

		// Broken Link Checkers
		'blc_linkdata',
		'blc_postdata',
		'blc_instances',
		'blc_links',
		'blc_synch',
		'blc_filters',

		// Stream
		'stream',
		'stream_meta',

		// Audit Trail
		'audit_trail',

		// WPcerber
		'cerber_traffic',
		'cerber_log',

		// ThirstyAffiliates
		'ta_link_clicks_meta',
		'ta_link_clicks',

		// GDPR Cookie Consent
		'cli_visitor_details',

		// FacetWP
		'facetwp_cache',
		'facetwp_index',

		// Gravityforms
		'gf_entry',
		'gf_entry_meta',
		'gf_entry_notes',
		'gf_form_view',

		// WP All Export / Import
		'pmxe_exports',
		'pmxe_google_cat',
		'pmxe_posts',
		'pmxe_templates',
		'pmxi_files',
		'pmxi_history',
		'pmxi_images',
		'pmxi_imports',
		'pmxi_posts',
		'pmxi_templates',

		// Cavalcade
		'cavalcade_jobs',
		'cavalcade_logs',

		// TA links
		'ta_link_clicks',
		'ta_link_clicks_meta',

		// Yoast
		'yoast_seo_links',
		'yoast_seo_meta',

		// Matomo
		'matomo_access',
		'matomo_archive_invalidations',
		'matomo_brute_force_log',
		'matomo_changes',
		'matomo_custom_dimensions',
		'matomo_goal',
		'matomo_locks',
		'matomo_log_action',
		'matomo_log_conversion',
		'matomo_log_conversion_item',
		'matomo_log_link_visit_action',
		'matomo_log_profiling',
		'matomo_log_visit',
		'matomo_logger_message',
		'matomo_option',
		'matomo_plugin_setting',
		'matomo_privacy_logdata_anonymizations',
		'matomo_report',
		'matomo_report_subscriptions',
		'matomo_segment',
		'matomo_sequence',
		'matomo_session',
		'matomo_site',
		'matomo_site_setting',
		'matomo_site_url',
		'matomo_tracking_failure',
		'matomo_twofactor_recovery_code',
		'matomo_user',
		'matomo_user_dashboard',
		'matomo_user_language',
		'matomo_user_token_auth',
		'matomo_tagmanager_container',
		'matomo_tagmanager_container_release',
		'matomo_tagmanager_container_version',
		'matomo_tagmanager_tag',
		'matomo_tagmanager_trigger',
		'matomo_tagmanager_variable',
		'matomo_archive_'
	];

	/**
	 * Export the database without data from specified tables
	 *
	 * ## OPTIONS
	 *
	 * [<file>]
	 * : List of table names, defaults to all tables in the current site
	 *
	 * [--tables-to-filter]
	 * : List of table names to export without data separated with commas, defaults swp_log,redirection_logs,redirection_404,yop2_poll_logs,wsal_metadata,wsal_occurrences
	 *
	 * [--no-compress]
	 * : Allow to export without GZIP compress.gz
	 *
	 * ## EXAMPLES
	 *
	 *     wp light_db export export.sql --tables-to-filter=postmeta,posts --no-compress
	 *     wp light_db export export.sql.gz
	 *
	 * @synopsis [<file>...] [--tables-to-filter] [--no-compress]
	 */
	public function export( $positional_args, $assoc_args = [] ) {
		global $wpdb;

		$database_name = $wpdb->dbname;

		/**
		 * Filename to export (required)
		 *
		 */
		$file = $positional_args[0];

		/**
		 * Get the list of tables with no-data
		 */
		$this->tables_to_filter = $this->get_tables_to_filter( $assoc_args );

		/**
		 * Get all the tables form the database
		 */
		$table_names = WP_CLI\Utils\wp_get_table_names( array(), array( 'all-tables' => true ) );

		/**
		 * Get the tables with no-data and normal tables
		 */
		$no_data_tables = array_filter( $table_names, array( $this, 'extract_no_data_tables' ) );
		$table_names    = array_diff( $table_names, $no_data_tables );

		/**
		 * Init vars for stats
		 */
		$total_size     = 0;
		$total_of_lines = 0;

		foreach ( $no_data_tables as $table_name ) {
			$total_size     += $this->get_table_size( $database_name, $table_name );
			$total_of_lines += $this->get_row_count( $database_name, $table_name );
		}

		$additional_params = '--all-tablespaces --single-transaction --quick --lock-tables=false';

		WP_CLI::log( sprintf( "You are saving %d lines and %s of data", $total_of_lines, size_format( $total_size ) ) );
		WP_CLI::log( sprintf( 'Export the %d no-data tables', count( $no_data_tables ) ) );
		WP_CLI::runcommand( sprintf( 'db export - > %s --no-data=true --tables=%s %s', $file, implode( ',', $no_data_tables ), $additional_params ) );

		WP_CLI::log( sprintf( 'Export the %d data tables', count( $table_names ) ) );
		WP_CLI::runcommand( sprintf( 'db export - >> %s --tables=%s %s', $file, implode( ',', $table_names ), $additional_params ) );

		if ( ! isset( $assoc_args['no-compress'] ) ) {
			WP_CLI::launch( "gzip --force -9 $file", false, true );

			$file .= '.gz';
		}

		WP_CLI::success( sprintf( "Exported to '%s'", $file ) );
	}

	/**
	 * Get all the tables with the assoc_args
	 *
	 * @param $args
	 *
	 * @return array
	 *
	 */
	private function get_tables_to_filter( $args ) {
		if ( isset( $args['tables-to-filter'] ) ) {
			$this->tables_to_filter = array_merge( $this->tables_to_filter, explode( ',', $args['tables-to-filter'] ) );
		}

		return $this->tables_to_filter;
	}

	/**
	 * Extract the tables with no-data from the array
	 *
	 * @param $table
	 *
	 * @return bool
	 *
	 */
	private function extract_no_data_tables( $table ) {
		foreach ( $this->tables_to_filter as $filter ) {
			if ( false !== strpos( $table, $filter ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the table size
	 *
	 * @param $table_name
	 *
	 * @param $database_name
	 *
	 * @return mixed
	 *
	 */
	private function get_table_size( $database_name, $table_name ) {
		global $wpdb;
		$size = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(data_length + index_length) FROM information_schema.TABLES where table_schema = '%s' and Table_Name = '%s' GROUP BY Table_Name LIMIT 1",
			$database_name,
			$table_name
		)
		);

		return $size;
	}

	/**
	 * Get the row count for the table
	 *
	 * @param $table_name
	 *
	 * @param $database_name
	 *
	 * @return mixed
	 *
	 */
	private function get_row_count( $database_name, $table_name ) {
		global $wpdb;
		$database_name = sanitize_key( $database_name );
		$table_name    = sanitize_key( $table_name );

		return $wpdb->get_var( "SELECT count(*) FROM `{$database_name}`.`{$table_name}`" );
	}
}

WP_CLI::add_command( 'light_db', 'WP_CLI_DB_Light_Export' );
