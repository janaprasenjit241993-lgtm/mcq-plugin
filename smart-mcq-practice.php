<?php
/**
 * Plugin Name: Smart MCQ Practice
 * Description: Advanced MCQ practice system with dynamic selection, timer, performance analytics, and explanation links.
 * Version: 1.2
 * Author: JanaSir
 * License: GPL-2.0+
 * Text Domain: smart-mcq-practice
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('SMART_MCQ_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SMART_MCQ_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SMART_MCQ_VERSION', '1.2');

// Include core components
require_once SMART_MCQ_PLUGIN_PATH . 'includes/class-db-handler.php';
require_once SMART_MCQ_PLUGIN_PATH . 'includes/class-mcq-handler.php';
require_once SMART_MCQ_PLUGIN_PATH . 'includes/admin-panel.php';
require_once SMART_MCQ_PLUGIN_PATH . 'includes/template-functions.php';

class Smart_Mcq_Core {

    public static function init() {

        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);

        add_shortcode('smart_mcq_practice', [__CLASS__, 'practice_shortcode']);

        register_activation_hook(__FILE__, [__CLASS__, 'activate_plugin']);

        self::register_ajax_actions();

    }

    public static function enqueue_assets() {

        wp_enqueue_style(
            'smart-mcq-style',
            SMART_MCQ_PLUGIN_URL . 'assets/style.css',
            [],
            SMART_MCQ_VERSION
        );

        wp_enqueue_script(
            'smart-mcq-script',
            SMART_MCQ_PLUGIN_URL . 'assets/script.js',
            ['jquery'],
            SMART_MCQ_VERSION,
            true
        );

        wp_localize_script(
            'smart-mcq-script',
            'smart_mcq_ajax',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('smart_mcq_nonce')
            ]
        );

    }

    public static function practice_shortcode() {

        ob_start();

        include SMART_MCQ_PLUGIN_PATH . 'templates/frontend-view.php';

        return ob_get_clean();

    }

    public static function activate_plugin() {

        Smart_Mcq_DB_Handler::create_tables();

    }

    private static function register_ajax_actions() {

        $actions = [

            'fetch_mediums',
            'fetch_semesters',
            'fetch_subjects', // NEW
            'fetch_chapters',
            'fetch_topics',
            'fetch_mcq',
            'submit_answer'

        ];

        foreach ($actions as $action) {

            add_action("wp_ajax_{$action}", ['Smart_Mcq_Handler', $action]);

            add_action("wp_ajax_nopriv_{$action}", ['Smart_Mcq_Handler', $action]);

        }

    }

}

Smart_Mcq_Core::init();