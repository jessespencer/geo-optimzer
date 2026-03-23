(function () {
    'use strict';

    function init() {
        var btn = document.getElementById('geo-opt-recalculate-btn');
        if (!btn) {
            return;
        }

        btn.addEventListener('click', function () {
            recalculateScore(btn);
        });
    }

    function recalculateScore(btn) {
        var postId = document.getElementById('post_ID');
        if (!postId) {
            return;
        }

        btn.disabled = true;
        var spinner = document.getElementById('geo-opt-recalculate-spinner');
        if (spinner) {
            spinner.classList.add('is-active');
        }

        var formData = new FormData();
        formData.append('action', 'geo_opt_calculate_score');
        formData.append('post_id', postId.value);
        formData.append('nonce', geoOptAdmin.nonce);

        fetch(geoOptAdmin.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.success) {
                    updateScoreDisplay(data.data);
                }
            })
            .catch(function (error) {
                console.error('GEO Optimizer score error:', error);
            })
            .finally(function () {
                btn.disabled = false;
                if (spinner) {
                    spinner.classList.remove('is-active');
                }
            });
    }

    function updateScoreDisplay(data) {
        var scoreNumber = document.getElementById('geo-opt-score-number');
        var scoreBarFill = document.getElementById('geo-opt-score-bar-fill');
        var breakdownContainer = document.getElementById('geo-opt-breakdown');
        var suggestionsContainer = document.getElementById('geo-opt-suggestions');
        var noScore = document.getElementById('geo-opt-no-score');

        if (noScore) {
            noScore.style.display = 'none';
        }

        var scoreDisplay = document.getElementById('geo-opt-score-area');
        if (scoreDisplay) {
            scoreDisplay.style.display = 'block';
        }

        var score = data.score;
        var colorClass = score < 40 ? 'red' : score < 70 ? 'yellow' : 'green';

        if (scoreNumber) {
            scoreNumber.textContent = score;
            scoreNumber.className = 'geo-opt-score-number geo-opt-score-' + colorClass + '-text';
        }

        if (scoreBarFill) {
            scoreBarFill.style.width = score + '%';
            scoreBarFill.className = 'geo-opt-score-bar-fill geo-opt-score-' + colorClass;
        }

        if (breakdownContainer && data.breakdown) {
            var html = '';
            var labels = {
                direct_answers: 'Direct Answers',
                question_headings: 'Question Headings',
                reading_level: 'Reading Level',
                entity_clarity: 'Entity Clarity',
                content_length: 'Content Length',
            };

            for (var key in data.breakdown) {
                if (data.breakdown.hasOwnProperty(key) && labels[key]) {
                    html +=
                        '<div class="geo-opt-breakdown-item">' +
                        '<span class="geo-opt-breakdown-label">' + labels[key] + '</span>' +
                        '<span class="geo-opt-breakdown-score">' + data.breakdown[key] + ' / 20</span>' +
                        '</div>';
                }
            }
            breakdownContainer.innerHTML = html;
        }

        if (suggestionsContainer && data.suggestions) {
            if (data.suggestions.length === 0) {
                suggestionsContainer.innerHTML = '<li style="background:#edf7ed;border-left-color:#00a32a;">Great job! Your content is well-optimized for AI engines.</li>';
            } else {
                var sugHtml = '';
                for (var i = 0; i < data.suggestions.length; i++) {
                    sugHtml += '<li>' + escapeHtml(data.suggestions[i]) + '</li>';
                }
                suggestionsContainer.innerHTML = sugHtml;
            }
        }
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
