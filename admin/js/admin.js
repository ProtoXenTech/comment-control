/**
 * Comment Control Admin Tabs JS
 *
 * @package Comment_Control
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const tabButtons = document.querySelectorAll('.cctl-tab-btn');
        const tabPanes = document.querySelectorAll('.cctl-tab-pane');

        tabButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const targetId = this.getAttribute('data-tab');

                tabButtons.forEach(function (b) { b.classList.remove('active'); });
                tabPanes.forEach(function (p) { p.classList.remove('active'); });

                this.classList.add('active');
                const targetPane = document.getElementById(targetId);
                if (targetPane) {
                    targetPane.classList.add('active');
                }
            });
        });
    });
})();
