(function () {
    'use strict';

    function init() {
        initBotList();
    }

    function initBotList() {
        var addBtn = document.getElementById('geo-opt-add-bot');
        if (!addBtn) {
            return;
        }

        addBtn.addEventListener('click', function () {
            var container = document.getElementById('geo-opt-bot-list');
            var row = document.createElement('div');
            row.className = 'geo-opt-bot-row';
            row.innerHTML =
                '<input type="text" name="geo_opt_settings[robots_bots][]" value="" class="regular-text" placeholder="BotName" />' +
                '<button type="button" class="button geo-opt-remove-bot">Remove</button>';
            container.appendChild(row);
        });

        document.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('geo-opt-remove-bot')) {
                var row = e.target.closest('.geo-opt-bot-row');
                if (row) {
                    row.remove();
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
