<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

function cv_certificate_verification_form_shortcode_handler( $atts ) {
    // Enqueue script here to ensure it's loaded only when shortcode is used
    wp_enqueue_script('cv-public-js');

    // Localize script with variables
    wp_localize_script('cv-public-js', 'cv_public_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cv_verify_nonce_action'), // This nonce will be part of form data
        'loading_message' => __('Loading...', 'certificate-verification-for-wp'),
        'error_message' => __('An error occurred. Please try again.', 'certificate-verification-for-wp'),
        'details_header' => __('Certificate Details', 'certificate-verification-for-wp'),
        'student_name_label' => __('Student\'s Full Name', 'certificate-verification-for-wp'),
        'father_mother_name_label' => __('Father\'s/Mother\'s Name', 'certificate-verification-for-wp'),
        'course_name_label' => __('Course Name', 'certificate-verification-for-wp'),
        'course_status_label' => __('Course Completion Status', 'certificate-verification-for-wp'),
        'dob_label' => __('Date of Birth', 'certificate-verification-for-wp'),
        'issue_date_label' => __('Certificate Issue Date', 'certificate-verification-for-wp'),
    ));

    $output = '';
    $roll_id_input = isset( $_POST['roll_id'] ) ? sanitize_text_field( wp_unslash( $_POST['roll_id'] ) ) : ''; // Keep for sticky form if JS fails

    ob_start();
    ?>
    <div class="cv-verification-form-wrapper">
        <form method="POST" action="<?php echo esc_url( get_permalink() ); ?>" class="cv-verification-form">
            <?php wp_nonce_field( 'cv_verify_nonce_action', 'cv_verify_nonce_field' ); // Keep nonce in form for AJAX ?>

            <div class="cv-form-field">
                <label for="cv_roll_id"><?php esc_html_e( 'Enter Roll/ID', 'certificate-verification-for-wp' ); ?></label>
                <input type="text" id="cv_roll_id" name="roll_id" value="<?php echo esc_attr( $roll_id_input ); ?>" required />
            </div>

            <div class="cv-form-submit">
                <input type="submit" name="cv_verify_submit" class="cv-button" value="<?php esc_attr_e( 'Verify', 'certificate-verification-for-wp' ); ?>" />
            </div>
        </form>
        <div class="cv-verification-results-ajax-container">
            <?php // Results will be loaded here by AJAX ?>
        </div>
    </div>
    <?php
    $output .= ob_get_clean();
    return $output;
}
add_shortcode( 'certificate_verification_form', 'cv_certificate_verification_form_shortcode_handler' );

/**
 * AJAX handler for certificate verification.
 */
function cv_ajax_verify_certificate_handler() {
    // Verify nonce (sent as part of form data)
    check_ajax_referer( 'cv_verify_nonce_action', 'cv_verify_nonce_field' );

    $roll_id_input = isset( $_POST['roll_id'] ) ? sanitize_text_field( wp_unslash( $_POST['roll_id'] ) ) : '';

    if ( empty( $roll_id_input ) ) {
        wp_send_json_error( array( 'message' => __( 'Please enter a Roll/ID to verify.', 'certificate-verification-for-wp' ) ) );
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'certificates';
    $sql = $wpdb->prepare(
        "SELECT student_name, father_mother_name, course_name, course_status, date_of_birth, issue_date
         FROM {$table_name}
         WHERE roll_id = %s",
        $roll_id_input
    );
    $certificate_data_row = $wpdb->get_row( $sql, ARRAY_A );

    if ( $certificate_data_row ) {
        // Format dates before sending
        if (!empty($certificate_data_row['date_of_birth']) && $certificate_data_row['date_of_birth'] !== '0000-00-00') {
            $certificate_data_row['date_of_birth_formatted'] = date_i18n( get_option( 'date_format' ), strtotime( $certificate_data_row['date_of_birth'] ) );
        } else {
             $certificate_data_row['date_of_birth_formatted'] = ''; // Or handle as needed
        }
        if (!empty($certificate_data_row['issue_date']) && $certificate_data_row['issue_date'] !== '0000-00-00') {
            $certificate_data_row['issue_date_formatted'] = date_i18n( get_option( 'date_format' ), strtotime( $certificate_data_row['issue_date'] ) );
        } else {
             $certificate_data_row['issue_date_formatted'] = '';
        }

        wp_send_json_success( array( 'certificate_data' => $certificate_data_row ) );
    } else {
        wp_send_json_error( array( 'message' => __( 'The result for the inputted ID was not found in our server.', 'certificate-verification-for-wp' ) ) );
    }
    wp_die(); // this is required to terminate immediately and return a proper response
}
add_action( 'wp_ajax_nopriv_cv_verify_certificate', 'cv_ajax_verify_certificate_handler' ); // For non-logged-in users
add_action( 'wp_ajax_cv_verify_certificate', 'cv_ajax_verify_certificate_handler' );    // For logged-in users

?>
