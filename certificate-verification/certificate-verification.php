<?php
/**
 * Plugin Name:       Certificate Verification for WP
 * Plugin URI:        https://cibdhk.com/verification/
 * Description:       A plugin to verify student certificates via Roll/ID and manage certificate data.
 * Version:           1.0.0
 * Author:            Hasan Rizvee
 * Author URI:        https://rizvee.github.io
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       certificate-verification-for-wp
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define plugin constants
define( 'CERTIFICATE_VERIFICATION_VERSION', '1.0.0' );
define( 'CERTIFICATE_VERIFICATION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CERTIFICATE_VERIFICATION_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include admin menu
require_once CERTIFICATE_VERIFICATION_PLUGIN_DIR . 'admin/cv-admin-menu.php';

// Include public shortcodes
require_once CERTIFICATE_VERIFICATION_PLUGIN_DIR . 'public/cv-shortcodes.php';

/**
 * Load plugin textdomain.
 */
function cv_load_textdomain_init() {
    load_plugin_textdomain(
        'certificate-verification-for-wp',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
}
add_action( 'plugins_loaded', 'cv_load_textdomain_init' );

/**
 * Create the database table upon plugin activation.
 */
function cv_activate_plugin() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'certificates';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        student_name tinytext NOT NULL,
        father_mother_name tinytext NOT NULL,
        roll_id varchar(55) NOT NULL,
        course_name tinytext NOT NULL,
        course_status varchar(55) NOT NULL,
        date_of_birth date DEFAULT '0000-00-00' NOT NULL,
        issue_date date DEFAULT '0000-00-00' NOT NULL,
        certificate_uid varchar(255) DEFAULT '' NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY roll_id (roll_id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'cv_activate_plugin' );

/**
 * Enqueue public scripts and styles.
 */
function cv_enqueue_public_assets() {
    // Register script - will be enqueued by shortcode handler only when needed
    wp_register_script(
        'cv-public-js',
        CERTIFICATE_VERIFICATION_PLUGIN_URL . 'public/js/cv-public.js',
        array('jquery'),
        CERTIFICATE_VERIFICATION_VERSION,
        true
    );

    // Enqueue Public CSS
    wp_enqueue_style(
        'cv-public-css', // Handle
        CERTIFICATE_VERIFICATION_PLUGIN_URL . 'public/css/cv-public.css', // Path to file
        array(), // Dependencies
        CERTIFICATE_VERIFICATION_VERSION // Version
    );
}
add_action( 'wp_enqueue_scripts', 'cv_enqueue_public_assets' );

/**
 * Enqueue admin scripts and styles.
 */
function cv_enqueue_admin_assets($hook_suffix) {
    // Example: Only load on our plugin's admin pages
    // The $hook_suffix for add_menu_page is 'toplevel_page_{menu_slug}'
    // For add_submenu_page it's '{parent_slug}_page_{submenu_slug}' or for first item '{toplevel_page_slug}'
    $plugin_pages = array(
        'toplevel_page_cv-manage-certificates', // Main page
        'certificates_page_cv-add-new-certificate', // Submenu page
        'certificates_page_cv-bulk-import'        // Submenu page
    );

    // A more generic check if the page belongs to "cv-" prefix
    // if (strpos($hook_suffix, 'cv-manage-certificates') === false &&
    //     strpos($hook_suffix, 'cv-add-new-certificate') === false &&
    //     strpos($hook_suffix, 'cv-bulk-import') === false) {
    //    return;
    // }
    // For simplicity, let's use a check that works for all plugin pages based on common slug part
    if ( !in_array($hook_suffix, $plugin_pages) && strpos($hook_suffix, 'cv-') === false ) {
         // A better check would be to store the hook suffixes returned by add_menu_page and add_submenu_page
         // and check against that array. For now, this is a simpler approach.
         // return; // Uncomment to restrict loading
    }


    wp_enqueue_style(
        'cv-admin-css',
        CERTIFICATE_VERIFICATION_PLUGIN_URL . 'admin/css/cv-admin.css',
        array(),
        CERTIFICATE_VERIFICATION_VERSION
    );

    wp_enqueue_script(
        'cv-admin-js',
        CERTIFICATE_VERIFICATION_PLUGIN_URL . 'admin/js/cv-admin.js',
        array('jquery'),
        CERTIFICATE_VERIFICATION_VERSION,
        true // In footer
    );
}
add_action( 'admin_enqueue_scripts', 'cv_enqueue_admin_assets' );

?>
