/**
 * @file
 * Conferme per i pulsanti «elaborata» e «annulla» dell'istruttoria.
 */

(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.entypaPraxisConfirm = {
    attach: function (context, settings) {
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

  // Richiesta di annullamento da parte del dipendente.
  Drupal.behaviors.entypaPraxisRichiesta = {
    attach: function (context, settings) {
      once('entypa-praxis-richiesta', '.richiestaButtonClass', context).forEach(function (element) {
        var $button = $(element);
        var tid = $button.data('tid');

        $button.off('mousedown');

        $button.on('mousedown', function (e) {
          e.preventDefault();
          if (confirm(Drupal.t("La richiesta viene trasmessa alla segreteria, che decide se accoglierla.\nPer chiedere l'annullamento premere OK."))) {
            $button.trigger('richiestaAnnullamentoEvent_' + tid);
          }
          return false;
        });
      });
    }
  };

  /**
   * Nasconde la riga di un'istanza appena annullata.
   *
   * Dopo l'annullamento l'istanza non appartiene più agli elenchi di lavoro:
   * la si ritrova nella scheda «Annullate». Drupal riesegue i comportamenti
   * sul contenuto sostituito via AJAX, quindi questo scatta da solo.
   */
  Drupal.behaviors.entypaPraxisRigaAnnullata = {
    attach: function (context, settings) {
      once('entypa-praxis-riga-annullata', '.entypa-praxis-riga-annullata', context).forEach(function (element) {
        var riga = element.closest('tr');
        if (riga) {
          riga.style.display = 'none';
        }
      });
    }
  };

})(jQuery, Drupal, once);
