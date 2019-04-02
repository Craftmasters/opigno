(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.opignoMoxtraMeetingScheduled = {
    attach: function (context, settings) {
      Moxtra.init({
        mode: drupalSettings.opignoMoxtra.mode,
        client_id: drupalSettings.opignoMoxtra.clientId,
        org_id: drupalSettings.opignoMoxtra.orgId,
        access_token: drupalSettings.opignoMoxtra.accessToken,
        sdk_version: '5',
      });

      const $startBtn = $('#start-meeting', context);
      $startBtn.once('click').click(function (e) {
        e.preventDefault();

        Moxtra.meet({
          schedule_binder_id: drupalSettings.opignoMoxtra.binderId,
          iframe: true,
          video: true,
          tagid4iframe: 'live-meeting-container',
          iframewidth: '100%',
          start_meet: function () {
            $startBtn.hide();
          },
          end_meet: function () {
            location.reload(true);
          },
          error: function(event){
            if (event.error_code == 409) {
              // If the meeting is already started in another window
              $('div#live-meeting-container').hide();
              // The reload will direct the host to the "join meeting" page.
              location.reload(true);
            }
          }
        });

        return false;
      });
    },
  };
}(jQuery, Drupal, drupalSettings));
