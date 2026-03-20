(function () {
    'use strict';

    function buildPrintableCard(cardNode) {
        var clone = cardNode.cloneNode(true);
        var controls = Array.prototype.slice.call(clone.querySelectorAll('[data-print-control="true"]'));

        controls.forEach(function (control) {
            if (control.parentNode) {
                control.parentNode.removeChild(control);
            }
        });

        return clone.outerHTML;
    }

    function buildPrintDocument(cardsHtml) {
        return '' +
            '<!doctype html>' +
            '<html><head><meta charset="utf-8"><title>QR Print Sheet</title>' +
            '<style>' +
            'body{font-family:Arial,sans-serif;margin:18px;background:#fff;color:#1f2937;}' +
            '.qr-print-sheet{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;}' +
            '.qr-print-card{border:1px solid #dbe5dd;border-radius:16px;padding:12px;background:#fff;break-inside:avoid;}' +
            '.qr-print-card__top{display:grid;grid-template-columns:165px minmax(135px,165px);gap:10px;justify-content:space-between;align-items:start;}' +
            '.qr-print-card__main{display:flex;flex-direction:column;gap:6px;}' +
            '.qr-print-card__serial{display:flex;align-items:center;justify-content:center;min-height:36px;padding:5px 8px;border:1px solid #dbe5dd;border-radius:10px;background:#fff;font-size:18px;font-weight:800;letter-spacing:0.08em;color:#173a2c;}' +
            '.qr-print-card__media{text-align:center;min-height:165px;}' +
            '.qr-print-card__media img{max-width:145px;max-height:145px;border:1px solid #e5e7eb;border-radius:12px;padding:8px;background:#fff;}' +
            '.qr-print-card__side{min-width:0;}' +
            '.qr-print-card__details{display:flex;flex-direction:column;gap:5px;text-align:left;}' +
            '.qr-print-card__detail{border:1px solid #dbe5dd;border-radius:10px;padding:6px 7px;background:#f9fcfa;min-height:58px;}' +
            '.qr-print-card__detail-label{display:block;font-size:8px;font-weight:700;letter-spacing:0.08em;color:#5c6d63;text-transform:uppercase;margin-bottom:2px;}' +
            '.qr-print-card__detail-value{display:block;font-size:10px;font-weight:600;line-height:1.22;color:#173a2c;word-break:break-word;}' +
            '.qr-print-card__meta{border-top:1px dashed #dbe5dd;padding-top:6px;margin-top:8px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:5px 8px;text-align:left;}' +
            '.qr-print-card__meta-row{display:flex;flex-direction:column;gap:1px;}' +
            '.qr-print-card__meta-row--wide{grid-column:3;}' +
            '.qr-print-card__meta-label{display:block;font-size:7px;font-weight:700;letter-spacing:0.08em;color:#6d7b74;text-transform:uppercase;}' +
            '.qr-print-card__meta-value{display:block;font-size:9px;line-height:1.18;color:#21473b;word-break:break-word;}' +
            '@media print{body{margin:0.35in;}.qr-print-sheet{gap:12px;}.qr-print-card__top{grid-template-columns:165px minmax(135px,165px);}}' +
            '</style></head><body>' +
            '<div class="qr-print-sheet">' + cardsHtml + '</div>' +
            '</body></html>';
    }

    function getCopies() {
        var copiesInput = document.getElementById('qrPrintCopies');
        var copies = parseInt((copiesInput && copiesInput.value) || '1', 10);

        if (!copies || copies < 1) {
            copies = 1;
        }

        if (copies > 20) {
            copies = 20;
        }

        return copies;
    }

    function printCards(cardNodes) {
        if (!cardNodes.length) {
            return;
        }

        var copies = getCopies();
        var printableCards = [];

        cardNodes.forEach(function (cardNode) {
            for (var copyIndex = 0; copyIndex < copies; copyIndex += 1) {
                printableCards.push(buildPrintableCard(cardNode));
            }
        });

        var printWindow = window.open('', '_blank', 'width=1200,height=900');
        if (!printWindow) {
            return;
        }

        printWindow.document.open();
        printWindow.document.write(buildPrintDocument(printableCards.join('')));
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var printButton = document.getElementById('printFilteredQrBtn');
        var printModeSelect = document.getElementById('qrPrintMode');
        var cardNodes = Array.prototype.slice.call(document.querySelectorAll('[data-print-card="true"]'));

        if (!printButton) {
            return;
        }

        printButton.addEventListener('click', function () {
            var mode = printModeSelect ? printModeSelect.value : 'all';
            var printableNodes = cardNodes;

            if (mode === 'selected') {
                printableNodes = cardNodes.filter(function (cardNode) {
                    var checkbox = cardNode.querySelector('.qr-print-checkbox');
                    return !!(checkbox && checkbox.checked);
                });

                if (!printableNodes.length) {
                    window.alert('Select at least one QR card before using selected print mode.');
                    return;
                }
            }

            printCards(printableNodes);
        });

        cardNodes.forEach(function (cardNode) {
            var printSingleButton = cardNode.querySelector('[data-print-single="true"]');
            if (!printSingleButton) {
                return;
            }

            printSingleButton.addEventListener('click', function () {
                printCards([cardNode]);
            });
        });
    });
})();
