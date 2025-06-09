<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

require_once CERTIFICATE_VERIFICATION_PLUGIN_DIR . 'admin/class-cv-certificates-list-table.php';

/**
 * Register the admin menu for Certificate Verification.
 */
function cv_register_admin_menu() {
    add_menu_page(
        __( 'Certificates', 'certificate-verification-for-wp' ), // Page title
        __( 'Certificates', 'certificate-verification-for-wp' ), // Menu title
        'manage_options', // Capability
        'cv-manage-certificates', // Menu slug
        'cv_manage_certificates_page_content', // Callback function
        'dashicons-awards', // Icon URL
        30 // Position
    );

    add_submenu_page(
        'cv-manage-certificates', // Parent slug
        __( 'Manage Certificates', 'certificate-verification-for-wp' ), // Page title
        __( 'Manage Certificates', 'certificate-verification-for-wp' ), // Menu title
        'manage_options', // Capability
        'cv-manage-certificates', // Menu slug (same as parent for the main page)
        'cv_manage_certificates_page_content' // Callback function
    );

    add_submenu_page(
        'cv-manage-certificates', // Parent slug
        __( 'Add New Certificate', 'certificate-verification-for-wp' ), // Page title
        __( 'Add New', 'certificate-verification-for-wp' ), // Menu title
        'manage_options', // Capability
        'cv-add-new-certificate', // Menu slug
        'cv_add_new_certificate_page_content' // Callback function
    );

    add_submenu_page(
        'cv-manage-certificates', // Parent slug
        __( 'Bulk Import Certificates', 'certificate-verification-for-wp' ), // Page title
        __( 'Bulk Import', 'certificate-verification-for-wp' ), // Menu title
        'manage_options', // Capability
        'cv-bulk-import', // Menu slug
        'cv_bulk_import_page_content' // Callback function
    );
}
add_action( 'admin_menu', 'cv_register_admin_menu' );

/**
 * Placeholder callback for Manage Certificates page.
 */
