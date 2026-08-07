document.addEventListener('DOMContentLoaded', function () {
    var hwWrap = document.querySelector('.tfp-week__homework');
    if (!hwWrap) return;

    var lessonId = hwWrap.getAttribute('data-lesson-id');
    var isSubmitted = hwWrap.getAttribute('data-submitted') === '1';
    var currentState = hwWrap.getAttribute('data-state') || 'state-1';
    var activeIndex = 0;

    var panels = {
        'state-1': document.querySelector('.tfp-week__homework-state-1'),
        'state-2': document.querySelector('.tfp-week__homework-state-2'),
        'state-3': document.querySelector('.tfp-week__homework-state-3'),
        'state-review': document.querySelector('.tfp-week__homework-state-review')
    };

    var qContainers = document.querySelectorAll('.tfp-week__homework-question-container');
    var navItems = document.querySelectorAll('.tfp-week__homework-list-item');

    function switchState(newState) {
        currentState = newState;
        for (var key in panels) {
            if (panels[key]) {
                panels[key].style.display = (key === newState) ? 'block' : 'none';
            }
        }
        
        if (newState === 'state-2') {
            showQuestion(activeIndex);
        }
    }

    function showQuestion(index) {
        activeIndex = index;
        qContainers.forEach(function (el) {
            el.style.display = (parseInt(el.getAttribute('data-index'), 10) === index) ? 'block' : 'none';
        });

        // Update active class on sidebar
        navItems.forEach(function (el) {
            if (parseInt(el.getAttribute('data-index'), 10) === index) {
                el.classList.add('is-active');
            } else {
                el.classList.remove('is-active');
            }
        });
    }

    // Start Button
    var startBtn = document.querySelector('.tfp-homework-start-btn');
    if (startBtn) {
        startBtn.addEventListener('click', function () {
            switchState('state-2');
        });
    }

    // Nav Buttons (Prev/Next)
    document.querySelectorAll('.tfp-homework-prev, .tfp-homework-next').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var targetId = this.getAttribute('data-target');
            var targetIndex = -1;
            qContainers.forEach(function (el, idx) {
                if (el.getAttribute('data-question-id') === targetId) {
                    targetIndex = idx;
                }
            });
            if (targetIndex !== -1) {
                showQuestion(targetIndex);
            }
        });
    });

    // Sidebar Nav — supports edit from review/state-3 and normal nav from state-2/state-1
    document.querySelectorAll('.tfp-week__homework-list-item').forEach(function (item) {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            var idx = parseInt(this.getAttribute('data-index'), 10);
            if (currentState === 'state-review' || currentState === 'state-3') {
                // Edit mode: open question for editing
                currentState = 'state-2';
                for (var key in panels) {
                    if (panels[key]) panels[key].style.display = (key === 'state-2') ? 'block' : 'none';
                }
                showQuestion(idx);
            } else if (currentState === 'state-1') {
                switchState('state-2');
                showQuestion(idx);
            } else {
                showQuestion(idx);
            }
        });
    });

    // Finish Button (from last question)
    var finishBtn = document.querySelector('.tfp-homework-finish');
    if (finishBtn) {
        finishBtn.addEventListener('click', function (e) {
            e.preventDefault();
            // Just visually go to state-3 if we are here.
            // Server side validation happens on actual submit.
            // Optionally, we could check if all are completed here.
            switchState('state-3');
        });
    }

    // Review Answers Button
    var reviewBtn = document.querySelector('.tfp-homework-review-btn');
    if (reviewBtn) {
        reviewBtn.addEventListener('click', function (e) {
            e.preventDefault();
            switchState('state-review');
        });
    }

    // Submit for Review Button
    var submitBtns = document.querySelectorAll('.tfp-homework-submit-btn');
    submitBtns.forEach(function(submitBtn) {
        submitBtn.addEventListener('click', function (e) {
            e.preventDefault();
            var btn = this;
            var originalText = btn.textContent;
            btn.textContent = 'Submitting...';
            btn.disabled = true;

            var data = new URLSearchParams();
            data.append('action', 'tfp_week_submit_homework');
            data.append('tfp_week_nonce', tfpWeekSettings.nonce);
            data.append('lesson_id', lessonId);

            fetch(tfpWeekSettings.ajaxUrl, {
                method: 'POST',
                body: data
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    switchState('state-review');
                    // Hide all submit buttons and show next step buttons
                    document.querySelectorAll('.tfp-homework-submit-btn').forEach(function(b) {
                        b.style.display = 'none';
                    });
                    document.querySelectorAll('.tfp-homework-next-step-btn').forEach(function(b) {
                        b.style.display = 'inline-flex';
                    });
                } else {
                    alert(res.message || 'Error submitting homework.');
                    btn.textContent = originalText;
                    btn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                alert('A network error occurred.');
                btn.textContent = originalText;
                btn.disabled = false;
            });
        });
    });

    // Auto-save logic
    var saveTimeout;
    
    function saveAnswer(container) {
        var qId = container.getAttribute('data-question-id');
        var indicator = container.querySelector('.tfp-homework-saving-indicator');
        
        var data = new URLSearchParams();
        data.append('action', 'tfp_week_save_homework_answer');
        data.append('tfp_week_nonce', tfpWeekSettings.nonce);
        data.append('lesson_id', lessonId);
        data.append('question_id', qId);

        // Gather inputs
        var radio = container.querySelector('input[type="radio"][name="hw_' + qId + '"]:checked');
        if (radio) data.append('selected_index', radio.value);

        var yn = container.querySelector('input[type="radio"][name="yn_' + qId + '"]:checked');
        if (yn) data.append('yes_no', yn.value);

        var text = container.querySelector('textarea[name="text_' + qId + '"]');
        if (text) data.append('text', text.value);

        if (indicator) {
            indicator.textContent = 'Saving...';
            indicator.style.display = 'inline-block';
        }

        fetch(tfpWeekSettings.ajaxUrl, {
            method: 'POST',
            body: data
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                if (indicator) {
                    indicator.textContent = 'Saved';
                    setTimeout(() => indicator.style.display = 'none', 2000);
                }
                
                // Update sidebar title and statuses
                var progressTitle = document.querySelector('.tfp-week__homework-progress-title');
                if (progressTitle && res.progress) {
                    progressTitle.textContent = 'Homework Progress — ' + res.progress.completed + ' of ' + res.progress.total + ' Completed';
                }
                
                // Mark current sidebar item as completed (naive client-side check, 
                // ideally we'd check actual response data, but doing it heuristically here)
                var navItem = document.querySelector('.tfp-week__homework-list-item[data-question-id="' + qId + '"]');
                if (navItem) {
                    navItem.classList.add('is-completed');
                    var statusSpan = navItem.querySelector('.tfp-week__homework-list-item-status span');
                    if (statusSpan) statusSpan.textContent = 'Completed';
                    var btn = navItem.querySelector('.tfp-homework-nav-btn');
                    if (btn) {
                        btn.textContent = 'Edit Answer';
                        btn.classList.remove('tfp-dash-btn--primary');
                        btn.classList.add('tfp-reded-btn');
                    }
                }
                
                // If all completed, enable finish/submit? Handled by state transitions.
            } else {
                if (indicator) indicator.textContent = 'Error';
            }
        })
        .catch(err => {
            console.error(err);
            if (indicator) indicator.textContent = 'Error';
        });
    }

    qContainers.forEach(function (container) {
        var inputs = container.querySelectorAll('input[type="radio"]');
        var textareas = container.querySelectorAll('textarea');

        inputs.forEach(function (input) {
            input.addEventListener('change', function () {
                saveAnswer(container);
            });
        });

        textareas.forEach(function (textarea) {
            textarea.addEventListener('input', function () {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(function () {
                    saveAnswer(container);
                }, 1000);
            });
        });
    });

    // Initialization
    if (currentState === 'state-2') {
        showQuestion(activeIndex);
    }
});
