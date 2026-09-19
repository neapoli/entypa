/**
 * @file
 * Gestione AJAX della casella «protocollo».
 *
 * Ha un proprio endpoint perché la vista contiene molti moduli e l'AJAX di
 * Drupal non riesce a distinguere quale sia stato azionato.
 */

(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.entypaPraxisProtocolloAjax = {
    attach: function (context, settings) {
      var checkboxes = once('entypa-praxis-protocollo-ajax', '.entypa-praxis-protocollo-checkbox', context);

      checkboxes.forEach(function (checkbox) {
        var $checkbox = $(checkbox);

        $checkbox.on('change', function (e) {
          var sid = $checkbox.attr('data-sid');
          var tid = $checkbox.attr('data-tid');
          var isChecked = $checkbox.is(':checked') ? 1 : 0;

          $.ajax({
            // Drupal.url tiene conto di un'eventuale installazione in sottocartella.
            url: Drupal.url('entypa-praxis/ajax-protocollo'),
            type: 'POST',
            data: {
              sid: sid,
              tid: tid,
              checked: isChecked,
              current_path: window.location.pathname + window.location.search
            },
            success: function (response) {
              if (response && response.length > 0) {
                for (var i = 0; i < response.length; i++) {
                  if (response[i].command === 'insert') {
                    $(response[i].selector).replaceWith(response[i].data);
                  }
                }
              }
            },
            error: function (xhr, status, error) {
              console.error('Errore AJAX protocollo:', error);
            }
          });
        });
      });
    }
  };

})(jQuery, Drupal, once);