function cv_manage_certificates_page_content() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'certificate-verification-for-wp' ) );
    }

    $certificates_table = new CV_Certificates_List_Table();
    $message = ''; // For success/error messages related to actions

    // Handle Delete Action
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_certificate' && isset( $_GET['certificate_id'] ) ) {
        $certificate_id = absint( $_GET['certificate_id'] );
        // Verify nonce
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'cv_delete_certificate_nonce' ) ) {
            $message = '<div id="message" class="error notice is-dismissible"><p>' . __( 'Nonce verification failed. Delete action aborted.', 'certificate-verification-for-wp' ) . '</p></div>';
        } else {
            CV_Certificates_List_Table::delete_certificate( $certificate_id );
            // Using wp_redirect to remove action parameters from URL and show a clean message
            // Add a query arg for the message
            $redirect_url = add_query_arg( array('page' => $_REQUEST['page'], 'message' => 'deleted'), admin_url( 'admin.php' ) );
            wp_redirect($redirect_url);
            exit;
        }
    }

    // Display message after redirect
    if (isset($_GET['message']) && $_GET['message'] == 'deleted') {
        $message = '<div id="message" class="updated notice is-dismissible"><p>' . __( 'Certificate deleted successfully.', 'certificate-verification-for-wp' ) . '</p></div>';
    }
    if (isset($_GET['message']) && $_GET['message'] == 'updated') {
        $message = '<div id="message" class="updated notice is-dismissible"><p>' . __( 'Certificate updated successfully.', 'certificate-verification-for-wp' ) . '</p></div>';
    }
     if (isset($_GET['message']) && $_GET['message'] == 'added') { // From Add New page if we redirect
        $message = '<div id="message" class="updated notice is-dismissible"><p>' . __( 'Certificate added successfully.', 'certificate-verification-for-wp' ) . '</p></div>';
    }


    // Handle Edit Action - Display Edit Form
    // This will be a separate view. If 'edit_certificate' action is set, we show the edit form instead of the table.
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit_certificate' && isset( $_GET['certificate_id'] ) ) {
        // Verify nonce for edit link
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'cv_edit_certificate_nonce' ) ) {
             echo '<div class="wrap"><h1>' . esc_html__( 'Edit Certificate', 'certificate-verification-for-wp' ) . '</h1>';
             echo '<div id="message" class="error notice is-dismissible"><p>' . __( 'Nonce verification failed. Cannot edit certificate.', 'certificate-verification-for-wp' ) . '</p></div></div>';
             return; // Stop further processing for edit form
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'certificates';
        $certificate_id = absint( $_GET['certificate_id'] );
        $certificate = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $certificate_id ), ARRAY_A );

        if ( ! $certificate ) {
            echo '<div class="wrap"><h1>' . esc_html__( 'Edit Certificate', 'certificate-verification-for-wp' ) . '</h1>';
            echo '<div id="message" class="error notice is-dismissible"><p>' . __( 'Certificate not found.', 'certificate-verification-for-wp' ) . '</p></div></div>';
            return;
        }

        // Process update if form submitted
        if ( isset( $_POST['cv_submit_edit_certificate'] ) ) {
            if ( ! isset( $_POST['cv_edit_nonce_field'] ) || ! wp_verify_nonce( $_POST['cv_edit_nonce_field'], 'cv_edit_action_nonce_' . $certificate_id ) ) {
                echo '<div id="message" class="error notice is-dismissible"><p>' . __( 'Nonce verification failed. Update aborted.', 'certificate-verification-for-wp' ) . '</p></div>';
            } else {
                $student_name = sanitize_text_field( wp_unslash( $_POST['student_name'] ) );
                $father_mother_name = sanitize_text_field( wp_unslash( $_POST['father_mother_name'] ) );
                $roll_id = sanitize_text_field( wp_unslash( $_POST['roll_id'] ) );
                $course_name = sanitize_text_field( wp_unslash( $_POST['course_name'] ) );
                $course_status = sanitize_text_field( wp_unslash( $_POST['course_status'] ) );
                $date_of_birth = sanitize_text_field( wp_unslash( $_POST['date_of_birth'] ) );
                $issue_date = sanitize_text_field( wp_unslash( $_POST['issue_date'] ) );
                $certificate_uid = sanitize_text_field( wp_unslash( $_POST['certificate_uid'] ) );

                $errors = array();
                if ( empty( $student_name ) ) $errors[] = __( 'Student Name is required.', 'certificate-verification-for-wp' );
                if ( empty( $roll_id ) ) $errors[] = __( 'Roll/ID is required.', 'certificate-verification-for-wp' );
                // ... (add all other validations similar to add new form)
                $allowed_statuses = array('Completed', 'In Progress', 'Failed');
                if ( !empty($course_status) && !in_array($course_status, $allowed_statuses) ) {
                    $errors[] = __( 'Invalid Course Completion Status.', 'certificate-verification-for-wp' );
                }
                if ( empty( $issue_date ) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $issue_date) ) {
                     $errors[] = __( 'Valid Issue Date is required (YYYY-MM-DD).', 'certificate-verification-for-wp' );
                }
                 if ( !empty($date_of_birth) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_of_birth) ) {
                    $errors[] = __( 'Invalid Date of Birth format (YYYY-MM-DD).', 'certificate-verification-for-wp' );
                }


                if ( !empty($errors) ) {
                    echo '<div id="message" class="error notice is-dismissible"><p>' . implode('<br>', $errors) . '</p></div>';
                    // Display form again with current (potentially erroneous) values
                    $certificate['student_name'] = $student_name; // Update $certificate array to show submitted values
                    $certificate['father_mother_name'] = $father_mother_name;
                    $certificate['roll_id'] = $roll_id;
                    $certificate['course_name'] = $course_name;
                    $certificate['course_status'] = $course_status;
                    $certificate['date_of_birth'] = $date_of_birth;
                    $certificate['issue_date'] = $issue_date;
                    $certificate['certificate_uid'] = $certificate_uid;

                } else {
                    // Check if roll_id already exists for a *different* certificate
                    $existing_roll = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE roll_id = %s AND id != %d", $roll_id, $certificate_id ) );
                    if ( $existing_roll ) {
                         echo '<div id="message" class="error notice is-dismissible"><p>' . __( 'Error: This Roll/ID already exists for another certificate.', 'certificate-verification-for-wp' ) . '</p></div>';
                         // Update $certificate array to show submitted values
                        $certificate['roll_id'] = $roll_id;

                    } else {
                        $wpdb->update(
                            $table_name,
                            array(
                                'student_name' => $student_name,
                                'father_mother_name' => $father_mother_name,
                                'roll_id' => $roll_id,
                                'course_name' => $course_name,
                                'course_status' => $course_status,
                                'date_of_birth' => !empty($date_of_birth) ? $date_of_birth : null,
                                'issue_date' => $issue_date,
                                'certificate_uid' => $certificate_uid,
                                    'updated_at' => current_time( 'mysql', 1 ) // GMT
                            ),
                            array( 'id' => $certificate_id ),
                                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'), // Data formats
                            array('%d') // Where format
                        );
                        $redirect_url = add_query_arg( array('page' => $_REQUEST['page'], 'message' => 'updated'), admin_url( 'admin.php' ) );
                        wp_redirect($redirect_url);
                        exit;
                    }
                }
            }

            // Display Edit Form if we are in edit action and no successful POST occurred to redirect
            ?>
            <div class="wrap">
                <h1><?php echo esc_html__( 'Edit Certificate', 'certificate-verification-for-wp' ); ?></h1>
                <form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=cv-manage-certificates&action=edit_certificate&certificate_id=' . $certificate_id ) ); ?>">
                    <?php wp_nonce_field( 'cv_edit_action_nonce_' . $certificate_id, 'cv_edit_nonce_field' ); ?>
                    <input type="hidden" name="certificate_id" value="<?php echo esc_attr( $certificate_id ); ?>" />
                    <table class="form-table" role="presentation">
                        <tr valign="top">
                            <th scope="row"><label for="student_name"><?php esc_html_e( 'Student\'s Full Name', 'certificate-verification-for-wp' ); ?></label><span style="color:red;">*</span></th>
                            <td><input type="text" id="student_name" name="student_name" value="<?php echo esc_attr( $certificate['student_name'] ); ?>" class="regular-text" required /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="father_mother_name"><?php esc_html_e( 'Father\'s/Mother\'s Name', 'certificate-verification-for-wp' ); ?></label></th>
                            <td><input type="text" id="father_mother_name" name="father_mother_name" value="<?php echo esc_attr( $certificate['father_mother_name'] ); ?>" class="regular-text" /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="roll_id"><?php esc_html_e( 'Student\'s Roll/ID', 'certificate-verification-for-wp' ); ?></label><span style="color:red;">*</span> <em class="description">(Unique)</em></th>
                            <td><input type="text" id="roll_id" name="roll_id" value="<?php echo esc_attr( $certificate['roll_id'] ); ?>" class="regular-text" required /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="course_name"><?php esc_html_e( 'Course Name', 'certificate-verification-for-wp' ); ?></label><span style="color:red;">*</span></th>
                            <td><input type="text" id="course_name" name="course_name" value="<?php echo esc_attr( $certificate['course_name'] ); ?>" class="regular-text" required/></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="course_status"><?php esc_html_e( 'Course Completion Status', 'certificate-verification-for-wp' ); ?></label><span style="color:red;">*</span></th>
                            <td>
                                <select id="course_status" name="course_status" required>
                                    <option value="Completed" <?php selected( $certificate['course_status'], 'Completed' ); ?>><?php esc_html_e( 'Completed', 'certificate-verification-for-wp' ); ?></option>
                                    <option value="In Progress" <?php selected( $certificate['course_status'], 'In Progress' ); ?>><?php esc_html_e( 'In Progress', 'certificate-verification-for-wp' ); ?></option>
                                    <option value="Failed" <?php selected( $certificate['course_status'], 'Failed' ); ?>><?php esc_html_e( 'Failed', 'certificate-verification-for-wp' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="date_of_birth"><?php esc_html_e( 'Date of Birth', 'certificate-verification-for-wp' ); ?></label></th>
                            <td><input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo esc_attr( $certificate['date_of_birth'] === '0000-00-00' ? '' : $certificate['date_of_birth'] ); ?>" class="regular-text" /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="issue_date"><?php esc_html_e( 'Certificate Issue Date', 'certificate-verification-for-wp' ); ?></label><span style="color:red;">*</span></th>
                            <td><input type="date" id="issue_date" name="issue_date" value="<?php echo esc_attr( $certificate['issue_date'] ); ?>" class="regular-text" required /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="certificate_uid"><?php esc_html_e( 'Unique Certificate ID (for QR)', 'certificate-verification-for-wp' ); ?></label></th>
                            <td><input type="text" id="certificate_uid" name="certificate_uid" value="<?php echo esc_attr( $certificate['certificate_uid'] ); ?>" class="regular-text" /></td>
                        </tr>
                    </table>
                    <?php submit_button( __( 'Update Certificate', 'certificate-verification-for-wp' ), 'primary', 'cv_submit_edit_certificate' ); ?>
                     <a href="<?php echo esc_url(admin_url('admin.php?page=cv-manage-certificates')); ?>" class="button secondary"><?php esc_html_e('Cancel', 'certificate-verification-for-wp'); ?></a>
                </form>
            </div>
            <?php
            return; // Do not display the table if we are editing
        }


        // If not editing, display the table
        $certificates_table->prepare_items();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Manage Certificates', 'certificate-verification-for-wp' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=cv-add-new-certificate' ) ); ?>" class="page-title-action">
                    <?php echo esc_html__( 'Add New', 'certificate-verification-for-wp' ); ?>
                </a>
            </h1>

            <?php if (!empty($message)) echo $message; // Display messages from delete/update ?>

            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
                <?php
                $certificates_table->search_box( __( 'Search Certificates', 'certificate-verification-for-wp' ), 'certificate-search-input' );
                $certificates_table->display();
                ?>
            </form>
        </div>
        <?php
    }

