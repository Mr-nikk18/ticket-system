(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        window.setTimeout(function () {
            var alerts = document.querySelectorAll('.js-auto-dismiss-alert');

            alerts.forEach(function (alertNode) {
                alertNode.style.transition = 'opacity 0.35s ease';
                alertNode.style.opacity = '0';

                window.setTimeout(function () {
                    if (alertNode.parentNode) {
                        alertNode.parentNode.removeChild(alertNode);
                    }
                }, 360);
            });
        }, 4200);
    });
})();
