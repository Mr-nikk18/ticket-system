(function (window, $) {
    var nativeAlert = window.alert;

    function suppressBoardAlerts() {
        window.alert = function () {
            return;
        };
    }

    function restoreAlert() {
        window.alert = nativeAlert;
    }

    function getCurrentTicketCard() {
        if (!window.currentTicketId) {
            return $();
        }

        return $('.ticket-card[data-id="' + window.currentTicketId + '"]');
    }

    function syncBoardCardProgress() {
        var $card = getCurrentTicketCard();
        if (!$card.length) {
            return;
        }

        var total = $('#taskSection .task-checkbox').length;
        var completed = $('#taskSection .task-checkbox:checked').length;

        $card.find('.kanban-card-meta .text-muted').first().text(completed + ' / ' + total + ' Completed');
    }

    function enableTaskCheckboxes() {
        $('#ticketDetailContent .task-checkbox').prop('disabled', false);
    }

    function syncTaskCommentVisibility($scope) {
        ($scope || $('#ticketDetailContent')).find('.task-card').each(function () {
            var $taskCard = $(this);
            var isCompleted = $taskCard.find('.task-checkbox').is(':checked');

            $taskCard.children('.input-group').addClass('task-comment-composer');

            $taskCard.toggleClass('task-completed', isCompleted);
            $taskCard.find('.task-comment-composer').toggle(!isCompleted);
        });

        syncBoardCardProgress();
    }

    function enhanceSummaryTasks() {
        $('#ticketDetailContent h6').filter(function () {
            return $(this).text().trim() === 'Tasks';
        }).each(function () {
            $(this).siblings('.ticket-summary-task').remove();

            $(this).nextAll('div').each(function () {
                var $item = $(this);

                if ($item.hasClass('text-muted')) {
                    return false;
                }

                if ($item.attr('id') || $item.hasClass('progress') || $item.find('#taskSection').length) {
                    return false;
                }

                var text = $item.text().replace(/^[^A-Za-z0-9]+/, '').trim();
                if (!text) {
                    $item.remove();
                    return;
                }

                $item
                    .addClass('ticket-summary-task')
                    .html('<i class="far fa-check-circle"></i><span></span>')
                    .find('span')
                    .text(text);
            });
        });
    }

    function enhanceTicketModal() {
        enableTaskCheckboxes();
        enhanceSummaryTasks();
        syncTaskCommentVisibility($('#ticketDetailContent'));
    }

    suppressBoardAlerts();

    $(document).on('click', '.ticket-card', function () {
        setTimeout(enhanceTicketModal, 250);
    });

    $('#ticketModal').on('shown.bs.modal', function () {
        enhanceTicketModal();
    });

    $(document).on('change', '.task-checkbox', function () {
        var $taskCard = $(this).closest('.task-card');
        $(this).data('previous-checked', !$(this).is(':checked'));
        syncTaskCommentVisibility($taskCard);
    });

    $(document).ajaxSuccess(function (event, xhr, settings) {
        if (!settings || !settings.url) {
            return;
        }

        if (settings.url.indexOf('update_task_status') !== -1) {
            syncBoardCardProgress();
        }
    });

    $(document).ajaxError(function (event, xhr, settings) {
        if (!settings || !settings.url || settings.url.indexOf('update_task_status') === -1) {
            return;
        }

        var requestData = settings.data || '';
        var match = /task_id=(\d+)/.exec(requestData);
        if (!match) {
            return;
        }

        var taskId = match[1];
        var $checkbox = $('.task-checkbox[data-id="' + taskId + '"]');
        if (!$checkbox.length) {
            return;
        }

        var previousChecked = !!$checkbox.data('previous-checked');
        $checkbox.prop('checked', previousChecked);
        syncTaskCommentVisibility($checkbox.closest('.task-card'));
    });

    $(document).ajaxComplete(function (event, xhr, settings) {
        if (!settings || !settings.url) {
            return;
        }

        if (
            settings.url.indexOf('get_ticket_details') !== -1 ||
            settings.url.indexOf('add_task') !== -1 ||
            settings.url.indexOf('update_task_status') !== -1
        ) {
            enhanceTicketModal();
        }

        if (settings.url.indexOf('update_task_status') !== -1) {
            var response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
            if (response && response.success === false) {
                var requestData = settings.data || '';
                var match = /task_id=(\d+)/.exec(requestData);
                if (match) {
                    var taskId = match[1];
                    var $checkbox = $('.task-checkbox[data-id="' + taskId + '"]');
                    var previousChecked = !!$checkbox.data('previous-checked');
                    $checkbox.prop('checked', previousChecked);
                    syncTaskCommentVisibility($checkbox.closest('.task-card'));
                }
            }
        }
    });

    $(window).on('unload', function () {
        restoreAlert();
    });
})(window, jQuery);
