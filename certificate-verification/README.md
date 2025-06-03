# Certificate Verification for WP

[![License: GPLv2 or later](https://img.shields.io/badge/License-GPLv2%20or%20later-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Current Version: 1.0.0](https://img.shields.io/badge/Version-1.0.0-brightgreen.svg)](#)
[![WordPress Tested: 6.5](https://img.shields.io/badge/WordPress%20Tested-6.5-orange.svg)](#)
[![Requires PHP: 7.4+](https://img.shields.io/badge/PHP-7.4%2B-blueviolet.svg)](#)

**Plugin URI:** [https://cibdhk.com/verification/](https://cibdhk.com/verification/) <br>
**Author:** Hasan Rizvee <br>
**Author URI:** [https://rizvee.github.io](https://rizvee.github.io)

A plugin to verify student certificates via Roll/ID and manage certificate data efficiently within WordPress.

== Description ==

Certificate Verification for WP provides a comprehensive solution for educational institutions and organizations to manage and verify student certificates. Admins can easily add, edit, delete, and bulk import certificate records. A simple shortcode allows placement of a verification form on any page, enabling users (students, employers, etc.) to verify certificate authenticity by entering a Roll Number or Student ID.

The plugin is designed to be secure, user-friendly, and lightweight, ensuring a seamless experience for both administrators and those seeking to verify credentials.

== Features (Version 1.0.0) ==

**I. Core Functionality & Data Management:**

* **Custom Database Table:** A dedicated database table to store all certificate records securely.
* **Structured Certificate Data:** Stores detailed information per certificate:
    * ID (Primary Key, Auto-increment)
    * Roll/Student ID (Unique) (Implemented as `roll_id`)
    * Student Name
    * Father's Name (Part of `father_mother_name` in current implementation)
    * Mother's Name (Part of `father_mother_name` in current implementation)
    * Course Name
    * Course Status (e.g., 'Completed', 'In Progress', 'Failed')
    * Date of Birth
    * Certificate Issue Date
    * Unique Certificate ID (for future QR code or direct link verification)
    * Creation Timestamp (`created_at`)
    * Last Updated Timestamp (`updated_at`)

**II. Admin Panel Features (Certificate Management):**

* **Dedicated Admin Menu:** A top-level "Certificates" menu in the WordPress admin dashboard for easy access.
* **Add New Certificate:** A user-friendly form for manually entering individual certificate details.
* **Edit Certificate:** Ability to modify the details of existing certificates.
* **Delete Certificate:** Option to remove certificate records from the database.
* **View All Certificates:** A paginated list displaying all entered certificates, built using `WP_List_Table` for a native WordPress experience.
    * Search functionality (by Roll/ID, Student Name, Course Name).
    * Filtering options (by Course Name, Course Status).
* **Bulk CSV Import:** Functionality for admins to upload a CSV file to add multiple certificate records at once, with data validation during import. (Note: Sample CSV uses `roll_id` and `father_mother_name`)
* **Access Control:** Plugin management features are restricted to users with 'manage_options' capability.

**III. Front-End Verification System:**

* **Shortcode Integration:** `[certificate_verification_form]` shortcode to easily embed the verification form on any WordPress page or post.
* **User-Friendly Verification Form:**
    * Modern, clean, and responsive design (basic styling provided).
    * Single input field for 'Roll or ID'.
    * "Verify" button to initiate the search.
* **AJAX Powered Verification:** Certificate details are fetched and displayed without a page reload for a smoother user experience.
* **Dynamic Result Display:**
    * If a matching Roll/ID is found, relevant details (Student Name, Father's/Mother's Name, Course Name & Status, DOB, Issue Date) are displayed.
    * If no match is found, a clear message "The result for the inputted ID, not found in our server." is displayed.

**IV. Security & Performance:**

* **Nonce Protection:** Use of WordPress nonces for all form submissions and AJAX requests.
* **Data Sanitization:** All input data is sanitized before processing or database storage.
* **Output Escaping:** All data outputted to the browser is escaped to prevent XSS.
* **SQL Injection Prevention:** Use of `$wpdb->prepare()` for database queries.
* **Optimized Queries:** Efficient database operations for good performance.
* **Lightweight Code:** Developed to be lean and fast.

**V. Plugin Standards & Usability:**

* **Activation Hook:** Automatically creates the custom database table (including `created_at`, `updated_at`) on plugin activation.
* **Uninstall Hook (`uninstall.php`):** Properly cleans up plugin data (drops custom table) upon uninstallation.
* **Separate Assets:** Enqueues distinct CSS and JavaScript files for admin and public sections.
* **Internationalization (I18n):** All plugin strings are translatable. Text Domain: `certificate-verification-for-wp`. `languages/` directory created.
* **WordPress Coding Standards:** Adherence to best practices for compatibility and maintainability.

== Installation ==

1.  Upload the `certificate-verification-for-wp` folder (or the plugin's root folder if named differently) to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to the "Certificates" menu in your WordPress admin panel to start adding and managing certificates.
4.  To display the verification form on a page or post, use the shortcode: `[certificate_verification_form]`

== Frequently Asked Questions ==

* **Q: How do I display the verification form?**
    * A: Use the shortcode `[certificate_verification_form]` on any page or post.

* **Q: What format should the CSV file be for bulk import?**
    * A: The CSV file should have columns corresponding to the certificate data fields: `student_name`, `father_mother_name`, `roll_id`, `course_name`, `course_status`, `date_of_birth`, `issue_date`, `certificate_uid`. A sample CSV is provided within the plugin's admin section (`admin/sample-import.csv`). (Note: While the ideal might be separate parent names and `roll_or_id`, the current implementation uses combined names and `roll_id`.)

* **Q: Is the plugin secure?**
    * A: Yes, the plugin follows WordPress security best practices, including nonces, data sanitization, output escaping, and prepared SQL statements.

== Screenshots ==

*(Screenshots would be added to an `assets` directory in a WordPress.org submission)*
1.  Admin Panel - Certificate List View
2.  Admin Panel - Add New Certificate Form
3.  Front-End - Certificate Verification Form
4.  Front-End - Certificate Verification Result (Success)
5.  Front-End - Certificate Verification Result (Not Found)

== Changelog ==

= 1.0.0 =
* Initial release. Features include manual certificate management (add, edit, delete, view), bulk CSV import, AJAX-powered front-end verification via shortcode, security measures (nonces, sanitization, escaping), and internationalization readiness.

== Upgrade Notice ==

= 1.0.0 =
* Initial release of the Certificate Verification for WP plugin.

== Future Enhancement Scope ==

Here are potential features and improvements that can be considered for future versions of the plugin:

**I. Advanced Admin & Data Management:**

* **Advanced Search & Filtering:** Implement multi-field search (e.g., by name, course, date range), and more advanced sortable columns in the admin certificate list.
* **Further WP_List_Table Enhancements:** e.g. advanced column management.
* **Data Export:** Allow admins to export certificate data to CSV, Excel, or PDF formats.
* **Customizable Admin Columns:** Let admins choose which data fields to display.
* **Audit Log:** Track changes made to certificates.
* **Role-Based Access Control (RBAC):** Grant plugin management capabilities to custom user roles.
* **Dashboard Widgets:** Display summary statistics on the WordPress dashboard.
* **Dedicated Settings Page:** For customizing messages, date formats, etc.

**II. Enhanced Verification & Display:**

* **QR Code Verification System:** Generate unique QR codes for certificates linking to a verification page.
* **Printable/Downloadable Certificate Stub:** Display verified details in an official template, possibly downloadable as PDF.
* **Direct Link Verification:** Enable verification via a unique URL.
* **CAPTCHA/reCAPTCHA Integration:** Add to the verification form.
* **Multi-Field Verification:** Option to require multiple fields for verification.
* **Analytics & Reporting:** Track verification attempts.

**III. Design & User Experience:**

* **Front-End Form & Result Customizer:** Basic styling options via the admin panel.
* **Email Notifications:** Optional system for notifications.
* **Multiple Result Templates:** Offer different design templates for verified information display.
```
