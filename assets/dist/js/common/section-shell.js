(function (window, $) {
    function parseHashState() {
        var raw = (window.location.hash || '').replace(/^#/, '');
        var map = {};

        if (!raw) {
            return map;
        }

        raw.split('&').forEach(function (pair) {
            var bits = pair.split('=');
            var key = decodeURIComponent(bits[0] || '').trim();
            var value = decodeURIComponent(bits[1] || '').trim();

            if (key) {
                map[key] = value;
            }
        });

        return map;
    }

    function writeHashState(nextState) {
        var parts = [];

        Object.keys(nextState || {}).forEach(function (key) {
            if (!nextState[key]) {
                return;
            }

            parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(nextState[key]));
        });

        var nextHash = parts.length ? '#' + parts.join('&') : '';
        var nextUrl = window.location.pathname + window.location.search + nextHash;

        if (window.history && typeof window.history.replaceState === 'function') {
            window.history.replaceState({}, '', nextUrl);
            return;
        }

        window.location.hash = nextHash;
    }

    function activateSection($workspace, targetSection, updateHash) {
        if (!$workspace || !$workspace.length || !targetSection) {
            return;
        }

        var shellId = $workspace.data('section-shell') || 'section';
        var $tabs = $workspace.find('.trs-section-shell__tab');
        var $panels = $workspace.find('.trs-section-panel');
        var $targetPanel = $workspace.find('.trs-section-panel[data-section-panel="' + targetSection + '"]');

        if (!$targetPanel.length) {
            return;
        }

        $tabs.removeClass('is-active').attr('aria-selected', 'false');
        $tabs.filter('[data-section-target="' + targetSection + '"]').addClass('is-active').attr('aria-selected', 'true');

        $panels.removeClass('is-active').attr('hidden', true);
        $targetPanel.addClass('is-active').removeAttr('hidden');

        $workspace.attr('data-active-section', targetSection);

        if (updateHash) {
            var hashState = parseHashState();
            hashState[shellId] = targetSection;
            writeHashState(hashState);
        }
    }

    function initWorkspace(root) {
        $(root).find('.trs-section-workspace').each(function () {
            var $workspace = $(this);
            var shellId = $workspace.data('section-shell') || 'section';
            var defaultSection = $workspace.data('default-section') || '';
            var hashState = parseHashState();
            var initialSection = hashState[shellId] || defaultSection;

            if (!initialSection) {
                initialSection = $workspace.find('.trs-section-shell__tab').first().data('section-target') || '';
            }

            activateSection($workspace, initialSection, false);
        });
    }

    $(document).on('click', '.trs-section-shell__tab', function (event) {
        event.preventDefault();

        var $button = $(this);
        var $workspace = $button.closest('.trs-section-workspace');
        var targetSection = $button.data('section-target');

        activateSection($workspace, targetSection, true);
    });

    $(window).on('hashchange', function () {
        initWorkspace(document);
    });

    window.TRSSectionShell = {
        init: initWorkspace,
        activate: activateSection
    };

    $(function () {
        initWorkspace(document);
    });
})(window, jQuery);
