(function ($) {
    'use strict';

    var parsedRows = [];
    var config = window.AssetBulkUploadConfig || {};

    function resolveDepartmentId(row) {
        var directId = String(row.department_id == null ? '' : row.department_id).trim();
        var departmentName = String(row.department_name == null ? '' : row.department_name).trim().toLowerCase();
        var departmentMap = config.departmentMap || {};

        if (directId) {
            return directId;
        }

        if (!departmentName) {
            return '';
        }

        return String(departmentMap[departmentName] || '');
    }

    function buildGeneratedQrCode(row) {
        var departmentId = resolveDepartmentId(row);
        var seed = $.trim(row.serial_number || row.asset_name || 'ASSET')
            .toUpperCase()
            .replace(/[^A-Z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');

        if (!seed) {
            seed = 'ASSET';
        }

        return departmentId
            ? 'TRS-ASSET-D' + departmentId + '-' + seed
            : 'TRS-ASSET-' + seed;
    }

    function buildTicketLink(routeKey) {
        var base = String(config.assetQrBaseUrl || '').replace(/\/+$/, '');
        if (!base || !routeKey) {
            return '';
        }

        return base + '/' + encodeURIComponent(routeKey);
    }

    function getHeaders(rows) {
        if (!rows.length) {
            return [];
        }

        var headers = [];
        rows.forEach(function (row) {
            Object.keys(row || {}).forEach(function (key) {
                if (headers.indexOf(key) === -1) {
                    headers.push(key);
                }
            });
        });

        return headers;
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderPreview(rows) {
        var headers = getHeaders(rows);
        var previewRows = rows.slice(0, 25);
        var headerHtml = headers.map(function (header) {
            return '<th>' + escapeHtml(header) + '</th>';
        }).join('');
        var bodyHtml = previewRows.map(function (row) {
            return '<tr>' + headers.map(function (header) {
                return '<td>' + escapeHtml(row[header]) + '</td>';
            }).join('') + '</tr>';
        }).join('');

        $('#assetImportRowCount').text(rows.length);
        $('#assetImportColumnCount').text(headers.length);
        $('#assetImportPreviewCount').text(previewRows.length);

        if (!rows.length) {
            $('#assetImportEmpty').removeClass('d-none');
            $('#assetImportPreviewWrap').addClass('d-none');
            $('#assetImportRowsJson').val('');
            $('#submitAssetBulkUpload').prop('disabled', true);
            return;
        }

        $('#assetImportEmpty').addClass('d-none');
        $('#assetImportPreviewWrap').removeClass('d-none');
        $('#assetImportPreviewHead').html('<tr>' + headerHtml + '</tr>');
        $('#assetImportPreviewBody').html(bodyHtml);
        $('#assetImportRowsJson').val(JSON.stringify(rows));
        $('#submitAssetBulkUpload').prop('disabled', false);
    }

    function normalizeRows(rows) {
        return rows.map(function (row) {
            var normalized = {};
            var hasMeaningfulValue = false;

            Object.keys(row || {}).forEach(function (key) {
                var cleanKey = String(key).trim();
                normalized[cleanKey] = typeof row[key] === 'string'
                    ? row[key].trim()
                    : row[key];

                if (String(normalized[cleanKey] == null ? '' : normalized[cleanKey]).trim() !== '') {
                    hasMeaningfulValue = true;
                }
            });

            delete normalized.qr_code;
            delete normalized.qr_image;
            delete normalized.qr_image_path;
            delete normalized.generated_qr_code;
            delete normalized.ticket_link;
            delete normalized.scan_url;

            if (!hasMeaningfulValue) {
                return null;
            }

            var generatedQrCode = buildGeneratedQrCode(normalized);
            normalized.generated_qr_code = generatedQrCode;
            normalized.ticket_link = buildTicketLink($.trim(normalized.serial_number || generatedQrCode));

            return normalized;
        }).filter(function (row) {
            return !!row;
        });
    }

    function parseSpreadsheet(file) {
        if (!file) {
            renderPreview([]);
            return;
        }

        var reader = new FileReader();
        reader.onload = function (event) {
            var workbook = XLSX.read(event.target.result, { type: 'array' });
            var firstSheetName = workbook.SheetNames[0];
            var sheet = workbook.Sheets[firstSheetName];
            var rows = normalizeRows(XLSX.utils.sheet_to_json(sheet, { defval: '' }));

            parsedRows = rows;
            renderPreview(rows);
        };

        reader.readAsArrayBuffer(file);
    }

    $(function () {
        $('#assetSpreadsheetFile').on('change', function (event) {
            parseSpreadsheet((event.target.files || [])[0]);
        });

        $('#assetBulkForm').on('submit', function (event) {
            if (!parsedRows.length) {
                event.preventDefault();
                $('#assetImportEmpty').removeClass('d-none').text('Select an Excel or CSV file first, then preview it before upload.');
            }
        });

        renderPreview([]);
    });
})(jQuery);
