<?php
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

define( 'WP_CLI_LIGHT_DB_EXPORT', __DIR__ );

require_once WP_CLI_LIGHT_DB_EXPORT . '/src/wp-cli-light-db-export-base.php';
require_once WP_CLI_LIGHT_DB_EXPORT . '/src/wp-cli-light-db-export.php';
