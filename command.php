<?php
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}
define( 'WP_CLI_LIGHT_DB_EXPORT', dirname( __FILE__ ) );

require_once WP_CLI_LIGHT_DB_EXPORT . '/src/class-wp-cli-size-base-command.php';
require_once WP_CLI_LIGHT_DB_EXPORT . '/src/class-wp-cli-size-command.php';