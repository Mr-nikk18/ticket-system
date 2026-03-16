(function (window, $) {
    var boardConfig = window.KanbanBoardConfig || {};
    var currentUserId = parseInt(boardConfig.currentUserId || 0, 10);
    var isRoleTwo = !!boardConfig.isRoleTwo;

    function isMoveAllowed(fromStatus, toStatus, canResolve, isDraggable) {
        if (!isDraggable) {
            return false;
        }

        if (fromStatus === 1 && (toStatus === 4 || toStatus === 3)) {
            return false;
        }

        if (fromStatus === 2 && toStatus === 1) {
            return false;
        }

        if (fromStatus === 3 && toStatus === 4) {
            return false;
        }

        if (toStatus === 3 && canResolve !== 1) {
            return false;
        }

        return true;
    }

    function clearDropState(selector) {
        $(selector).removeClass('kanban-drop-allowed kanban-drop-blocked');
    }

    function markDropTargets(selector, fromStatus, canResolve, isDraggable) {
        clearDropState(selector);

        $(selector).each(function () {
            var toStatus = parseInt($(this).data('status'), 10);
            var allowed = isMoveAllowed(fromStatus, toStatus, canResolve, isDraggable);
            $(this).addClass(allowed ? 'kanban-drop-allowed' : 'kanban-drop-blocked');
        });
    }

    function canDragTicket(ticket, teamBoard) {
        if (teamBoard || boardConfig.departmentId != 2) {
            return 0;
        }

        var statusId = parseInt(ticket.status_id, 10);
        var assignedEngineerId = parseInt(ticket.assigned_engineer_id || 0, 10);
        var isOpenUnassigned = (statusId === 1 && (!ticket.assigned_engineer_id || assignedEngineerId === 0));
        var isOwnAssigned = assignedEngineerId === currentUserId;

        if (boardConfig.roleId == 2) {
            if (isOpenUnassigned) {
                return 1;
            }

            if ((statusId === 2 || statusId === 3) && isOwnAssigned) {
                return 1;
            }

            return 0;
        }

        if (statusId === 2 || statusId === 3) {
            return 1;
        }

        if (isOpenUnassigned) {
            return 1;
        }

        return isOwnAssigned ? 1 : 0;
    }

    window.generateTicketCard = function (ticket, options) {
        options = options || {};
        var total = ticket.tasks.length;
        var completed = 0;

        ticket.tasks.forEach(function (task) {
            if (task.is_completed == 1) {
                completed++;
            }
        });

        var canResolve = parseInt(ticket.can_resolve || 0, 10);
        var canDrag = canDragTicket(ticket, options.teamBoard === true);
        var unreadCommentCount = parseInt(ticket.unread_comment_count || 0, 10);
        var handledBy = ticket.handled_by_name ? ticket.handled_by_name : 'Open Queue';

        return '\
        <div class="ticket-card mb-2 p-2 border bg-white ' + (canDrag ? 'is-draggable' : 'is-locked') + '"\
             data-id="' + ticket.ticket_id + '"\
             data-can-resolve="' + canResolve + '"\
             data-draggable="' + canDrag + '">\
            <h6>Ticket ID: #' + ticket.ticket_id + '</h6>\
            <strong>Owner:</strong> ' + (ticket.owner_name || 'N/A') + '<br>\
            <strong>Title:</strong>\
            <div>' + ticket.title + '</div>\
            <div class="kanban-card-meta">\
              <div class="text-muted">' + completed + ' / ' + total + ' Completed</div>\
              ' + (unreadCommentCount > 0 ? '<span class="kanban-comment-indicator"><i class="far fa-bell"></i> ' + unreadCommentCount + '</span>' : '') + '\
            </div>\
            <div class="mt-2 text-muted"><strong>Handled By:</strong> ' + handledBy + '</div>\
        </div>';
    };

    window.initKanbanSortable = function (selector) {
        selector = selector || '.kanban-column';

        if ($(selector).hasClass('ui-sortable')) {
            $(selector).sortable('destroy');
        }

        if (boardConfig.departmentId == 2 && selector === '.kanban-column') {
            $(selector).sortable({
                connectWith: selector,
                items: '.ticket-card.is-draggable',
                placeholder: 'ui-state-highlight',
                forcePlaceholderSize: true,
                tolerance: 'pointer',
                cursor: 'grabbing',
                opacity: 0.8,
                revert: 150,
                start: function (event, ui) {
                    ui.item.addClass('dragging');
                    ui.item.data('from-status', $(this).data('status'));

                    var fromStatus = parseInt(ui.item.data('from-status'), 10);
                    var canResolve = parseInt(ui.item.attr('data-can-resolve') || ui.item.data('can-resolve') || 0, 10);
                    var isDraggable = parseInt(ui.item.data('draggable'), 10) === 1;

                    markDropTargets(selector, fromStatus, canResolve, isDraggable);
                },
                over: function (event, ui) {
                    var fromStatus = parseInt(ui.item.data('from-status'), 10);
                    var toStatus = parseInt($(this).data('status'), 10);
                    var canResolve = parseInt(ui.item.attr('data-can-resolve') || ui.item.data('can-resolve') || 0, 10);
                    var isDraggable = parseInt(ui.item.data('draggable'), 10) === 1;
                    var isAllowed = isMoveAllowed(fromStatus, toStatus, canResolve, isDraggable);

                    if (!isAllowed) {
                        $(this).find('.ui-sortable-placeholder').hide();
                    }
                },
                change: function () {
                    if ($(this).hasClass('kanban-drop-blocked')) {
                        $(this).find('.ui-sortable-placeholder').hide();
                    }
                },
                out: function () {
                    $(this).find('.ui-sortable-placeholder').show();
                },
                stop: function (event, ui) {
                    ui.item.removeClass('dragging');
                    clearDropState(selector);
                },
                update: function (event, ui) {
                    var newColumn = $(this);
                    if (ui.item.parent()[0] !== newColumn[0]) {
                        return;
                    }

                    var toStatus = parseInt(newColumn.data('status'), 10);
                    var fromStatus = parseInt(ui.item.data('from-status'), 10);
                    var isDraggable = parseInt(ui.item.data('draggable'), 10) === 1;
                    var canResolve = parseInt(ui.item.attr('data-can-resolve') || ui.item.data('can-resolve') || 0, 10);
                    var allowed = isMoveAllowed(fromStatus, toStatus, canResolve, isDraggable);

                    if (!allowed) {
                        if (ui.sender) {
                            $(ui.sender).sortable('cancel');
                        } else {
                            $(this).sortable('cancel');
                        }
                        return;
                    }

                    var order = [];
                    newColumn.children('.ticket-card').each(function (index) {
                        order.push({
                            ticket_id: $(this).data('id'),
                            board_position: index
                        });
                    });

                    $.ajax({
                        url: boardConfig.updateBoardUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            status_id: toStatus,
                            order: JSON.stringify(order)
                        },
                        success: function (response) {
                            if (!response || response.success !== true) {
                                if (ui.sender) {
                                    $(ui.sender).sortable('cancel');
                                } else {
                                    newColumn.sortable('cancel');
                                }
                            } else {
                                loadKanbanTickets($('#kanbanFilter').val() || 'assigned');
                            }
                        },
                        error: function () {
                            if (ui.sender) {
                                $(ui.sender).sortable('cancel');
                            } else {
                                newColumn.sortable('cancel');
                            }
                        }
                    });
                }
            });
        } else if (selector === '.kanban-column') {
            $(selector).sortable({
                connectWith: selector,
                placeholder: 'ui-state-highlight',
                tolerance: 'pointer',
                start: function (event, ui) {
                    ui.item.data('from-status', $(this).data('status'));
                },
                update: function (event, ui) {
                    if (ui.item.parent()[0] !== $(this)[0]) {
                        return;
                    }

                    var fromStatus = parseInt(ui.item.data('from-status'), 10);
                    var toStatus = parseInt($(this).data('status'), 10);
                    var ticketId = ui.item.data('id');

                    if (fromStatus === 4 && toStatus === 1) {
                        $.ajax({
                            url: boardConfig.reopenUrl,
                            type: 'POST',
                            dataType: 'json',
                            data: { ticket_id: ticketId },
                            success: function (response) {
                                loadKanbanTickets($('#kanbanFilter').val() || 'assigned');
                            },
                            error: function () {
                                loadKanbanTickets($('#kanbanFilter').val() || 'assigned');
                            }
                        });
                    } else {
                        $(this).sortable('cancel');
                    }
                }
            });
        }

    };

    window.loadKanbanTickets = function (filter) {
        $.ajax({
            url: boardConfig.boardTicketsUrl,
            type: 'GET',
            data: { filter: filter || $('#kanbanFilter').val() || 'assigned' },
            dataType: 'json',
            success: function (response) {
                $('.kanban-column').empty();
                response.forEach(function (ticket) {
                    $('#column' + ticket.status_id).append(generateTicketCard(ticket));
                });
                initKanbanSortable('.kanban-column');
            }
        });
    };

    function loadTeamKanbanTickets() {
        if (!isRoleTwo) {
            return;
        }

        $.ajax({
            url: boardConfig.boardTicketsUrl,
            type: 'GET',
            data: { filter: 'all', board: 'team' },
            dataType: 'json',
            success: function (response) {
                $('.kanban-team-column').empty();
                response.forEach(function (ticket) {
                    $('#teamColumn' + ticket.status_id).append(generateTicketCard(ticket, { teamBoard: true }));
                });
            }
        });
    }

    $(function () {
        loadKanbanTickets($('#kanbanFilter').val() || 'assigned');
        loadTeamKanbanTickets();

        $('#refreshBoard').on('click', function () {
            var $button = $(this);
            var originalHtml = $button.html();

            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            loadKanbanTickets($('#kanbanFilter').val() || 'assigned');

            if (isRoleTwo && $('#kanbanBoardMode').val() === 'team') {
                loadTeamKanbanTickets();
            }

            setTimeout(function () {
                $button.prop('disabled', false).html(originalHtml);
            }, 400);
        });

        $('#kanbanFilter').on('change', function () {
            loadKanbanTickets($(this).val() || 'assigned');
        });

        $('#kanbanBoardMode').on('change', function () {
            var mode = $(this).val();
            if (mode === 'team') {
                $('#workflowBoardWrap').hide();
                $('#teamBoardWrap').show();
                $('#kanbanFilter').prop('disabled', true);
                return;
            }

            $('#teamBoardWrap').hide();
            $('#workflowBoardWrap').show();
            $('#kanbanFilter').prop('disabled', false);
        });
    });
})(window, jQuery);

