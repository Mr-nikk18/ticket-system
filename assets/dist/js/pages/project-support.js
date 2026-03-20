(function ($) {
    'use strict';

    var config = window.ProjectSupportConfig || {};
    var loadedTicket = null;

    function setAiStatus(message, level) {
        var $status = $('#supportAiStatus');
        var alertClass = 'alert-light';

        if (level === 'success') {
            alertClass = 'alert-success';
        } else if (level === 'error') {
            alertClass = 'alert-danger';
        } else if (level === 'warning') {
            alertClass = 'alert-warning';
        } else if (level === 'working') {
            alertClass = 'alert-info';
        }

        $status
            .removeClass('alert-light alert-success alert-danger alert-warning alert-info')
            .addClass(alertClass)
            .text(message);
    }

    function renderTicketSnapshot(ticket) {
        var $target = $('#supportTicketSnapshot');
        var tasks = Array.isArray(ticket.tasks) ? ticket.tasks : [];
        var taskItems = tasks.length
            ? '<ul class="project-support-ticket-list">' + tasks.map(function (task) {
                var suffix = Number(task.is_completed || 0) === 1 ? ' [completed]' : ' [pending]';
                return '<li>' + escapeHtml(task.task_title || '') + suffix + '</li>';
            }).join('') + '</ul>'
            : '<div class="text-muted">No tasks attached.</div>';

        $target.html(
            '<dl class="row project-support-ticket-grid mb-0">' +
                '<dt class="col-sm-4">Ticket</dt><dd class="col-sm-8">#' + Number(ticket.ticket_id || 0) + '</dd>' +
                '<dt class="col-sm-4">Title</dt><dd class="col-sm-8">' + escapeHtml(ticket.title || 'Untitled') + '</dd>' +
                '<dt class="col-sm-4">Department</dt><dd class="col-sm-8">' + escapeHtml(ticket.department_name || '-') + '</dd>' +
                '<dt class="col-sm-4">Assigned</dt><dd class="col-sm-8">' + escapeHtml(ticket.assigned_engineer_name || 'Unassigned') + '</dd>' +
                '<dt class="col-sm-4">Description</dt><dd class="col-sm-8">' + escapeHtml(ticket.description || '-') + '</dd>' +
                '<dt class="col-sm-4">Tasks</dt><dd class="col-sm-8">' + taskItems + '</dd>' +
                '<dt class="col-sm-4">Share URL</dt><dd class="col-sm-8"><a href="' + escapeAttribute(ticket.share_url || '#') + '" target="_blank" rel="noopener">' + escapeHtml(ticket.share_url || '-') + '</a></dd>' +
            '</dl>'
        );
    }

    function applyTicketToForms(ticket, mode) {
        loadedTicket = ticket;
        $('#supportTicketState').removeClass('badge-light').addClass('badge-primary').text('Ticket #' + Number(ticket.ticket_id || 0) + ' loaded');
        renderTicketSnapshot(ticket);

        if (mode !== 'qr-only') {
            $('#supportTicketId').val(ticket.ticket_id || '');
            $('#supportTitle').val(ticket.title || '');
            $('#supportDescription').val(ticket.description || '');

            if (Array.isArray(ticket.tasks) && ticket.tasks.length) {
                var taskNotes = ticket.tasks.map(function (task) {
                    return '- ' + (task.task_title || '') + (Number(task.is_completed || 0) === 1 ? ' [completed]' : ' [pending]');
                }).join('\n');
                $('#supportContext').val('Ticket tasks:\n' + taskNotes + '\n\nShare URL:\n' + (ticket.share_url || ''));
            } else if (ticket.share_url) {
                $('#supportContext').val('Share URL:\n' + ticket.share_url);
            }
        }

        if (mode !== 'ai-only') {
            $('#qrTicketId').val(ticket.ticket_id || '');
            $('#qrPayload').val(ticket.share_url || '');
            $('#qrResolvedPayload').val(ticket.share_url || '');
            $('#openQrPayload').attr('href', ticket.share_url || '#');
        }
    }

    function loadTicket(ticketId, mode) {
        if (!ticketId) {
            setAiStatus('Enter a ticket ID first.', 'warning');
            return;
        }

        setAiStatus('Loading ticket context...', 'working');

        $.ajax({
            url: config.ticketSnapshotUrl,
            type: 'GET',
            dataType: 'json',
            data: { ticket_id: ticketId },
            success: function (response) {
                if (!response || response.success !== true || !response.ticket) {
                    setAiStatus((response && response.message) ? response.message : 'Unable to load ticket.', 'error');
                    return;
                }

                applyTicketToForms(response.ticket, mode);
                setAiStatus('Ticket context loaded into the workspace.', 'success');
            },
            error: function (xhr) {
                var message = 'Unable to load ticket context.';
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                setAiStatus(message, 'error');
            }
        });
    }

    function buildQrUrl(payload, size) {
        var template = String(config.qrImageTemplate || '');
        if (!template) {
            return '';
        }

        return template
            .replace(/\{size\}/g, encodeURIComponent(String(size)))
            .replace(/\{data\}/g, encodeURIComponent(payload));
    }

    function generateQrPreview() {
        var payload = $.trim($('#qrPayload').val());
        var size = $('#qrSize').val();
        var qrUrl = buildQrUrl(payload, size);

        if (!payload) {
            $('#qrState').removeClass('badge-primary').addClass('badge-light').text('Waiting for content');
            $('#qrPreviewImage').addClass('d-none').attr('src', '');
            $('#qrPreviewEmpty').removeClass('d-none').text('Enter a ticket or paste a URL to generate the next QR code.');
            $('#qrResolvedPayload').val('');
            $('#openQrPayload').attr('href', '#');
            return;
        }

        if (!qrUrl) {
            $('#qrState').removeClass('badge-primary').addClass('badge-warning').text('QR template missing');
            $('#qrPreviewImage').addClass('d-none').attr('src', '');
            $('#qrPreviewEmpty').removeClass('d-none').text('QR generation template is not configured.');
            return;
        }

        $('#qrState').removeClass('badge-light badge-warning').addClass('badge-primary').text('QR ready');
        $('#qrPreviewEmpty').addClass('d-none');
        $('#qrPreviewImage').removeClass('d-none').attr('src', qrUrl);
        $('#qrResolvedPayload').val(payload);
        $('#openQrPayload').attr('href', payload);
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeAttribute(value) {
        return escapeHtml(value);
    }

    function copyText(value, successMessage) {
        if (!value) {
            return;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(function () {
                setAiStatus(successMessage, 'success');
            });
            return;
        }

        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(value).trigger('select');
        document.execCommand('copy');
        $temp.remove();
        setAiStatus(successMessage, 'success');
    }

    $(function () {
        $('#loadTicketForAi').on('click', function () {
            loadTicket($('#supportTicketId').val(), 'ai-only');
        });

        $('#loadTicketForQr').on('click', function () {
            loadTicket($('#qrTicketId').val(), 'qr-only');
        });

        $('#projectSupportAiForm').on('submit', function (event) {
            event.preventDefault();

            if (!config.aiEnabled) {
                setAiStatus('AI support is disabled until OPENAI_API_KEY is configured.', 'warning');
                return;
            }

            setAiStatus('Generating AI support output...', 'working');
            $('#generateSupportDraft').prop('disabled', true).text('Generating...');

            $.ajax({
                url: config.aiAssistUrl,
                type: 'POST',
                dataType: 'json',
                data: $(this).serialize(),
                success: function (response) {
                    if (!response || response.success !== true) {
                        setAiStatus((response && response.message) ? response.message : 'Unable to generate AI output.', 'error');
                        return;
                    }

                    $('#supportAiOutput').val(response.content || '');
                    setAiStatus('AI output ready on ' + (response.model || config.aiModel || 'configured model') + '.', 'success');
                },
                error: function (xhr) {
                    var message = 'Unable to generate AI output.';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    setAiStatus(message, 'error');
                },
                complete: function () {
                    $('#generateSupportDraft').prop('disabled', false).text('Generate AI Draft');
                }
            });
        });

        $('#clearSupportForm').on('click', function () {
            $('#projectSupportAiForm')[0].reset();
            $('#supportAiOutput').val('');
            $('#supportTicketSnapshot').html('Load a visible ticket to pull project context into the assistant and QR generator.');
            $('#supportTicketState').removeClass('badge-primary').addClass('badge-light').text('No ticket loaded');
            loadedTicket = null;
            setAiStatus('Ready for a prompt.', 'info');
        });

        $('#copyAiOutput').on('click', function () {
            copyText($('#supportAiOutput').val(), 'AI output copied to clipboard.');
        });

        $('#generateQrCode').on('click', function () {
            generateQrPreview();
            setAiStatus('QR preview updated.', 'success');
        });

        $('#copyQrPayload').on('click', function () {
            copyText($('#qrResolvedPayload').val() || $('#qrPayload').val(), 'QR link copied to clipboard.');
        });

        $('#qrPayload, #qrSize').on('change keyup', function () {
            generateQrPreview();
        });

        if (config.dashboardUrl) {
            $('#openQrPayload').attr('href', '#');
        }

        setAiStatus(config.aiEnabled ? 'Ready for a prompt.' : 'AI support is waiting for OPENAI_API_KEY.', config.aiEnabled ? 'info' : 'warning');
    });
})(jQuery);
