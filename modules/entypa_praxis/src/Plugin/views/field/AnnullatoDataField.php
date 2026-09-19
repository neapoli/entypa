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
      // Already canceled - show date and icon.
      $formatted_date = parent::render($values);
      $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="red" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
      $html = sprintf(
        '<span id="can%d" style="white-space:nowrap">%s (%s)</span>',
        $sid,
        $icon_svg,
        strip_tags($formatted_date)
      );
      // Markup::create() perché la stringa è nostra e l'icona è SVG,
      // che Xss::filterAdmin() toglierebbe.
      return ['#markup' => Markup::create($html)];
    }

    // Show cancel button if user has permission.
    if (\Drupal::currentUser()->hasPermission('annullato') && $tid) {
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
