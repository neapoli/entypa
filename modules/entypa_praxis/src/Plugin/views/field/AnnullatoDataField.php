<?php

namespace Drupal\entypa_praxis\Plugin\views\field;

use Drupal\Core\Render\Markup;
use Drupal\views\Plugin\views\field\Date;
use Drupal\views\ResultRow;
use Drupal\entypa_praxis\RigaIstruttoria;

/**
 * Field handler for Annullato Data with cancel button.
 *
 * @ViewsField("entypa_praxis_annullato_data")
 */
class AnnullatoDataField extends Date {

  use RigaIstruttoria;

  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    $sid = $this->colonnaRiga($values, 'sid');
    $uid = $this->colonnaRiga($values, 'uid');
    $tid = $this->colonnaIstruttoria($values, 'tid');

    if ($value) {
      // Solo l'icona: la data allungava la colonna e la si ritrova come
      // suggerimento al passaggio del mouse. È la stessa X che il dipendente
      // vede nel proprio elenco, presa dal corredo di icone del tema.
      // La data si formatta qui: il formato del campo Views è quello
      // dell'elenco, e nel suggerimento va scritta all'italiana.
      $icona = entypa_icona('it-close-circle', '#dc3545', $this->t('Annullata il @data', [
        '@data' => \Drupal::service('date.formatter')->format((int) $value, 'custom', 'd/m/Y'),
      ]));

      return [
        '#markup' => Markup::create('<span id="can' . $sid . '">' . $icona . '</span>'),
      ];
    }

    // Il pulsante compare solo dove la scheda è operativa. Il campo
    // «modifica» è l'interruttore: se una visualizzazione non lo contiene,
    // resta di sola consultazione — e allora nessun comando deve accendersi,
    // questo compreso, che finora sfuggiva al controllo.
    $editable = property_exists($this->view, 'si_editable') ? $this->view->si_editable : FALSE;

    if ($editable && \Drupal::currentUser()->hasPermission('annullato') && $tid) {
      $form = \Drupal::formBuilder()->getForm(
        'Drupal\entypa_praxis\Form\EntypaPraxisCancelForm',
        $sid, $uid, $tid, 0, 'annullato'
      );
      // Il form si restituisce reso, non incollato dentro #markup:
      // Xss::filterAdmin() non ammette <form> e lo cancellerebbe.
      $form['#prefix'] = '<span id="can' . $sid . '">';
      $form['#suffix'] = '</span>';
      return \Drupal::service('renderer')->render($form);
    }

    return ['#markup' => '<span id="can' . $sid . '">---</span>'];
  }
}
