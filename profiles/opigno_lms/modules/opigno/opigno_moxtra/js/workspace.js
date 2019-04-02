(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.opignoMoxtraWorkspace = {
    attach: function (context, settings) {
      Moxtra.init({
        mode: drupalSettings.opignoMoxtra.mode,
        client_id: drupalSettings.opignoMoxtra.clientId,
        org_id: drupalSettings.opignoMoxtra.orgId,
        access_token: drupalSettings.opignoMoxtra.accessToken,
        sdk_version: '5',
      });

      Moxtra.chat({
        binder_id: drupalSettings.opignoMoxtra.binderId,
        iframe: true,
        tagid4iframe: 'collaborative_workspace_container',
        iframewidth: '100%',
        autostart_meet: true,
        invite_members: true,
        produce_feeds: true,
        start_chat: function (e) {
          const $container = $('#collaborative_workspace_container');
          $container.trigger('moxtra_loaded');
        },
      });
    },
  };
}(jQuery, Drupal, drupalSettings));
