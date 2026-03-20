(function ($) {
    'use strict';

    var config = window.AssetCreateConfig || {};
    var nextSerialRequest = null;
    var previewTimer = null;

    function resetPreview(message) {
        $('#assetGeneratedQrCodeDisplay, #assetGeneratedQrCodeInput').val('');
        $('#assetQrPayload').val('');
        $('#assetPreviewSerialLabel').text('Serial');
        $('#assetQrPreviewImage').addClass('d-none').attr('src', '');
        $('#assetQrPreviewEmpty').removeClass('d-none').text(message || 'Add the serial number and department to preview the QR.');
    }

    function sanitizeQrSeed(value) {
        var seed = String(value || '')
            .trim()
            .toUpperCase()
            .replace(/[^A-Z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');

        return seed || 'ASSET';
    }

    function buildGeneratedQrCode(assetName, serialNumber, departmentId) {
        var seed = sanitizeQrSeed(serialNumber || assetName);
        var prefix = 'TRS-ASSET-';

        if (departmentId) {
            prefix += 'D' + departmentId + '-';
        }

        return prefix + seed;
    }

    function buildScanUrl(serialNumber) {
        var base = String(config.assetQrBaseUrl || '').replace(/\/+$/, '');
        if (!base || !serialNumber) {
            return '';
        }

        return base + '/' + encodeURIComponent(serialNumber);
    }

    function buildPreviewImageUrl(payload, size) {
        if (!config.qrImageTemplate || !payload) {
            return '';
        }

        return String(config.qrImageTemplate)
            .replace('{size}', encodeURIComponent(String(size || 280)))
            .replace('{data}', encodeURIComponent(payload));
    }

    function updatePreviewFromLocalData() {
        var assetName = $.trim($('#assetName').val());
        var serialNumber = $.trim($('#assetSerialNumber').val());
        var departmentId = $.trim($('#assetDepartment').val());
        var size = $('#assetQrSize').val() || 280;

        if (!serialNumber || !departmentId) {
            resetPreview('Add the serial number and department. The QR preview and generated code will appear automatically.');
            return;
        }

        var qrCode = buildGeneratedQrCode(assetName, serialNumber, departmentId);
        var scanUrl = buildScanUrl(serialNumber);
        var previewUrl = buildPreviewImageUrl(scanUrl, size);

        $('#assetGeneratedQrCodeDisplay, #assetGeneratedQrCodeInput').val(qrCode);
        $('#assetQrPayload').val(scanUrl);
        $('#assetPreviewSerialLabel').text(serialNumber);

        if (!previewUrl) {
            $('#assetQrPreviewImage').addClass('d-none').attr('src', '');
            $('#assetQrPreviewEmpty').removeClass('d-none').text('Preview image is not available right now, but the QR code and scan link are ready.');
            return;
        }

        $('#assetQrPreviewEmpty').addClass('d-none');
        $('#assetQrPreviewImage').removeClass('d-none').attr('src', previewUrl);
    }

    function schedulePreviewRefresh() {
        if (previewTimer) {
            window.clearTimeout(previewTimer);
        }

        previewTimer = window.setTimeout(updatePreviewFromLocalData, 120);
    }

    function syncDepartmentFromAssignedUser() {
        var departmentField = $('#assetDepartment');
        var assignedOption = $('#assetAssignedUser option:selected');
        var optionDepartmentId = $.trim(String(assignedOption.data('department-id') || ''));

        if (!departmentField.val() && optionDepartmentId) {
            departmentField.val(optionDepartmentId);
        }
    }

    function serialField() {
        return $('#assetSerialNumber');
    }

    function canAutoFillSerial() {
        var field = serialField();
        return $.trim(field.val()) === '' || field.attr('data-auto-filled') === '1';
    }

    function setAutoFilledSerial(value) {
        var field = serialField();
        field.val(value).attr('data-auto-filled', value ? '1' : '0');
    }

    function loadNextSerialSuggestion() {
        if (!config.nextSerialEndpoint || !canAutoFillSerial()) {
            schedulePreviewRefresh();
            return;
        }

        if (nextSerialRequest && typeof nextSerialRequest.abort === 'function') {
            nextSerialRequest.abort();
        }

        nextSerialRequest = $.getJSON(config.nextSerialEndpoint, {
            department_id: $.trim($('#assetDepartment').val()),
            step: $.trim($('#assetSeriesStep').val()) || 1
        }).done(function (response) {
            if (!response || !response.success || !response.serial_number || !canAutoFillSerial()) {
                schedulePreviewRefresh();
                return;
            }

            setAutoFilledSerial($.trim(String(response.serial_number)));
            schedulePreviewRefresh();
        }).fail(function (_xhr, status) {
            if (status !== 'abort') {
                schedulePreviewRefresh();
            }
        });
    }

    $(function () {
        $('#assetName, #assetQrSize').on('keyup change', schedulePreviewRefresh);

        serialField().on('input', function () {
            if ($.trim($(this).val()) !== '') {
                $(this).attr('data-auto-filled', '0');
            }

            schedulePreviewRefresh();
        });

        serialField().on('blur', function () {
            if ($.trim($(this).val()) === '') {
                loadNextSerialSuggestion();
            }
        });

        $('#assetDepartment, #assetSeriesStep').on('change', function () {
            loadNextSerialSuggestion();
        });

        $('#assetAssignedUser').on('change', function () {
            syncDepartmentFromAssignedUser();
            loadNextSerialSuggestion();
        });

        $('#refreshAssetQrPreview').on('click', function () {
            if ($.trim(serialField().val()) === '') {
                loadNextSerialSuggestion();
                return;
            }

            updatePreviewFromLocalData();
        });

        syncDepartmentFromAssignedUser();

        if ($.trim(serialField().val()) === '') {
            loadNextSerialSuggestion();
        } else {
            schedulePreviewRefresh();
        }
    });
})(jQuery);
