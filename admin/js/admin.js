/**
 * Comment Control Admin Tabs JS
 *
 * @package Comment_Control
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // 1. Tab Navigation
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

        // 2. Floating Auto-Dismiss Toast Alert
        const toast = document.getElementById('cctl_toast_alert');
        if (toast) {
            const dismissToast = function () {
                toast.classList.add('cctl-toast-hiding');
                setTimeout(function () {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 380);
            };

            // Auto-hide after 3.5 seconds
            const autoTimer = setTimeout(dismissToast, 3500);

            // Manual close button
            const closeBtn = document.getElementById('cctl_toast_close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    clearTimeout(autoTimer);
                    dismissToast();
                });
            }

            // Clean URL query parameter (?settings-updated=true) without page reload
            if (window.history && window.history.replaceState) {
                const cleanUrl = window.location.protocol + '//' + window.location.host + window.location.pathname + window.location.search.replace(/([&?])settings-updated=true(&|$)/, '$1').replace(/[?&]$/, '');
                window.history.replaceState({ path: cleanUrl }, '', cleanUrl);
            }
        }
    });
})();