/**
 * Placeholder callback for Add New Certificate page.
 */
function cv_add_new_certificate_page_content() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'certificate-verification-for-wp' ) );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'certificates';
    $message = '';
    $message_type = ''; // 'updated' for success, 'error' for failure

    // Initialize variables for form fields to keep them sticky or clear them after success
    $student_name_val = '';
    $father_mother_name_val = '';
    $roll_id_val = '';
    $course_name_val = '';
    $course_status_val = '';
    $date_of_birth_val = '';
    $issue_date_val = '';
    $certificate_uid_val = '';

    // If form was submitted, retain values for sticky form fields
    if ( isset( $_POST['cv_submit_new_certificate'] ) ) {
        $student_name_val = isset( $_POST['student_name'] ) ? sanitize_text_field( wp_unslash( $_POST['student_name'] ) ) : '';
        $father_mother_name_val = isset( $_POST['father_mother_name'] ) ? sanitize_text_field( wp_unslash( $_POST['father_mother_name'] ) ) : '';
        $roll_id_val = isset( $_POST['roll_id'] ) ? sanitize_text_field( wp_unslash( $_POST['roll_id'] ) ) : '';
        $course_name_val = isset( $_POST['course_name'] ) ? sanitize_text_field( wp_unslash( $_POST['course_name'] ) ) : '';
        $course_status_val = isset( $_POST['course_status'] ) ? sanitize_text_field( wp_unslash( $_POST['course_status'] ) ) : '';
        $date_of_birth_val = isset( $_POST['date_of_birth'] ) ? sanitize_text_field( wp_unslash( $_POST['date_of_birth'] ) ) : '';
        $issue_date_val = isset( $_POST['issue_date'] ) ? sanitize_text_field( wp_unslash( $_POST['issue_date'] ) ) : '';
        $certificate_uid_val = isset( $_POST['certificate_uid'] ) ? sanitize_text_field( wp_unslash( $_POST['certificate_uid'] ) ) : '';
    }


    // Handle form submission logic
    if ( isset( $_POST['cv_submit_new_certificate'] ) ) {
        // Verify nonce
        if ( ! isset( $_POST['cv_add_new_nonce_field'] ) || ! wp_verify_nonce( $_POST['cv_add_new_nonce_field'], 'cv_add_new_nonce_action' ) ) {
            $message = __( 'Nonce verification failed. Please try again.', 'certificate-verification-for-wp' );
            $message_type = 'error';
        } else {
            // Data already sanitized above for sticky form, can re-assign for clarity if preferred or use directly

            // Basic validation
            $errors = array();
            if ( empty( $student_name_val ) ) {
                $errors[] = __( 'Student\'s Full Name is required.', 'certificate-verification-for-wp' );
            }
            if ( empty( $roll_id_val ) ) {
                $errors[] = __( 'Student\'s Roll/ID is required.', 'certificate-verification-for-wp' );
            }
            if ( empty( $course_name_val ) ) {
                $errors[] = __( 'Course Name is required.', 'certificate-verification-for-wp' );
            }
            if ( empty( $course_status_val ) ) {
                $errors[] = __( 'Course Completion Status is required.', 'certificate-verification-for-wp' );
            }
             // Allowed course statuses
            $allowed_statuses = array('Completed', 'In Progress', 'Failed');
            if ( !empty($course_status_val) && !in_array($course_status_val, $allowed_statuses) ) {
                $errors[] = __( 'Invalid Course Completion Status.', 'certificate-verification-for-wp' );
            }
            if ( empty( $issue_date_val ) ) {
                $errors[] = __( 'Certificate Issue Date is required.', 'certificate-verification-for-wp' );
            } elseif ( !preg_match('/^\d{4}-\d{2}-\d{2}$/', $issue_date_val) ) {
                $errors[] = __( 'Invalid Certificate Issue Date format. Please use YYYY-MM-DD.', 'certificate-verification-for-wp' );
            }

            if ( !empty($date_of_birth_val) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_of_birth_val) ) {
                $errors[] = __( 'Invalid Date of Birth format. Please use YYYY-MM-DD.', 'certificate-verification-for-wp' );
            }


            if ( !empty($errors) ) {
                $message = implode( '<br>', $errors );
                $message_type = 'error';
            } else {
                // Check if roll_id already exists
                $existing_roll = $wpdb->get_var( $wpdb->prepare( "SELECT roll_id FROM $table_name WHERE roll_id = %s", $roll_id_val ) );
                if ( $existing_roll ) {
                    $message = __( 'Error: This Roll/ID already exists in the database.', 'certificate-verification-for-wp' );
                    $message_type = 'error';
                } else {
                    $data_to_insert = array(
                        'student_name' => $student_name_val,
                        'father_mother_name' => $father_mother_name_val,
                        'roll_id' => $roll_id_val,
                        'course_name' => $course_name_val,
                        'course_status' => $course_status_val,
                        'issue_date' => $issue_date_val,
                        'certificate_uid' => $certificate_uid_val,
                        'date_of_birth' => !empty($date_of_birth_val) ? $date_of_birth_val : null,
                        'created_at' => current_time( 'mysql', 1 ), // GMT
                        'updated_at' => current_time( 'mysql', 1 )  // GMT
                    );

                    $format_to_insert = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');

                    $result = $wpdb->insert(
                        $table_name,
                        $data_to_insert,
                        $format_to_insert
                    );

                    if ( $result === false ) {
                        $message = __( 'Error inserting certificate data: ', 'certificate-verification-for-wp' ) . $wpdb->last_error;
                        $message_type = 'error';
                    } else {
                        $message = __( 'Certificate data added successfully!', 'certificate-verification-for-wp' );
                        $message_type = 'updated';
                        // Clear variables for a fresh form
                        $student_name_val = $father_mother_name_val = $roll_id_val = $course_name_val = $course_status_val = $date_of_birth_val = $issue_date_val = $certificate_uid_val = '';
                    }
                }
            }
        }
    }

    // Display messages
    if ( ! empty( $message ) ) {
        echo '<div id="message" class="' . esc_attr( $message_type ) . ' notice is-dismissible"><p>' . wp_kses_post( $message ) . '</p></div>';
    }

    // Display form
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Add New Certificate', 'certificate-verification-for-wp' ); ?></h1>
        <form method="POST" action="">
            <?php wp_nonce_field( 'cv_add_new_nonce_action', 'cv_add_new_nonce_field' ); ?>
            <table class="form-table" role="presentation">
                <tr valign="top">
                    <th scope="row">
                        <label for="student_name"><?php esc_html_e( 'Student\'s Full Name', 'certificate-verification-for-wp' ); ?></label>
                        <span style="color:red;">*</span>
                    </th>
                    <td><input type="text" id="student_name" name="student_name" value="<?php echo esc_attr($student_name_val); ?>" class="regular-text" required aria-required="true" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="father_mother_name"><?php esc_html_e( 'Father\'s/Mother\'s Name', 'certificate-verification-for-wp' ); ?></label>
                    </th>
                    <td><input type="text" id="father_mother_name" name="father_mother_name" value="<?php echo esc_attr($father_mother_name_val); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="roll_id"><?php esc_html_e( 'Student\'s Roll/ID', 'certificate-verification-for-wp' ); ?></label>
                        <span style="color:red;">*</span> <em class="description">(Unique)</em>
                    </th>
                    <td><input type="text" id="roll_id" name="roll_id" value="<?php echo esc_attr($roll_id_val); ?>" class="regular-text" required aria-required="true" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="course_name"><?php esc_html_e( 'Course Name', 'certificate-verification-for-wp' ); ?></label>
                        <span style="color:red;">*</span>
                    </th>
                    <td><input type="text" id="course_name" name="course_name" value="<?php echo esc_attr($course_name_val); ?>" class="regular-text" required aria-required="true" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="course_status"><?php esc_html_e( 'Course Completion Status', 'certificate-verification-for-wp' ); ?></label>
                        <span style="color:red;">*</span>
                    </th>
                    <td>
                        <select id="course_status" name="course_status" required aria-required="true">
                            <option value="" <?php selected( $course_status_val, '' ); ?>><?php esc_html_e( '-- Select Status --', 'certificate-verification-for-wp' ); ?></option>
                            <option value="Completed" <?php selected( $course_status_val, 'Completed' ); ?>><?php esc_html_e( 'Completed', 'certificate-verification-for-wp' ); ?></option>
                            <option value="In Progress" <?php selected( $course_status_val, 'In Progress' ); ?>><?php esc_html_e( 'In Progress', 'certificate-verification-for-wp' ); ?></option>
                            <option value="Failed" <?php selected( $course_status_val, 'Failed' ); ?>><?php esc_html_e( 'Failed', 'certificate-verification-for-wp' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="date_of_birth"><?php esc_html_e( 'Date of Birth', 'certificate-verification-for-wp' ); ?></label>
                    </th>
                    <td><input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo esc_attr($date_of_birth_val); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Format: YYYY-MM-DD. Optional.', 'certificate-verification-for-wp' ); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="issue_date"><?php esc_html_e( 'Certificate Issue Date', 'certificate-verification-for-wp' ); ?></label>
                        <span style="color:red;">*</span>
                    </th>
                    <td><input type="date" id="issue_date" name="issue_date" value="<?php echo esc_attr($issue_date_val); ?>" class="regular-text" required aria-required="true" />
                         <p class="description"><?php esc_html_e( 'Format: YYYY-MM-DD.', 'certificate-verification-for-wp' ); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="certificate_uid"><?php esc_html_e( 'Unique Certificate ID (for QR)', 'certificate-verification-for-wp' ); ?></label>
                    </th>
                    <td><input type="text" id="certificate_uid" name="certificate_uid" value="<?php echo esc_attr($certificate_uid_val); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <?php submit_button( __( 'Add Certificate', 'certificate-verification-for-wp' ), 'primary', 'cv_submit_new_certificate' ); ?>
        </form>
    </div>
    <?php
}

