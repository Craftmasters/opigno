/* eslint func-names: ["error", "never"] */

import CodeMirror from 'codemirror';
import 'codemirror/lib/codemirror.css';
import 'codemirror/mode/css/css';
import 'codemirror/theme/material.css';
import 'codemirror/addon/edit/closebrackets';
import 'codemirror/addon/edit/matchbrackets';

/**
 * @file
 * Define theme settings form JS logic.
 */
(function ($, Drupal) {
  Drupal.behaviors.platonThemeSettings = {

    attach(context) {
      // Prevent ajax callbacks reload
      if (context !== document) return;

      const textarea = document.querySelector('textarea[name*="_css_override_content"]');
      CodeMirror.fromTextArea(textarea, {
        mode: 'css',
        autoCloseBrackets: true,
        matchBrackets: true,
        theme: 'material',
        lineNumbers: true,
      });
    },
  };
}(jQuery, Drupal, window));
