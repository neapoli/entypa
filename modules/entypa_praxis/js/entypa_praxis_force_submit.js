/**
 * @file
 * Forza l'invio HTML normale (niente AJAX) di EntypaPraxisReasonsForm.
 */

(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.entypaPraxisForceNormalSubmit = {
    attach: function (context, settings) {
      once('entypa-praxis-force-normal', '#entypa-praxis-reasons-form', context).forEach(function (form) {
        var $form = $(form);

        // Toglie il comportamento AJAX dal modulo.
        $form.removeClass('use-ajax-submit');
        $form.removeAttr('data-drupal-form-submit-last');

        // E dal pulsante di invio.
        $form.find('input[type="submit"]').each(function () {
          $(this).removeClass('use-ajax-submit');
          $(this).removeClass('js-form-submit');
          $(this).off('mousedown.ajax');
          $(this).off('click.ajax');
        });

        if ($form.data('drupal-ajax-processed')) {
          $form.removeData('drupal-ajax-processed');
        }

        $form.off('submit.ajax');

        $form.on('submit', function (e) {
          // Rimuove l'oggetto Drupal.Ajax associato, se c'è.
          if (Drupal.ajax && Drupal.ajax.instances) {
            Drupal.ajax.instances = Drupal.ajax.instances.filter(function (instance) {
              return !instance || instance.element !== form;
            });
          }

          // Lascia proseguire l'invio HTML normale.
          return true;
        });
      });
    }
  };

})(jQuery, Drupal, once);
