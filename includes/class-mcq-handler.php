<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Smart_Mcq_Handler {

    private static $table_name;

    public static function init() {

        global $wpdb;

        self::$table_name = $wpdb->prefix . 'smart_mcq_practice';


        add_action('wp_ajax_fetch_mediums', [__CLASS__, 'fetch_mediums']);
        add_action('wp_ajax_nopriv_fetch_mediums', [__CLASS__, 'fetch_mediums']);


        add_action('wp_ajax_fetch_semesters', [__CLASS__, 'fetch_semesters']);
        add_action('wp_ajax_nopriv_fetch_semesters', [__CLASS__, 'fetch_semesters']);


        add_action('wp_ajax_fetch_subjects', [__CLASS__, 'fetch_subjects']);
        add_action('wp_ajax_nopriv_fetch_subjects', [__CLASS__, 'fetch_subjects']);


        add_action('wp_ajax_fetch_chapters', [__CLASS__, 'fetch_chapters']);
        add_action('wp_ajax_nopriv_fetch_chapters', [__CLASS__, 'fetch_chapters']);


        add_action('wp_ajax_fetch_topics', [__CLASS__, 'fetch_topics']);
        add_action('wp_ajax_nopriv_fetch_topics', [__CLASS__, 'fetch_topics']);


        add_action('wp_ajax_fetch_mcq', [__CLASS__, 'fetch_mcq']);
        add_action('wp_ajax_nopriv_fetch_mcq', [__CLASS__, 'fetch_mcq']);


        add_action('wp_ajax_submit_answer', [__CLASS__, 'submit_answer']);
        add_action('wp_ajax_nopriv_submit_answer', [__CLASS__, 'submit_answer']);

    }



    public static function fetch_mediums() {

        global $wpdb;

        self::init();


        $mediums = $wpdb->get_col(

            "SELECT DISTINCT medium FROM " . self::$table_name

        );


        self::send_json_response($mediums, 'No mediums found.');

    }



    public static function fetch_semesters() {

        self::validate_request(['medium']);


        $medium = sanitize_text_field($_POST['medium']);


        global $wpdb;


        $semesters = $wpdb->get_col(

            $wpdb->prepare(

                "SELECT DISTINCT semester 

                FROM " . self::$table_name . "

                WHERE medium = %s",

                $medium

            )

        );


        self::send_json_response($semesters,

            'No semesters found for this medium.');

    }



    public static function fetch_subjects() {

        self::validate_request(['medium','semester']);


        $medium = sanitize_text_field($_POST['medium']);

        $semester = sanitize_text_field($_POST['semester']);


        global $wpdb;


        $subjects = $wpdb->get_col(

            $wpdb->prepare(

                "SELECT DISTINCT subject

                FROM " . self::$table_name . "

                WHERE medium = %s

                AND semester = %s",

                $medium,

                $semester

            )

        );


        self::send_json_response($subjects,

            'No subjects found.');

    }



    public static function fetch_chapters() {

        self::validate_request(['medium','semester','subject']);


        $medium = sanitize_text_field($_POST['medium']);

        $semester = sanitize_text_field($_POST['semester']);

        $subject = sanitize_text_field($_POST['subject']);


        global $wpdb;


        $chapters = $wpdb->get_col(

            $wpdb->prepare(

                "SELECT DISTINCT chapter

                FROM " . self::$table_name . "

                WHERE medium = %s

                AND semester = %s

                AND subject = %s",

                $medium,

                $semester,

                $subject

            )

        );


        self::send_json_response($chapters,

            'No chapters found.');

    }



    public static function fetch_topics() {

        self::validate_request(['medium','semester','subject','chapter']);


        $medium = sanitize_text_field($_POST['medium']);

        $semester = sanitize_text_field($_POST['semester']);

        $subject = sanitize_text_field($_POST['subject']);

        $chapter = sanitize_text_field($_POST['chapter']);


        global $wpdb;


        $topics = $wpdb->get_col(

            $wpdb->prepare(

                "SELECT DISTINCT topic

                FROM " . self::$table_name . "

                WHERE medium = %s

                AND semester = %s

                AND subject = %s

                AND chapter = %s",

                $medium,

                $semester,

                $subject,

                $chapter

            )

        );


        self::send_json_response($topics,

            'No topics found.');

    }



    public static function fetch_mcq() {

        self::validate_request(['medium','semester','subject','chapter','topic']);


        $medium = sanitize_text_field($_POST['medium']);

        $semester = sanitize_text_field($_POST['semester']);

        $subject = sanitize_text_field($_POST['subject']);

        $chapter = sanitize_text_field($_POST['chapter']);

        $topic = sanitize_text_field($_POST['topic']);


        $attempted = self::sanitize_ids($_POST['attempted'] ?? '');


        $mcq = Smart_Mcq_DB_Handler::get_random_mcq(

            $medium,

            $semester,

            $subject,

            $chapter,

            $topic,

            $attempted

        );


        if ($mcq) {

            wp_send_json_success([

                'id' => (int)$mcq->id,

                'question' => htmlspecialchars_decode(

                    wp_kses_post(stripslashes($mcq->question))

                ),

                'option_a' => wp_kses_post(stripslashes($mcq->option_a)),

                'option_b' => wp_kses_post(stripslashes($mcq->option_b)),

                'option_c' => wp_kses_post(stripslashes($mcq->option_c)),

                'option_d' => wp_kses_post(stripslashes($mcq->option_d)),

                'correct_answer' => sanitize_text_field($mcq->correct_answer),

                'explanation' => htmlspecialchars_decode(

                    wp_kses_post(stripslashes($mcq->explanation ?? ''))

                ),

                'explanation_link' => esc_url($mcq->explanation_link ?? '')

            ]);

        } else {

            wp_send_json_error([

                'message' => 'No more questions available.'

            ]);

        }

    }



    public static function submit_answer() {

        self::validate_request(['question_id','selected_answer']);


        $question_id = (int)$_POST['question_id'];

        $selected_answer = sanitize_text_field($_POST['selected_answer']);


        $result = Smart_Mcq_DB_Handler::check_answer(

            $question_id,

            $selected_answer

        );


        if ($result) {

            $mcq_data = self::get_mcq_details($question_id);


            wp_send_json_success([

                'correct' => $result['correct'],

                'correct_answer' => $result['correct_answer'],

                'explanation' => $mcq_data['explanation'],

                'explanation_link' => $mcq_data['explanation_link']

            ]);

        }


        wp_send_json_error([

            'message' => 'Invalid question ID.'

        ]);

    }



    private static function validate_request($required_fields) {

        foreach ($required_fields as $field) {

            if (!isset($_POST[$field])) {

                wp_send_json_error([

                    'message' => "Missing required field: $field"

                ]);

            }

        }

    }



    private static function send_json_response($data,$error_message) {

        if (empty($data)) {

            wp_send_json_error([

                'message' => $error_message

            ]);

        }

        wp_send_json_success($data);

    }



    private static function sanitize_ids($id_string) {

        return array_map(

            'intval',

            explode(',', sanitize_text_field($id_string))

        );

    }



    private static function get_mcq_details($question_id) {

        global $wpdb;


        $mcq = $wpdb->get_row(

            $wpdb->prepare(

                "SELECT explanation, explanation_link

                 FROM " . self::$table_name . "

                 WHERE id = %d",

                $question_id

            )

        );


        return [

            'explanation' => $mcq

                ? wp_kses_post(stripslashes($mcq->explanation))

                : '',

            'explanation_link' => $mcq

                ? esc_url($mcq->explanation_link)

                : ''

        ];

    }

}

Smart_Mcq_Handler::init();