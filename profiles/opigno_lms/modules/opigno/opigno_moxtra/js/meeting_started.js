(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.opignoMoxtraMeetingStarted = {
    attach: function (context, settings) {
      Moxtra.init({
        mode: drupalSettings.opignoMoxtra.mode,
        client_id: drupalSettings.opignoMoxtra.clientId,
        org_id: drupalSettings.opignoMoxtra.orgId,
        access_token: drupalSettings.opignoMoxtra.accessToken,
        sdk_version: '5',
      });

      Moxtra.joinMeet({
        session_key: drupalSettings.opignoMoxtra.sessionKey,
        iframe: true,
        video: true,
        tagid4iframe: 'live-meeting-container',
        iframewidth: '100%',
        error: function (event) {
          if (event.error_code == 412) {
            // Error code when the meeting has already ended
            $('div#live-meeting-container').hide();
            location.reload(true);
          }
        },
        end_meet: function () {
          location.reload(true);
        },
        reach_limit: function() {
          $('div#live-meeting-container').hide();
          $('#max_reached').show();
        }
      });
    },
  };
}(jQuery, Drupal, drupalSettings));