/**
 * Placeholder callback for Bulk Import Certificates page.
 */
function cv_bulk_import_page_content() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'certificate-verification-for-wp' ) );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'certificates';
    $messages = array(); // To store multiple success/error messages

    // Handle file upload and CSV processing
    if ( isset( $_POST['cv_submit_bulk_import'] ) && isset( $_FILES['certificate_csv_file'] ) ) {
        // Verify nonce
        if ( ! isset( $_POST['cv_bulk_import_nonce_field'] ) || ! wp_verify_nonce( $_POST['cv_bulk_import_nonce_field'], 'cv_bulk_import_nonce_action' ) ) {
            $messages[] = array('type' => 'error', 'message' => __( 'Nonce verification failed. Please try again.', 'certificate-verification-for-wp' ));
        } elseif ( $_FILES['certificate_csv_file']['error'] !== UPLOAD_ERR_OK ) {
            $messages[] = array('type' => 'error', 'message' => __( 'File upload error. Code: ', 'certificate-verification-for-wp' ) . $_FILES['certificate_csv_file']['error']);
        } else {
            $file_tmp_name = $_FILES['certificate_csv_file']['tmp_name'];
            $file_mime_type = mime_content_type( $file_tmp_name );

            if ( $file_mime_type !== 'text/csv' && $file_mime_type !== 'application/csv' && $file_mime_type !== 'text/plain') {
                 $messages[] = array('type' => 'error', 'message' => __( 'Invalid file type. Please upload a CSV file.', 'certificate-verification-for-wp' ));
            } else {
                if ( ( $handle = fopen( $file_tmp_name, 'r' ) ) !== FALSE ) {
                    $header = fgetcsv( $handle, 1000, ',' ); // Get header row
                    // Define expected header columns (case-insensitive for flexibility, but we'll use specific keys for processing)
                    // These keys should match our database/internal logic
                    $expected_headers_map = array(
                        'student_name' => 'student_name',
                        'father_mother_name' => 'father_mother_name',
                        'roll_id' => 'roll_id',
                        'course_name' => 'course_name',
                        'course_status' => 'course_status',
                        'date_of_birth' => 'date_of_birth',
                        'issue_date' => 'issue_date',
                        'certificate_uid' => 'certificate_uid'
                    );
                    // Normalize uploaded header
                    $normalized_header = array_map('strtolower', array_map('trim', $header));
                    $processed_header = array();
                    foreach($normalized_header as $h_col){
                        $processed_header[] = str_replace(' ', '_', $h_col); // e.g. "Student Name" -> "student_name"
                    }


                    // Check if all expected headers are present
                    $missing_headers = array();
                    $found_headers_map = array(); // This will map the actual column index to our expected key

                    foreach ($expected_headers_map as $key => $display_name) {
                        $found = false;
                        foreach($processed_header as $idx => $h_col){
                             // Try direct match or common variations
                            if ($h_col === $key || $h_col === str_replace('_', '', $key) || $h_col === $display_name) {
                                $found_headers_map[$key] = $idx;
                                $found = true;
                                break;
                            }
                        }
                        if(!$found && in_array($key, ['student_name', 'roll_id', 'course_name', 'course_status', 'issue_date'])){ // Required check
                             $missing_headers[] = $display_name;
                        }
                    }


                    if (!empty($missing_headers)) {
                        $messages[] = array('type' => 'error', 'message' => __( 'CSV file is missing the following required columns: ', 'certificate-verification-for-wp' ) . implode(', ', $missing_headers));
                    } else {
                        $row_number = 1; // Start from 1 for header
                        $imported_count = 0;
                        $error_count = 0;
                        $allowed_statuses = array('Completed', 'In Progress', 'Failed');

                        while ( ( $data = fgetcsv( $handle, 1000, ',' ) ) !== FALSE ) {
                            $row_number++;
                            $certificate_data = array();
                            $current_row_errors = array();

                            // Map data using found_headers_map
                            foreach($expected_headers_map as $key => $display_name){
                                $idx = isset($found_headers_map[$key]) ? $found_headers_map[$key] : -1;
                                $certificate_data[$key] = isset($data[$idx]) ? trim($data[$idx]) : '';
                            }

                            // Sanitize data
                            $student_name = sanitize_text_field($certificate_data['student_name']);
                            $father_mother_name = sanitize_text_field($certificate_data['father_mother_name']);
                            $roll_id = sanitize_text_field($certificate_data['roll_id']);
                            $course_name = sanitize_text_field($certificate_data['course_name']);
                            $course_status = sanitize_text_field($certificate_data['course_status']);
                            $date_of_birth = sanitize_text_field($certificate_data['date_of_birth']); // Validate format later
                            $issue_date = sanitize_text_field($certificate_data['issue_date']); // Validate format later
                            $certificate_uid = sanitize_text_field($certificate_data['certificate_uid']);


                            // Validate required fields for this row
                            if ( empty( $student_name ) ) $current_row_errors[] = __( 'Student Name is missing.', 'certificate-verification-for-wp' );
                            if ( empty( $roll_id ) ) $current_row_errors[] = __( 'Roll/ID is missing.', 'certificate-verification-for-wp' );
                            if ( empty( $course_name ) ) $current_row_errors[] = __( 'Course Name is missing.', 'certificate-verification-for-wp' );
                            if ( empty( $course_status ) ) $current_row_errors[] = __( 'Course Status is missing.', 'certificate-verification-for-wp' );
                            if ( empty( $issue_date ) ) $current_row_errors[] = __( 'Issue Date is missing.', 'certificate-verification-for-wp' );

                            // Validate date formats
                            if ( !empty($issue_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $issue_date) ) {
                                 $current_row_errors[] = __( 'Invalid Issue Date format (YYYY-MM-DD).', 'certificate-verification-for-wp' );
                            }
                            if ( !empty($date_of_birth) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_of_birth) ) {
                                 $current_row_errors[] = __( 'Invalid Date of Birth format (YYYY-MM-DD).', 'certificate-verification-for-wp' );
                            } elseif (empty($date_of_birth)) {
                                $date_of_birth = null; // Default if empty
                            }


                            // Validate course status
                            if ( !empty($course_status) && !in_array($course_status, $allowed_statuses) ) {
                                $current_row_errors[] = __( 'Invalid Course Status.', 'certificate-verification-for-wp' );
                            }

                            if ( !empty($current_row_errors) ) {
                                $messages[] = array('type' => 'error', 'message' => sprintf(__( 'Row %d: %s', 'certificate-verification-for-wp' ), $row_number, implode('; ', $current_row_errors)));
                                $error_count++;
                                continue; // Skip to next row
                            }

                            // Check for duplicate roll_id
                            $existing_roll = $wpdb->get_var( $wpdb->prepare( "SELECT roll_id FROM $table_name WHERE roll_id = %s", $roll_id ) );
                            if ( $existing_roll ) {
                                $messages[] = array('type' => 'error', 'message' => sprintf(__( 'Row %d: Roll/ID %s already exists.', 'certificate-verification-for-wp' ), $row_number, esc_html($roll_id)));
                                $error_count++;
                                continue; // Skip to next row
                            }

                            // Insert data
                            $insert_result = $wpdb->insert(
                                $table_name,
                                array(
                                    'student_name' => $student_name,
                                    'father_mother_name' => $father_mother_name,
                                    'roll_id' => $roll_id,
                                    'course_name' => $course_name,
                                    'course_status' => $course_status,
                                    'date_of_birth' => $date_of_birth,
                                    'issue_date' => $issue_date,
                                    'certificate_uid' => $certificate_uid,
                                    'created_at' => current_time( 'mysql', 1 ), // GMT
                                    'updated_at' => current_time( 'mysql', 1 )  // GMT
                                ),
                                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
                            );

                            if ( $insert_result === false ) {
                                $messages[] = array('type' => 'error', 'message' => sprintf(__( 'Row %d: Database error while inserting data for Roll/ID %s.', 'certificate-verification-for-wp' ), $row_number, esc_html($roll_id)) . ' ' . $wpdb->last_error);
                                $error_count++;
                            } else {
                                $imported_count++;
                            }
                        }
                        fclose( $handle );

                        if ($imported_count > 0) {
                             $messages[] = array('type' => 'updated', 'message' => sprintf(__( 'Successfully imported %d certificate(s).', 'certificate-verification-for-wp' ), $imported_count));
                        }
                        if ($error_count > 0) {
                             $messages[] = array('type' => 'error', 'message' => sprintf(__( '%d row(s) could not be imported due to errors.', 'certificate-verification-for-wp' ), $error_count));
                        }
                         if ($imported_count == 0 && $error_count == 0 && $row_number == 1) { // Header was row 1
                            $messages[] = array('type' => 'warning', 'message' => __( 'The uploaded CSV file was empty or contained only a header row.', 'certificate-verification-for-wp' ));
                        }

                    }
                } else {
                    $messages[] = array('type' => 'error', 'message' => __( 'Could not open the uploaded CSV file.', 'certificate-verification-for-wp' ));
                }
            }
        }
    }


    // Display messages
    if ( ! empty( $messages ) ) {
        foreach ($messages as $msg_item) {
            echo '<div id="message" class="notice ' . esc_attr( $msg_item['type'] === 'error' ? 'notice-error' : ($msg_item['type'] === 'warning' ? 'notice-warning' : 'notice-success') ) . ' is-dismissible"><p>' . wp_kses_post( $msg_item['message'] ) . '</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Bulk Import Certificates', 'certificate-verification-for-wp' ); ?></h1>

        <p><?php esc_html_e('Upload a CSV file to import multiple certificate records at once.', 'certificate-verification-for-wp'); ?></p>
        <p><?php esc_html_e('The CSV file must have a header row with the following columns (order does not matter, but names should be recognizable):', 'certificate-verification-for-wp'); ?></p>
        <ul>
            <li><strong>student_name</strong> (Required)</li>
            <li>father_mother_name</li>
            <li><strong>roll_id</strong> (Required, Unique)</li>
            <li><strong>course_name</strong> (Required)</li>
            <li><strong>course_status</strong> (Required - e.g., 'Completed', 'In Progress', 'Failed')</li>
            <li>date_of_birth (Format: YYYY-MM-DD, Optional)</li>
            <li><strong>issue_date</strong> (Required, Format: YYYY-MM-DD)</li>
            <li>certificate_uid (Optional)</li>
        </ul>
        <p>
            <a href="<?php echo esc_url(CERTIFICATE_VERIFICATION_PLUGIN_URL . 'admin/sample-import.csv'); ?>" download="sample-import.csv">
                <?php esc_html_e('Download Sample CSV Template', 'certificate-verification-for-wp'); ?>
            </a>
        </p>

        <form method="POST" action="" enctype="multipart/form-data">
            <?php wp_nonce_field( 'cv_bulk_import_nonce_action', 'cv_bulk_import_nonce_field' ); ?>
            <table class="form-table" role="presentation">
                <tr valign="top">
                    <th scope="row">
                        <label for="certificate_csv_file"><?php esc_html_e( 'Certificate CSV File', 'certificate-verification-for-wp' ); ?></label>
                    </th>
                    <td>
                        <input type="file" id="certificate_csv_file" name="certificate_csv_file" accept=".csv" required />
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Upload and Import', 'certificate-verification-for-wp' ), 'primary', 'cv_submit_bulk_import' ); ?>
        </form>
    </div>
    <?php
}

// Helper function to create the sample CSV file (if it doesn't exist)
// This should be called somewhere appropriate, perhaps on plugin activation or when the admin page is loaded if the file is missing.
// For simplicity, we'll assume it's created manually or as a separate step for now.
// To make this self-contained for the subtask, we'll add a check and creation within the plugin.

// This function should be outside cv_bulk_import_page_content, but within cv-admin-menu.php or a new included file.
// Let's add it to cv-admin-menu.php for now.
// The subtask should be instructed to *add* this function to cv-admin-menu.php if it doesn't exist,
// and ensure the sample CSV is created in the `admin` directory.

// For the subtask, just focus on replacing cv_bulk_import_page_content.
// The sample CSV creation can be a follow-up or assumed to be handled manually for now to simplify the subtask.
// However, the download link needs to point to the correct future location.
// The subtask should *also* create an empty file `certificate-verification/admin/sample-import.csv` with just the headers:
// student_name,father_mother_name,roll_id,course_name,course_status,date_of_birth,issue_date,certificate_uid
?>
