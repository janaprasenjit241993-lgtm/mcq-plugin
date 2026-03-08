<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Smart_Mcq_DB_Handler {

    private static $table_name;

    public static function init() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'smart_mcq_practice';
    }

    public static function create_tables() {
        global $wpdb;
        self::init();

        $table_name = self::$table_name;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (

            id INT AUTO_INCREMENT PRIMARY KEY,

            medium VARCHAR(50) NOT NULL,

            semester VARCHAR(50) NOT NULL,

            subject VARCHAR(100) NOT NULL,

            chapter VARCHAR(100) NOT NULL,

            topic VARCHAR(100) NOT NULL,

            question TEXT NOT NULL,

            option_a TEXT NOT NULL,

            option_b TEXT NOT NULL,

            option_c TEXT NOT NULL,

            option_d TEXT NOT NULL,

            correct_answer VARCHAR(5) NOT NULL,

            explanation TEXT,

            explanation_link VARCHAR(255)

        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($sql);


        // Ensure subject column exists (for older versions)

        $subject_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'subject'");

        if (empty($subject_exists)) {

            $wpdb->query("ALTER TABLE $table_name ADD COLUMN subject VARCHAR(100) NOT NULL AFTER semester");

        }


        // Ensure explanation_link column exists (for older versions)

        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'explanation_link'");

        if (empty($column_exists)) {

            $wpdb->query("ALTER TABLE $table_name ADD COLUMN explanation_link VARCHAR(255)");

        }

    }



    public static function insert_mcq(

        $medium,

        $semester,

        $subject,

        $chapter,

        $topic,

        $question,

        $option_a,

        $option_b,

        $option_c,

        $option_d,

        $correct_answer,

        $explanation,

        $explanation_link

    ) {

        global $wpdb;

        self::init();


        $result = $wpdb->insert(

            self::$table_name,

            [

                'medium'           => $medium,

                'semester'         => $semester,

                'subject'          => $subject,

                'chapter'          => $chapter,

                'topic'            => $topic,

                'question'         => $question,

                'option_a'         => $option_a,

                'option_b'         => $option_b,

                'option_c'         => $option_c,

                'option_d'         => $option_d,

                'correct_answer'   => $correct_answer,

                'explanation'      => $explanation,

                'explanation_link' => $explanation_link

            ],

            [

                '%s','%s','%s','%s','%s',

                '%s','%s','%s','%s','%s',

                '%s','%s','%s'

            ]

        );


        if ($result === false) {

            error_log("MCQ Insertion Failed: " . $wpdb->last_error);

            return false;

        }

        return $wpdb->insert_id;

    }



    public static function get_random_mcq(

        $medium,

        $semester,

        $subject,

        $chapter,

        $topic,

        $attempted_questions = []

    ) {

        global $wpdb;

        self::init();


        $query = "SELECT * FROM " . self::$table_name . "

                  WHERE medium = %s

                  AND semester = %s

                  AND subject = %s

                  AND chapter = %s

                  AND topic = %s";


        $params = [

            $medium,

            $semester,

            $subject,

            $chapter,

            $topic

        ];


        if (!empty($attempted_questions)) {

            $placeholders = implode(',', array_fill(0, count($attempted_questions), '%d'));

            $query .= " AND id NOT IN ($placeholders)";

            $params = array_merge($params, $attempted_questions);

        }


        $query .= " ORDER BY RAND() LIMIT 1";


        return $wpdb->get_row($wpdb->prepare($query, $params));

    }



    public static function check_answer($question_id, $selected_answer) {

        global $wpdb;

        self::init();


        $correct_answer = $wpdb->get_var($wpdb->prepare(

            "SELECT correct_answer FROM " . self::$table_name . " WHERE id = %d",

            $question_id

        ));


        return [

            'correct'        => ($correct_answer === $selected_answer),

            'correct_answer' => $correct_answer

        ];

    }



    public static function get_all_mcqs() {

        global $wpdb;

        self::init();


        return $wpdb->get_results(

            "SELECT id,

                    medium,

                    semester,

                    subject,

                    chapter,

                    topic,

                    LEFT(question, 100) AS question_short,

                    explanation_link

             FROM " . self::$table_name . "

             ORDER BY id DESC"

        );

    }



    public static function delete_mcq($id) {

        global $wpdb;

        self::init();


        return $wpdb->delete(

            self::$table_name,

            ['id' => $id],

            ['%d']

        );

    }

}