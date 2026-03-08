<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Smart_Mcq_Template_Functions {

    public static function render_selection_panel() {
        ?>
        <div id="mcq-selection-box" class="mcq-box">
            <h4>Set Your Practice Criteria</h4>
            <div class="selection-grid">

                <div class="select-group">
                    <label>Choose Your Medium:</label>
                    <select id="mcq-medium" disabled class="mcq-dropdown">
                        <option value="">Choose Your Medium</option>
                    </select>
                </div>
                
                <div class="select-group">
                    <label>Choose Semester:</label>
                    <select id="mcq-semester" disabled class="mcq-dropdown">
                        <option value="">Choose Semester</option>
                    </select>
                </div>

                <!-- NEW SUBJECT DROPDOWN -->
                <div class="select-group">
                    <label>Choose Subject:</label>
                    <select id="mcq-subject" disabled class="mcq-dropdown">
                        <option value="">Choose Subject</option>
                    </select>
                </div>

                <div class="select-group">
                    <label>Pick a Chapter:</label>
                    <select id="mcq-chapter" disabled class="mcq-dropdown">
                        <option value="">Pick a Chapter</option>
                    </select>
                </div>

                <div class="select-group">
                    <label>Choose a Topic:</label>
                    <select id="mcq-topic" disabled class="mcq-dropdown">
                        <option value="">Choose a Topic</option>
                    </select>
                </div>

            </div>

            <button id="start-mcq-practice" class="mcq-btn" disabled>
                Start Practicing Now
            </button>
        </div>
        <?php
    }

    public static function render_mcq_practice_section() {
        ?>
        <div id="mcq-practice-section" class="mcq-box">
            <h4>Your MCQ Session</h4>

            <div class="stats-container">
                <div id="mcq-timer-box" class="stat-box">
                    <span>Timer:</span>
                    <span id="mcq-timer">00:00</span>
                    <button id="timer-control" class="timer-btn">⏸</button>
                </div>

                <div id="mcq-score-box" class="stat-box">
                    <span>Score:</span>
                    <span id="mcq-score">0/0</span>
                </div>
            </div>

            <div id="mcq-question-container">

                <div id="mcq-question" class="question-box">
                    "Select Medium, Semester, Subject, Chapter & Topic to start! Limited questions available—upgrade to Premium for more!"
                </div>

                <div id="mcq-options" class="options-grid">
                    <button class="mcq-option" data-option="A" aria-label="Option A"></button>
                    <button class="mcq-option" data-option="B" aria-label="Option B"></button>
                    <button class="mcq-option" data-option="C" aria-label="Option C"></button>
                    <button class="mcq-option" data-option="D" aria-label="Option D"></button>
                </div>

            </div>

            <button id="next-mcq" class="mcq-btn" disabled>
                Next MCQ
            </button>
        </div>
        <?php
    }
    
    public static function render_explanation_section() {
        ?>
        <div id="mcq-explanation-section" class="mcq-box">

            <div class="explanation-header">
                <h4>Question Explanation</h4>

                <button id="show-explanation" class="mcq-btn" aria-label="Show explanation">
                    See Explanation
                </button>
            </div>

            <div id="mcq-explanation-content" class="explanation-content" style="display: none;">

                <p id="mcq-explanation-text" class="explanation-text" style="white-space: pre-line;"></p>

                <p id="mcq-explanation-link" class="explanation-link"></p>

            </div>

        </div>
        <?php
    }

    public static function render_performance_section() {
        ?>
        <div id="mcq-performance-section" class="mcq-box">

            <h4>Your Performance Report</h4>

            <button id="my-performance" class="mcq-btn" aria-label="Show performance">
                View My Performance
            </button>

            <div id="performance-message" class="performance-message">
                Complete at least one question to view your Performance Report! Limited questions available—upgrade to Premium for more!
            </div>

        </div>
        <?php
    }
}
?>