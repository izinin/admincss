/**
 * @file
 * Behaviors for the Admin CSS configuration/editor.
 */

(function ($, Drupal, ace) {
  'use strict';

  Drupal.behaviors.adminCssEditor = {
    attach: function (context, settings) {

      if (typeof ace == 'undefined' || typeof ace.edit != 'function') {
        return;
      }

      function initializeEditor($textArea) {
        $textArea
            .wrap('<div class="admincss__resizable"></div>');
        var $resizable = $textArea.parent('.admincss__resizable');

        // Add the pseudo editor.
        $resizable
            .append('<div class="admincss__ace-editor"></div>');

        var textArea = $resizable.find('.admincss__ace-editor')[0];
        var editor = ace.edit(textArea);
        editor.setOptions({
          enableBasicAutocompletion: true,
          enableSnippets: true,
          enableLiveAutocompletion: false,
          newLineMode: 'unix',
          useSoftTabs: false,
          tabSize: 2,
          mode: 'ace/mode/' + $textArea.attr('data-ace-mode'),
          readOnly: $textArea.attr('data-ace-readonly'),
        });

        // Set content and place the cursor after it.
        editor.setValue($textArea.val(), 1);

        return editor;
      }

      // Add auto complete support.
      ace.require('ace/ext/language_tools');

      $('.admincss-ace-editor', context).once('initAceEditor').each(function () {

        var $this = $(this);
        var $form = $this.closest('form');
        var $editorTextArea = $this.find('.admincss__editor');

        var editor = initializeEditor($editorTextArea);

        // Make the editor resizable.
        $editorTextArea.closest('.admincss__resizable').resizable({
          // Resize vertically.
          handles: 's',
          resize: function () {
            editor.resize();
          }
        });

        editor.resize();

        var session = editor.getSession();
        session.on('change', function () {
          $editorTextArea.val(editor.getSession().getValue());
        });

        /*session.on('changeMode', function() {
          var worker = session.$worker;
          if (worker) {
            var submissionFn = function(){ return false; };
            worker.on('annotate', function(lint) {
              var messages = lint.data, hasError;
              hasError = messages.some(function(item) {
                return item.type === 'error';
              });
               if (hasError) {
                 // Disable the form submission.
                 $form.submit(submissionFn);
                 $form.find(':submit').attr('disabled', 'disabled');
               } else {
                 $form.unbind('submit', submissionFn);
                 $form.find(':submit').removeAttr('disabled');
               }
            });
          }
        });*/

        // Save the changes.
        editor.commands.addCommand({
          name: 'save',
          bindKey: {win: 'Ctrl-S', mac: 'Cmd-S'},
          exec: function(editor) {
            $form.submit();
          }
        });

        // Hide only if no unexpected errors occurred.
        $editorTextArea.hide();

      });
    }
  };

})(jQuery, Drupal, window.ace);
