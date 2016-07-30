<?php

class WP_CLI_DB_Light_Export_Base extends WP_CLI_Command {
	/**
	 * The tables that are commonly big
	 *
	 **/
	private $tables_to_filter = [
		// Search WP LOG
		'swp_log',

		// Redirect LOG and redirection 404
		'redirection_logs',
		'redirection_404',

		// YOP Logs
		'yop2_poll_logs',

		// WSAL
		'wsal_metadata',
		'wsal_occurrences',
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
	 *
	 * ## EXAMPLES
	 *
	 *     wp light_db export export.sql --tables-to-filter=postmeta,posts
	 *     wp light_db export export.sql
	 *
	 * @subcommand tables db
	 *
	 * @synopsis [<file>...] [--tables-to-filter]
	 */
	function export( $positional_args, $assoc_args = [] ) {
		global $wpdb;
		$database_name = $wpdb->dbname;
		/**
		 * Filename to export, database name by default
		 */
		$file          = sanitize_file_name( empty( $positional_args[0] ) ? $database_name . '.sql' : $positional_args[0] );

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
			$total_size += $this->get_table_size( $database_name, $table_name );
			$total_of_lines += $this->get_row_count( $database_name, $table_name );
		}

		WP_CLI::log( sprintf( "You are saving %d lines and %s of data", $total_of_lines, size_format( $total_size ) ) );
		WP_CLI::log( 'Export the no-data tables' );
		WP_CLI::launch_self( sprintf( 'db export - > %s --no-data=true --tables=%s', $file, implode( ',', $no_data_tables ) ) );

		WP_CLI::log( 'Export the data tables' );
		WP_CLI::launch_self( sprintf( 'db export - >> %s --tables=%s', $file, implode( ',', $table_names ) ) );
		WP_CLI::success( 'Export done' );

	}

	/**
	 * Get all the tables with the assoc_args
	 *
	 * @param $args
	 *
	 * @return array
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
	 * @param $database_name
	 * @param $table_name
	 *
	 * @return mixed
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
	 * @param $database_name
	 * @param $table_name
	 *
	 * @return mixed
	 */
	private function get_row_count( $database_name, $table_name ) {
		global $wpdb;
		$database_name = sanitize_key( $database_name );
		$table_name    = sanitize_key( $table_name );

		return $wpdb->get_var( "SELECT count(*) FROM `{$database_name}`.`{$table_name}`" );
	}
}

WP_CLI::add_command( 'ligth_db', 'WP_CLI_DB_Light_Export' );
