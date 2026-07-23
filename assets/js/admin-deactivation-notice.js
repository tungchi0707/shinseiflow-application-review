(function () {
    'use strict';

    var config = window.tcarmDeactivationNotice || {};
    var message = config.message || '';

    if (!message) {
        return;
    }

    var pluginFile = 'shinseiflow-application-review/shinseiflow-application-review.php';
    var pluginFileEncoded = 'shinseiflow-application-review%2Fshinseiflow-application-review.php';

    document.addEventListener('click', function (event) {
        var target = event.target;
        var link = target && target.closest ? target.closest('a') : null;

        if (!link) {
            return;
        }

        var href = link.getAttribute('href') || '';
        var decodedHref = href;
        try {
            decodedHref = decodeURIComponent(href);
        } catch (error) {
            decodedHref = href;
        }

        var isDeactivateLink =
            link.classList.contains('deactivate') ||
            !!link.closest('.deactivate') ||
            href.indexOf('action=deactivate') !== -1 ||
            decodedHref.indexOf('action=deactivate') !== -1;

        if (!isDeactivateLink) {
            return;
        }

        var row = link.closest ? link.closest('tr[data-plugin]') : null;
        var rowPlugin = row ? row.getAttribute('data-plugin') : '';

        if (rowPlugin && rowPlugin !== pluginFile) {
            return;
        }

        var isTargetPlugin =
            rowPlugin === pluginFile ||
            href.indexOf(pluginFileEncoded) !== -1 ||
            href.indexOf(pluginFile) !== -1 ||
            decodedHref.indexOf(pluginFile) !== -1;

        if (!isTargetPlugin) {
            return;
        }

        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
}());
