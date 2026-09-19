/**
 * @file
 * Conferme per i pulsanti «elaborata» e «annulla» dell'istruttoria.
 */

(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.entypaPraxisConfirm = {
    attach: function (context, settings) {
      // Pulsanti «istanza elaborata».
      once('entypa-praxis-done', '.doneButtonClass', context).forEach(function (element) {
        var $button = $(element);
        var tid = $button.data('tid');

        // Toglie il gestore predefinito.
        $button.off('mousedown');

        $button.on('mousedown', function (e) {
          e.preventDefault();
          if (confirm(Drupal.t("Operazione irreversibile.\nPer considerare elaborata l'istanza premere OK."))) {
            $button.trigger('doneInstanceEvent_' + tid);
          }
          return false;
        });
      });

      // Pulsanti «annulla istanza».
      once('entypa-praxis-cancel', '.deleteButtonClass', context).forEach(function (element) {
        var $button = $(element);
        var tid = $button.data('tid');

        $button.off('mousedown');

        $button.on('mousedown', function (e) {
          e.preventDefault();
          if (confirm(Drupal.t("Operazione irreversibile.\nPer annullare l'istanza premere OK."))) {
            $button.trigger('cancelInstanceEvent_' + tid);
          }
          return false;
        });
      });
    }
  };

})(jQuery, Drupal, once);
