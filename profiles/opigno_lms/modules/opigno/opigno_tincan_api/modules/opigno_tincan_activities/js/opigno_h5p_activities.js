/**
 * @file
 * Send h5p statements to statement relay
 */
(function ($) {
    Drupal.behaviors.h5pTincanRelay = {
        attach: function (context, settings) {
            if (window.H5P) {
                var moduleSettings = settings.h5pTincanRelay;
                H5P.externalDispatcher.on('xAPI', function (event) {
                    var data = {
                        statement: JSON.stringify(event.data.statement, null)
                    };
                    $.post(moduleSettings.relayUrl, data);
                });
            }
        }
    };
}(jQuery));