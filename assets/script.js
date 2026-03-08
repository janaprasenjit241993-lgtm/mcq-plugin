document.addEventListener("DOMContentLoaded", function () {

    // State variables
    let selectedMedium = "";
    let selectedSemester = "";
    let selectedSubject = "";
    let selectedChapter = "";
    let selectedTopic = "";

    let currentMCQ = null;
    let attemptedQuestions = [];
    let correctAnswers = 0;
    let totalAttempts = 0;
    let timerInterval;
    let timeElapsed = 0;
    let isTimerPaused = false;
    let optionSelected = false;

    // DOM Elements
    const mediumDropdown = document.getElementById("mcq-medium");
    const semesterDropdown = document.getElementById("mcq-semester");
    const subjectDropdown = document.getElementById("mcq-subject");   // NEW
    const chapterDropdown = document.getElementById("mcq-chapter");
    const topicDropdown = document.getElementById("mcq-topic");

    const startButton = document.getElementById("start-mcq-practice");
    const questionElement = document.getElementById("mcq-question");
    const optionButtons = document.querySelectorAll(".mcq-option");
    const nextButton = document.getElementById("next-mcq");
    const scoreBox = document.getElementById("mcq-score");
    const timerBox = document.getElementById("mcq-timer");
    const explanationButton = document.getElementById("show-explanation");
    const performanceButton = document.getElementById("my-performance");
    const performanceMessage = document.getElementById("performance-message");
    const timerControl = document.getElementById("timer-control");

    // Explanation elements
    const explanationContent = document.getElementById('mcq-explanation-content');
    const explanationText = document.getElementById('mcq-explanation-text');
    const explanationLink = document.getElementById('mcq-explanation-link');


    function queueMathTypeset(elements = []) {

        if (!window.MathJax) return;

        const targets = elements.filter(Boolean);

        if (typeof window.MathJax.typesetClear === 'function' && targets.length) {
            window.MathJax.typesetClear(targets);
        }

        if (typeof window.MathJax.typesetPromise === 'function') {
            window.MathJax.typesetPromise(targets.length ? targets : undefined).catch(console.error);
            return;
        }

        if (typeof window.MathJax.typeset === 'function') {
            window.MathJax.typeset(targets.length ? targets : undefined);
        }

    }


    // Initialization
    fetchDropdownData('fetch_mediums').then(mediums => {
        populateDropdown(mediumDropdown, mediums);
        mediumDropdown.disabled = false;
    });


    // Event Listeners
    mediumDropdown.addEventListener("change", handleMediumChange);
    semesterDropdown.addEventListener("change", handleSemesterChange);
    subjectDropdown.addEventListener("change", handleSubjectChange); // NEW
    chapterDropdown.addEventListener("change", handleChapterChange);
    topicDropdown.addEventListener("change", handleTopicChange);

    startButton.addEventListener("click", startPractice);
    nextButton.addEventListener("click", fetchMCQ);
    explanationButton.addEventListener("click", showExplanation);
    performanceButton.addEventListener("click", showPerformance);
    timerControl.addEventListener("click", toggleTimer);
    optionButtons.forEach(btn => btn.addEventListener("click", handleAnswer));


    // Medium Change
    function handleMediumChange() {

        selectedMedium = this.value;

        [semesterDropdown, subjectDropdown, chapterDropdown, topicDropdown].forEach(resetDropdown);

        startButton.disabled = true;

        if (selectedMedium) {

            fetchDropdownData('fetch_semesters', { medium: selectedMedium })

                .then(semesters => populateDropdown(semesterDropdown, semesters));

        }

    }


    // Semester Change
    function handleSemesterChange() {

        selectedSemester = this.value;

        [subjectDropdown, chapterDropdown, topicDropdown].forEach(resetDropdown);

        startButton.disabled = true;

        if (selectedMedium && selectedSemester) {

            fetchDropdownData('fetch_subjects', {
                medium: selectedMedium,
                semester: selectedSemester
            })

            .then(subjects => populateDropdown(subjectDropdown, subjects));

        }

    }


    // Subject Change
    function handleSubjectChange() {

        selectedSubject = this.value;

        [chapterDropdown, topicDropdown].forEach(resetDropdown);

        startButton.disabled = true;

        if (selectedMedium && selectedSemester && selectedSubject) {

            fetchDropdownData('fetch_chapters', {

                medium: selectedMedium,
                semester: selectedSemester,
                subject: selectedSubject

            }).then(chapters => populateDropdown(chapterDropdown, chapters));

        }

    }


    // Chapter Change
    function handleChapterChange() {

        selectedChapter = this.value;

        resetDropdown(topicDropdown);

        startButton.disabled = true;

        if (selectedMedium && selectedSemester && selectedSubject && selectedChapter) {

            fetchDropdownData('fetch_topics', {

                medium: selectedMedium,
                semester: selectedSemester,
                subject: selectedSubject,
                chapter: selectedChapter

            }).then(topics => populateDropdown(topicDropdown, topics));

        }

    }


    // Topic Change
    function handleTopicChange() {

        selectedTopic = this.value;

        startButton.disabled = !(

            selectedMedium &&
            selectedSemester &&
            selectedSubject &&
            selectedChapter &&
            selectedTopic

        );

    }



    // Fetch Dropdown Data
    function fetchDropdownData(action, params = {}) {

        return fetch(smart_mcq_ajax.ajax_url, {

            method: "POST",

            headers: {

                "Content-Type": "application/x-www-form-urlencoded"

            },

            body: new URLSearchParams({

                action,

                ...params,

                nonce: smart_mcq_ajax.nonce

            })

        })

        .then(response => response.json())

        .then(data => data.data || [])

        .catch(console.error);

    }



    function populateDropdown(dropdown, items) {

    dropdown.innerHTML = "";

    const placeholder = document.createElement("option");
    placeholder.textContent = `Select ${dropdown.id.replace('mcq-', '')}`;
    placeholder.value = "";
    placeholder.disabled = true;
    placeholder.selected = true;
    placeholder.hidden = true;

    dropdown.appendChild(placeholder);

    items.forEach(item => {
        dropdown.appendChild(new Option(item, item));
    });

    dropdown.disabled = items.length === 0;
}



    function resetDropdown(dropdown) {

    dropdown.innerHTML = "";

    const placeholder = document.createElement("option");
    placeholder.textContent = `Select ${dropdown.id.replace('mcq-', '')}`;
    placeholder.value = "";
    placeholder.disabled = true;
    placeholder.selected = true;
    placeholder.hidden = true;

    dropdown.appendChild(placeholder);

    dropdown.disabled = true;
}



    function fetchMCQ() {

        fetch(smart_mcq_ajax.ajax_url, {

            method: "POST",

            headers: {

                "Content-Type": "application/x-www-form-urlencoded"

            },

            body: new URLSearchParams({

                action: "fetch_mcq",

                medium: selectedMedium,

                semester: selectedSemester,

                subject: selectedSubject, // NEW

                chapter: selectedChapter,

                topic: selectedTopic,

                attempted: attemptedQuestions,

                nonce: smart_mcq_ajax.nonce

            })

        })

        .then(response => response.json())

        .then(({ success, data }) => success ? displayMCQ(data) : endPracticeSession())

        .catch(console.error);

    }


    // =============================
    // Remaining functions unchanged
    // =============================

    function displayMCQ(mcq) {

        currentMCQ = mcq;

        questionElement.innerHTML = mcq.question;

        optionSelected = false;

        explanationContent.style.display = "none";

        explanationText.innerHTML = "";

        explanationLink.innerHTML = "";

        optionButtons.forEach(btn => {

            const optionKey = btn.dataset.option;

            btn.innerHTML = `${optionKey}. ${mcq[`option_${optionKey.toLowerCase()}`]}`;

            btn.classList.remove("correct", "incorrect");

            btn.disabled = false;

        });

        nextButton.disabled = true;

        queueMathTypeset([questionElement, ...optionButtons]);

        if (window.MathJax && typeof window.MathJax.typesetPromise === "function") {
            window.MathJax.typesetPromise().catch(console.error);
        }

    }



    function handleAnswer() {

        if (!currentMCQ) return;

        optionSelected = true;

        const chosenOption = this.dataset.option;

        const correctOption = currentMCQ.correct_answer;

        this.classList.add(chosenOption === correctOption ? "correct" : "incorrect");

        if (chosenOption !== correctOption) {

            const correctButton = Array.from(optionButtons)

            .find(btn => btn.dataset.option === correctOption);

            if (correctButton) correctButton.classList.add("correct");

        }

        attemptedQuestions.push(currentMCQ.id);

        totalAttempts += 1;

        correctAnswers += (chosenOption === correctOption) ? 1 : 0;

        optionButtons.forEach(btn => btn.disabled = true);

        nextButton.disabled = false;

        updateScore();

    }



    function showExplanation() {

        if (!currentMCQ) return;

        explanationContent.style.display = "block";

        if (!optionSelected) {

            explanationText.innerHTML = "Please select an answer first";

            explanationLink.innerHTML = '';

            return;

        }

        explanationText.innerHTML = currentMCQ.explanation || "Explanation not available.";

        explanationText.classList.toggle('no-explanation', !currentMCQ.explanation);

        if (currentMCQ.explanation_link) {

            explanationLink.innerHTML = `
                <a href="${currentMCQ.explanation_link}" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="learn-more-link">
                   Learn More ↗
                </a>`;

        } else {

            explanationLink.innerHTML = '<span class="no-link-message">No additional resources available</span>';

        }

        queueMathTypeset([explanationText, explanationLink]);

    }



    function startPractice() {

        attemptedQuestions = [];

        correctAnswers = 0;

        totalAttempts = 0;

        updateScore();

        startTimer();

        fetchMCQ();

        timerControl.style.display = "inline-block";

    }



    function startTimer() {

        clearInterval(timerInterval);

        timeElapsed = 0;

        isTimerPaused = false;

        timerControl.textContent = "⏸";

        timerInterval = setInterval(updateTimer, 1000);

    }



    function updateTimer() {

        if (!isTimerPaused) timeElapsed++;

        const minutes = Math.floor(timeElapsed / 60).toString().padStart(2, "0");

        const seconds = (timeElapsed % 60).toString().padStart(2, "0");

        timerBox.textContent = `${minutes}:${seconds}`;

    }



    function toggleTimer() {

        isTimerPaused = !isTimerPaused;

        timerControl.textContent = isTimerPaused ? "▶" : "⏸";

        isTimerPaused

        ? clearInterval(timerInterval)

        : timerInterval = setInterval(updateTimer, 1000);

    }



    function updateScore() {

        scoreBox.textContent = `${correctAnswers}/${totalAttempts}`;

        scoreBox.classList.toggle('perfect-score',

            correctAnswers === totalAttempts && totalAttempts > 0

        );

    }



    function showPerformance() {

        let message = "Complete at least one question to view performance stats!";

        if (totalAttempts > 0) {

            const accuracy = Math.round((correctAnswers / totalAttempts) * 100);

            const thresholds = [

                [90, "Excellent! 🎉"],

                [80, "Very Good! 👍"],

                [70, "Good! 😊"],

                [60, "Average. 🤔"],

                [50, "Needs Practice. 📚"],

                [35, "Needs Improvement. 💪"],

                [0, "Let's Study More! 📖"]

            ].find(([min]) => accuracy >= min);

            message = `${accuracy}% Accuracy: ${thresholds[1]}`;

        }

        performanceMessage.innerHTML = message;

        performanceMessage.className = totalAttempts

        ? "show-performance"

        : "show-performance empty-state";

        setTimeout(() =>

            performanceMessage.classList.remove("show-performance"), 5000);

    }



    function endPracticeSession() {

        questionElement.textContent =

        "You've completed all available questions—upgrade to Premium for more!";

        optionButtons.forEach(btn => btn.style.display = "none");

        nextButton.style.display = "none";

        clearInterval(timerInterval);

        timerControl.style.display = "none";

        explanationContent.style.display = "none";

    }

});
