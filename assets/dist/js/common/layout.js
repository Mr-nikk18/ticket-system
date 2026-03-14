(function (window, $) {
    var config = window.AppConfig || {};

    if (!window.TRSAutoInit) {
        window.TRSAutoInit = {};
    }

    function initFlashMessages() {
        setTimeout(function () {
            document.querySelectorAll('.flash-msg').forEach(function (el) {
                el.classList.remove('show');
                el.classList.add('hide');
            });
        }, 5000);
    }

    function initInactivityTimer() {
        if (!config.forceLogoutUrl || !config.renewUrl || window.TRSAutoInit.inactivity) {
            return;
        }

        window.TRSAutoInit.inactivity = true;

        var inactivityLimit = 600;
        var warningTime = 100;
        var countdownInterval;
        var inactivityTimer;
        var secondsPassed = 0;

        function startInactivityTimer() {
            clearInterval(inactivityTimer);
            clearInterval(countdownInterval);
            secondsPassed = 0;

            inactivityTimer = setInterval(function () {
                secondsPassed++;

                if (secondsPassed === warningTime) {
                    showWarning();
                }

                if (secondsPassed === inactivityLimit) {
                    window.location.href = config.forceLogoutUrl;
                }
            }, 1000);
        }

        function showWarning() {
            var countdown = inactivityLimit - warningTime;

            if (document.getElementById('sessionModal')) {
                return;
            }

            document.body.insertAdjacentHTML('beforeend', '\
                <div id="sessionModal" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;justify-content:center;align-items:center;z-index:9999;">\
                    <div style="background:#fff;padding:20px 30px;border-radius:8px;text-align:center;width:300px;">\
                        <h4>Session Expiring</h4>\
                        <p>Session will expire in <span id="countdown">' + countdown + '</span> seconds.</p>\
                        <button id="renewSessionBtn" style="padding:6px 12px;background:#28a745;color:#fff;border:none;border-radius:4px;">Renew Now</button>\
                    </div>\
                </div>');

            countdownInterval = setInterval(function () {
                countdown--;
                var el = document.getElementById('countdown');
                if (el) {
                    el.innerText = countdown;
                }
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                }
            }, 1000);
        }

        $(document).on('click', '#renewSessionBtn', function () {
            fetch(config.renewUrl);
            var modal = document.getElementById('sessionModal');
            if (modal) {
                modal.remove();
            }
            startInactivityTimer();
        });

        document.addEventListener('click', startInactivityTimer);
        document.addEventListener('keypress', startInactivityTimer);
        document.addEventListener('mousemove', startInactivityTimer);

        startInactivityTimer();
    }

    function initDataTables() {
        if ($.fn.DataTable && $('#example1').length) {
            $('#example1').DataTable();
        }
    }

    function initNotifications() {
        if (!config.notificationsUrl || !config.markNotificationReadUrl || window.TRSAutoInit.notifications) {
            return;
        }

        window.TRSAutoInit.notifications = true;
        var notificationRequest = null;
        var $dropdown = $('#notificationDropdown');
        var $bell = $('#notificationBellBtn');
        var $menu = $('#notificationDropdownMenu');
        var $list = $('#notificationList');
        var $preview = $('#notificationPreview');
        var $previewBody = $('#notificationPreviewBody');

        if (!$dropdown.length || !$bell.length || !$menu.length || !$list.length) {
            return;
        }

        function openNotificationDropdown() {
            $dropdown.addClass('show');
            $menu.addClass('show');
            $bell.attr('aria-expanded', 'true');
        }

        function closeNotificationDropdown() {
            $dropdown.removeClass('show');
            $menu.removeClass('show');
            $bell.attr('aria-expanded', 'false');
            $preview.hide();
            $previewBody.empty();
            $list.show();
        }

        function showNotificationList() {
            $preview.hide();
            $previewBody.empty();
            $list.show();
            openNotificationDropdown();
        }

        function updateNotificationCount(count) {
            var badge = $('#notificationBadge');
            var header = $('#notificationHeader');
            var safeCount = parseInt(count || 0, 10);

            if (safeCount > 0) {
                badge.text(safeCount).show();
                header.text(safeCount + ' Notifications');
            } else {
                badge.hide();
                header.text('0 Notifications');
            }
        }

        function getNotificationMeta(item) {
            if (item.notification_type === 'rating') {
                return {
                    targetUrl: config.ticketBoardUrl,
                    iconClass: 'fa-star text-warning',
                    title: item.title || 'Ticket rating',
                    tag: 'Rating'
                };
            }

            if (item.notification_type && item.notification_type !== 'ticket') {
                return {
                    targetUrl: config.scheduleUrl,
                    iconClass: 'fa-calendar-check text-info',
                    title: item.schedule_name || 'Schedule notification',
                    tag: 'Schedule'
                };
            }

            if (item.task_id) {
                return {
                    targetUrl: config.ticketBoardUrl,
                    iconClass: 'fa-bell text-warning',
                    title: item.title || 'Ticket comment',
                    tag: 'Comment'
                };
            }

            return {
                targetUrl: config.ticketBoardUrl,
                iconClass: 'fa-ticket-alt text-primary',
                title: item.title || 'Ticket notification',
                tag: 'Ticket'
            };
        }

        function loadNotifications() {
            if (notificationRequest && notificationRequest.readyState !== 4) {
                return;
            }

            notificationRequest = $.ajax({
                url: config.notificationsUrl,
                type: 'GET',
                dataType: 'json',
                success: function (res) {
                    var previewOpen = $preview.is(':visible');
                    if (!previewOpen) {
                        $list.empty();
                    }

                    if (res.count > 0) {
                        updateNotificationCount(res.count);

                        if (!previewOpen) {
                            res.notifications.forEach(function (item) {
                                var meta = getNotificationMeta(item);

                                $list.append(
                                    '<a href="' + meta.targetUrl + '" class="dropdown-item notification-item" data-id="' + item.id + '" data-task-id="' + (item.task_id || '') + '" data-ticket-id="' + (item.ticket_id || '') + '" data-notification-type="' + (item.notification_type || '') + '" data-title="' + meta.title + '">' +
                                    '<div class="d-flex align-items-start">' +
                                    '<div class="mr-2 mt-1"><i class="fas ' + meta.iconClass + '"></i></div>' +
                                    '<div class="flex-grow-1">' +
                                    '<div class="d-flex justify-content-between align-items-start">' +
                                    '<strong class="text-dark">' + meta.title + '</strong>' +
                                    '<span class="badge badge-light">' + meta.tag + '</span>' +
                                    '</div>' +
                                    '<div class="text-muted small mt-1">' + item.message + '</div>' +
                                    '<div class="text-muted text-xs mt-1">' + item.created_at + '</div>' +
                                    '</div>' +
                                    '</div>' +
                                    '</a><div class="dropdown-divider"></div>'
                                );
                            });
                        }
                    } else {
                        updateNotificationCount(0);
                        if (!previewOpen) {
                            $list.html('<span class="dropdown-item text-muted text-center">No new notifications</span>');
                        }
                    }
                }
            });
        }

        $bell.on('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            if ($menu.hasClass('show')) {
                closeNotificationDropdown();
                return;
            }

            showNotificationList();
        });

        $menu.on('click', function (event) {
            event.stopPropagation();
        });

        $(document).on('click', function () {
            if ($menu.hasClass('show')) {
                closeNotificationDropdown();
            }
        });

        $(document).on('click', '.notification-item', function (event) {
            var $item = $(this);
            var notificationType = $item.data('notification-type');
            var ticketId = parseInt($item.data('ticket-id') || 0, 10);
            var taskId = parseInt($item.data('task-id') || 0, 10);

            event.stopPropagation();

            if (notificationType === 'rating' && ticketId > 0 && typeof window.loadTicketDetails === 'function') {
                event.preventDefault();

                // Mark notification read and open ticket modal
                $.post(config.markNotificationReadUrl, {
                    id: $item.data('id')
                }).always(function () {
                    loadTicketDetails(ticketId);
                    loadNotifications();
                });

                return;
            }

            if (taskId > 0 && config.taskCommentsUrl) {
                event.preventDefault();

                $('#notificationPreviewTitle').text($item.data('title') || 'Comment Preview');
                $previewBody.html('<div class="text-muted text-center py-2">Loading...</div>');
                $list.hide();
                $preview.show();
                openNotificationDropdown();

                $.post(config.taskCommentsUrl, {
                    task_id: taskId
                }, function (response) {
                    var data = response;

                    if (typeof response === 'string') {
                        try {
                            data = JSON.parse(response);
                        } catch (error) {
                            data = null;
                        }
                    }

                    if (!data || data.status === false) {
                        $previewBody.html((data && data.html) ? data.html : '<div class="text-danger text-center py-2">Unable to load comments.</div>');
                        return;
                    }

                    $previewBody.html(data.html || '<div class="text-muted text-center py-2">No comments found.</div>');
                    updateNotificationCount(data.unread_count);
                    loadNotifications();
                }).fail(function () {
                    $previewBody.html('<div class="text-danger text-center py-2">Unable to load comments.</div>');
                });
                return;
            }

            $.post(config.markNotificationReadUrl, {
                id: $item.data('id')
            }).always(function () {
                loadNotifications();
            });
        });

        $(document).on('click', '#notificationPreviewBack', function (event) {
            event.preventDefault();
            event.stopPropagation();
            showNotificationList();
        });

        loadNotifications();
        setInterval(loadNotifications, 5000);
    }

    $(function () {
        initFlashMessages();
        initInactivityTimer();
        initDataTables();
        initNotifications();
    });
})(window, jQuery);
