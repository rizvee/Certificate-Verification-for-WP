<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'CV_Certificates_List_Table' ) ) {
	class CV_Certificates_List_Table extends WP_List_Table {

		public function __construct() {
        parent::__construct( array(
            'singular' => __( 'Certificate', 'certificate-verification-for-wp' ), // singular name of the listed records
            'plural'   => __( 'Certificates', 'certificate-verification-for-wp' ), // plural name of the listed records
            'ajax'     => false // should this table support ajax?
        ) );
    }

    public static function get_certificates( $per_page = 20, $page_number = 1, $search_term = '', $filter_course = '', $filter_status = '' ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'certificates';
        $sql = "SELECT * FROM {$table_name}";
        $where_clauses = array();

        if ( ! empty( $search_term ) ) {
            $where_clauses[] = $wpdb->prepare( "(student_name LIKE %s OR roll_id LIKE %s OR course_name LIKE %s)", "%{$wpdb->esc_like($search_term)}%", "%{$wpdb->esc_like($search_term)}%", "%{$wpdb->esc_like($search_term)}%" );
        }
        if ( ! empty( $filter_course ) ) {
            $where_clauses[] = $wpdb->prepare( "course_name = %s", $filter_course );
        }
        if ( ! empty( $filter_status ) ) {
            $where_clauses[] = $wpdb->prepare( "course_status = %s", $filter_status );
        }

        if ( count( $where_clauses ) > 0 ) {
            $sql .= " WHERE " . implode( ' AND ', $where_clauses );
        }

        $sql .= " ORDER BY student_name ASC"; // Default order
        $sql .= $wpdb->prepare( " LIMIT %d OFFSET %d", $per_page, ( $page_number - 1 ) * $per_page );

        $result = $wpdb->get_results( $sql, 'ARRAY_A' );
        return $result;
    }

    public static function delete_certificate( $id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'certificates';
        $wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );
    }

    public static function record_count( $search_term = '', $filter_course = '', $filter_status = '' ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'certificates';
        $sql = "SELECT COUNT(*) FROM {$table_name}";
        $where_clauses = array();

        if ( ! empty( $search_term ) ) {
            $where_clauses[] = $wpdb->prepare( "(student_name LIKE %s OR roll_id LIKE %s OR course_name LIKE %s)", "%{$wpdb->esc_like($search_term)}%", "%{$wpdb->esc_like($search_term)}%", "%{$wpdb->esc_like($search_term)}%" );
        }
        if ( ! empty( $filter_course ) ) {
            $where_clauses[] = $wpdb->prepare( "course_name = %s", $filter_course );
        }
        if ( ! empty( $filter_status ) ) {
            $where_clauses[] = $wpdb->prepare( "course_status = %s", $filter_status );
        }

        if ( count( $where_clauses ) > 0 ) {
            $sql .= " WHERE " . implode( ' AND ', $where_clauses );
        }
        return $wpdb->get_var( $sql );
    }

    public function no_items() {
        _e( 'No certificates found.', 'certificate-verification-for-wp' );
    }

    function column_student_name( $item ) {
        $delete_nonce = wp_create_nonce( 'cv_delete_certificate_nonce' );
        $edit_nonce = wp_create_nonce( 'cv_edit_certificate_nonce' ); // Will be used when edit form is integrated

        $title = '<strong>' . esc_html( $item['student_name'] ) . '</strong>';

        $actions = array(
                'edit'   => sprintf( '<a href="?page=%s&action=%s&certificate_id=%s&_wpnonce=%s">%s</a>', esc_attr( $_REQUEST['page'] ), 'edit_certificate', absint( $item['id'] ), $edit_nonce, __( 'Edit', 'certificate-verification-for-wp' ) ),
                'delete' => sprintf( '<a href="?page=%s&action=%s&certificate_id=%s&_wpnonce=%s" onclick="return confirm(\'%s\')">%s</a>', esc_attr( $_REQUEST['page'] ), 'delete_certificate', absint( $item['id'] ), $delete_nonce, esc_js(__('Are you sure you want to delete this certificate? This action cannot be undone.', 'certificate-verification-for-wp')), __( 'Delete', 'certificate-verification-for-wp' ) )
        );

        return $title . $this->row_actions( $actions );
    }

    function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'roll_id':
            case 'father_mother_name':
            case 'course_name':
            case 'course_status':
            case 'date_of_birth':
            case 'issue_date':
            case 'certificate_uid':
                return esc_html( $item[ $column_name ] );
            default:
                return print_r( $item, true ); //Show the whole array for troubleshooting
        }
    }

    function get_columns() {
        $columns = array(
            // 'cb'        => '<input type="checkbox" />', // For bulk actions, implement later if needed
                'student_name'    => __( 'Student Name', 'certificate-verification-for-wp' ),
                'roll_id'         => __( 'Roll/ID', 'certificate-verification-for-wp' ),
                'course_name'     => __( 'Course Name', 'certificate-verification-for-wp' ),
                'course_status'   => __( 'Status', 'certificate-verification-for-wp' ),
                'issue_date'      => __( 'Issue Date', 'certificate-verification-for-wp' ),
                // 'father_mother_name' => __( 'Father/Mother Name', 'certificate-verification-for-wp' ), // Optionally hide some default columns
                // 'date_of_birth'   => __( 'Date of Birth', 'certificate-verification-for-wp' ),
                // 'certificate_uid' => __( 'Certificate UID', 'certificate-verification-for-wp' ),
                'created_at'      => __( 'Created At', 'certificate-verification-for-wp'),
                'updated_at'      => __( 'Updated At', 'certificate-verification-for-wp')
        );
        return $columns;
    }

    public function get_sortable_columns() {
        // For now, let's make student_name sortable.
        // Proper sorting would require modifying the get_certificates query.
        // This is a placeholder; full server-side sorting is more involved.
            // Adding created_at and updated_at to sortable columns
        $sortable_columns = array(
            'student_name' => array( 'student_name', false ),
            'roll_id' => array( 'roll_id', false ),
            'course_name' => array( 'course_name', false ),
                'issue_date' => array( 'issue_date', false ),
                'created_at' => array('created_at', false),
                'updated_at' => array('updated_at', false)
        );
        return $sortable_columns;
    }

    protected function get_views() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'certificates';
        $current_status_filter = isset( $_GET['filter_status'] ) ? sanitize_text_field( $_GET['filter_status'] ) : '';
        $current_course_filter = isset( $_GET['filter_course'] ) ? sanitize_text_field( $_GET['filter_course'] ) : '';
        $base_url = admin_url('admin.php?page=cv-manage-certificates');

        $views = array();
        $views['all'] = sprintf(
            '<a href="%s" %s>%s <span class="count">(%d)</span></a>',
            remove_query_arg( array( 'filter_status', 'filter_course' ), $base_url ),
            ( empty( $current_status_filter ) && empty( $current_course_filter ) ) ? 'class="current"' : '',
                __('All', 'certificate-verification-for-wp'),
            $wpdb->get_var("SELECT COUNT(*) FROM $table_name")
        );

        // Example for filtering by status (you can add more for courses if needed)
        $statuses = $wpdb->get_col("SELECT DISTINCT course_status FROM $table_name ORDER BY course_status ASC");
        foreach ($statuses as $status) {
            if(empty($status)) continue;
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE course_status = %s", $status));
            $views[strtolower(sanitize_key($status))] = sprintf(
                '<a href="%s" %s>%s <span class="count">(%d)</span></a>',
                add_query_arg(array('filter_status' => $status, 'filter_course' => $current_course_filter), $base_url),
                ( $current_status_filter === $status ) ? 'class="current"' : '',
                esc_html($status),
                $count
            );
        }
        return $views;
    }

    protected function extra_tablenav( $which ) {
        if ( 'top' !== $which ) {
            return;
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'certificates';
        $courses = $wpdb->get_col( "SELECT DISTINCT course_name FROM {$table_name} ORDER BY course_name ASC" );
        // $statuses = $wpdb->get_col( "SELECT DISTINCT course_status FROM {$table_name} ORDER BY course_status ASC" ); // Already used in get_views, can reuse if needed for dropdown

        $current_course = ! empty( $_GET['filter_course'] ) ? sanitize_text_field( $_GET['filter_course'] ) : '';
        // $current_status = ! empty( $_GET['filter_status'] ) ? sanitize_text_field( $_GET['filter_status'] ) : '';

        echo '<div class="alignleft actions">';

        if ( ! empty( $courses ) ) {
                echo '<label for="filter-by-course" class="screen-reader-text">' . __( 'Filter by course', 'certificate-verification-for-wp' ) . '</label>';
            echo '<select name="filter_course" id="filter-by-course">';
                echo '<option value="">' . __( 'All courses', 'certificate-verification-for-wp' ) . '</option>';
            foreach ( $courses as $course ) {
                if(empty($course)) continue;
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr( $course ),
                    selected( $current_course, $course, false ),
                    esc_html( $course )
                );
            }
            echo '</select>';
        }
        submit_button( __( 'Filter' ), 'secondary', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
        echo '</div>';
    }


    public function prepare_items() {
        $this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), $this->get_primary_column_name() );

        $search_term = isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '';
        $filter_course = isset( $_REQUEST['filter_course'] ) ? sanitize_text_field( $_REQUEST['filter_course'] ) : '';
        $filter_status = isset( $_REQUEST['filter_status'] ) ? sanitize_text_field( $_REQUEST['filter_status'] ) : '';


        $per_page     = $this->get_items_per_page( 'certificates_per_page', 20 );
        $current_page = $this->get_pagenum();
        $total_items  = self::record_count($search_term, $filter_course, $filter_status);

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page
        ) );

        // Sorting - basic implementation
        $orderby = ( ! empty( $_REQUEST['orderby'] ) && array_key_exists($_REQUEST['orderby'], $this->get_sortable_columns()) ) ? sanitize_sql_orderby( $_REQUEST['orderby'] ) : 'student_name';
        $order = ( ! empty( $_REQUEST['order'] ) && in_array(strtoupper($_REQUEST['order']), array('ASC', 'DESC')) ) ? strtoupper( sanitize_key( $_REQUEST['order'] ) ) : 'ASC';

        // This is a simplified sort. For full DB sort, modify get_certificates
        $items = self::get_certificates( $per_page, $current_page, $search_term, $filter_course, $filter_status );

        // Basic client-side sort if not handled by DB (less efficient for large datasets)
        if (!empty($orderby) && !empty($items)) {
            usort($items, function($a, $b) use ($orderby, $order) {
                $a_val = $a[$orderby] ?? '';
                $b_val = $b[$orderby] ?? '';
                if ($orderby === 'issue_date' || $orderby === 'date_of_birth') { // Date comparison
                    $a_time = strtotime($a_val);
                    $b_time = strtotime($b_val);
                    return ($order === 'ASC') ? $a_time - $b_time : $b_time - $a_time;
                }
                return ($order === 'ASC') ? strnatcasecmp($a_val, $b_val) : strnatcasecmp($b_val, $a_val);
            });
        }
        $this->items = $items;
    }
	}
}
?>
