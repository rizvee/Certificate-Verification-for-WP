<?php
/**
 * Uninstall Certificate Verification for WP
 *
 * Deletes plugin data, specifically the custom database table for certificates.
 *
 * @package CertificateVerificationWP
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'certificates';

// Drop the custom database table.
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

// Future: Delete any options saved via register_setting() if any were added.
// delete_option('cv_some_option');
// delete_site_option('cv_some_multisite_option'); // For multisite
?>
