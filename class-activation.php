<?php
/**
 * For SMS-RFI feature
 * On plugin activation, need to create custom table 'wp_sms_rfi' in wp database
 * This is used to manage SMS #2, which is sent on a time delay
 *
 * Table contains three columns:
 * sms_id       -> (not functionally used)
 * phone_number -> phone number field from submitted form
 * timestamp    -> time that form was submitted
 *
 * @package national-university
 *
 * @todo add conditional check to see if table already exists. dbDelta() does this, but I'd imagine aborting ealier would be better for performance.
 */

namespace NUedu_Forms\Inc;

/**
 * Activation
 */
class Activation {
	/**
	 * Add all actions & filters
	 */
	public function __construct() {
		$this->create_sms_rfi_table();
	}

	/**
	 * Creates custom database table 'wp_sms_rfi'
	 */
	protected function create_sms_rfi_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'sms_rfi';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			sms_id INTEGER NOT NULL AUTO_INCREMENT,
			phone_number TEXT NOT NULL,
			rfi_timestamp DATETIME NOT NULL,
			PRIMARY KEY (sms_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.dbDelta_dbdelta
		dbDelta( $sql );
	}
}