<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Smart_Mcq_Admin_Panel {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_menu']);
        add_action('admin_post_smart_mcq_csv_upload', [__CLASS__, 'handle_csv_upload']);
        add_action('admin_init', [__CLASS__, 'handle_mcq_actions']);
    }

    public static function register_menu() {
        add_menu_page(
            'Smart MCQ Practice Admin',
            'Smart MCQ Practice',
            'manage_options',
            'smart-mcq-practice-admin',
            [__CLASS__, 'admin_page'],
            'dashicons-welcome-learn-more',
            6
        );
    }

    public static function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart MCQ Practice Admin Panel</h1>
            
            <!-- CSV Upload Form -->
            <div class="card">
                <h2>Upload CSV</h2>
                <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="smart_mcq_csv_upload">
                    <?php wp_nonce_field('smart_mcq_csv_upload_nonce', 'smart_mcq_csv_nonce'); ?>
                    <p>
                        <input type="file" name="smart_mcq_csv" accept=".csv" required>
                        <br><small>
                        CSV format: Medium, Semester, Subject, Chapter, Topic, Question, Options A-D, Correct Answer, Explanation, Explanation Link
                        </small>
                    </p>
                    <?php submit_button('Upload CSV'); ?>
                </form>
            </div>

            <?php if (isset($_GET['upload'])) : ?>
                <div id="message" class="<?php echo $_GET['upload'] === 'success' ? 'updated' : 'error'; ?> notice is-dismissible">
                    <p><?php echo $_GET['upload'] === 'success' ? 'CSV uploaded successfully!' : 'CSV upload failed.'; ?></p>
                </div>
            <?php endif; ?>

            <div class="card">
                <h2>Manage MCQs</h2>
                <?php self::display_mcq_table(); ?>
            </div>
        </div>
        <?php
    }

    public static function handle_csv_upload() {

        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized access.'));
        }

        check_admin_referer('smart_mcq_csv_upload_nonce', 'smart_mcq_csv_nonce');

        if (!empty($_FILES['smart_mcq_csv']['tmp_name'])) {

            $file = $_FILES['smart_mcq_csv']['tmp_name'];

            $processed = 0;
            $errors = 0;

            if (($handle = fopen($file, 'r')) !== false) {

                fgetcsv($handle, 1000, ','); // Skip header

                while (($data = fgetcsv($handle, 1000, ',')) !== false) {

                    if (count($data) >= 13) {

                        $result = Smart_Mcq_DB_Handler::insert_mcq(

                            sanitize_text_field($data[0]), // Medium
                            sanitize_text_field($data[1]), // Semester
                            sanitize_text_field($data[2]), // Subject
                            sanitize_text_field($data[3]), // Chapter
                            sanitize_text_field($data[4]), // Topic
                            sanitize_textarea_field($data[5]), // Question
                            sanitize_textarea_field($data[6]), // Option A
                            sanitize_textarea_field($data[7]), // Option B
                            sanitize_textarea_field($data[8]), // Option C
                            sanitize_textarea_field($data[9]), // Option D
                            sanitize_text_field($data[10]), // Correct Answer
                            sanitize_textarea_field($data[11]), // Explanation
                            esc_url_raw($data[12]) // Explanation Link

                        );

                        $result ? $processed++ : $errors++;

                    }

                }

                fclose($handle);

                $redirect = admin_url(

                    'admin.php?page=smart-mcq-practice-admin&upload=' .

                    ($errors === 0 ? 'success' : 'partial&processed='.$processed.'&errors='.$errors)

                );

                wp_redirect($redirect);

                exit;

            }

        }

        wp_redirect(admin_url('admin.php?page=smart-mcq-practice-admin&upload=fail'));

        exit;

    }

    public static function handle_mcq_actions() {

        if (!current_user_can('manage_options') || !isset($_GET['action'])) {

            return;

        }

        if ($_GET['action'] === 'delete' && !empty($_GET['id'])) {

            $id = intval($_GET['id']);

            Smart_Mcq_DB_Handler::delete_mcq($id);

            wp_redirect(admin_url('admin.php?page=smart-mcq-practice-admin&deleted=1'));

            exit;

        }

    }

    public static function display_mcq_table() {

        $mcqs = Smart_Mcq_DB_Handler::get_all_mcqs();

        echo '<table class="wp-list-table widefat fixed striped">';

        echo '<thead><tr>

                <th>ID</th>

                <th>Medium</th>

                <th>Semester</th>

                <th>Subject</th>

                <th>Chapter</th>

                <th>Topic</th>

                <th>Question</th>

                <th>Explanation</th>

                <th>Link</th>

                <th>Actions</th>

              </tr></thead>';

        if ($mcqs) {

            echo '<tbody>';

            foreach ($mcqs as $mcq) {

                echo '<tr>

                    <td>'.esc_html($mcq->id).'</td>

                    <td>'.esc_html($mcq->medium).'</td>

                    <td>'.esc_html($mcq->semester).'</td>

                    <td>'.esc_html($mcq->subject).'</td>

                    <td>'.esc_html($mcq->chapter).'</td>

                    <td>'.esc_html($mcq->topic).'</td>

                    <td>'.esc_html(wp_trim_words($mcq->question, 10)).'</td>

                    <td>'.esc_html(wp_trim_words($mcq->explanation, 5)).'</td>

                    <td>'.($mcq->explanation_link ? 

                        '<a href="'.esc_url($mcq->explanation_link).'" target="_blank">View</a>' : 

                        'N/A').'</td>

                    <td>

                        <a href="'.admin_url('admin.php?page=smart-mcq-practice-admin&action=edit&id='.$mcq->id).'">Edit</a> | 

                        <a href="'.admin_url('admin.php?page=smart-mcq-practice-admin&action=delete&id='.$mcq->id).'" 

                           onclick="return confirm(\'Delete this MCQ?\')">Delete</a>

                    </td>

                </tr>';

            }

            echo '</tbody>';

        } else {

            echo '<tbody><tr><td colspan="10">No MCQs found.</td></tr></tbody>';

        }

        echo '</table>';

    }

}

Smart_Mcq_Admin_Panel::init();