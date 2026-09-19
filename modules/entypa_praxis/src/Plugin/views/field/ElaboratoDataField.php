<?php

namespace Drupal\entypa_praxis\Plugin\views\field;

use Drupal\Core\Render\Markup;
use Drupal\views\Plugin\views\field\Date;
use Drupal\views\ResultRow;
use Drupal\entypa_praxis\RigaIstruttoria;

/**
 * Field handler for Elaborato Data with done button.
 *
 * @ViewsField("entypa_praxis_elaborato_data")
 */
class ElaboratoDataField extends Date {

  use RigaIstruttoria;

  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    $sid = $this->colonnaRiga($values, 'sid');
    $uid = $this->colonnaRiga($values, 'uid');
    $tid = $this->colonnaIstruttoria($values, 'tid');
    $evaso = $this->colonnaIstruttoria($values, 'evaso_data');
    $annullato = $this->colonnaIstruttoria($values, 'annullato_data');

    if ($value) {
      // Already done - show date and icon.
      $formatted_date = parent::render($values);
      $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="green" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
      $html = sprintf(
        '<span id="done%d" style="white-space:nowrap">%s (%s)</span>',
        $sid,
        $icon_svg,
        strip_tags($formatted_date)
      );
      // Markup::create() perché la stringa è nostra e l'icona è SVG,
      // che Xss::filterAdmin() toglierebbe.
      return ['#markup' => Markup::create($html)];
    }

    // Show done button if evaso and not annullato.
    if (\Drupal::currentUser()->hasPermission('elaborato') &&
      $tid &&
      $evaso &&
      !$annullato) {
      $form = \Drupal::formBuilder()->getForm(
        'Drupal\entypa_praxis\Form\EntypaPraxisDoneForm',
        $sid, $uid, $tid, 0, 'elaborato'
      );
      // Il form si restituisce reso, non incollato dentro #markup:
      // Xss::filterAdmin() non ammette <form> e lo cancellerebbe.
      $form['#prefix'] = '<span id="done' . $sid . '">';
      $form['#suffix'] = '</span>';
      return \Drupal::service('renderer')->render($form);
    }

    return ['#markup' => '<span id="done' . $sid . '">---</span>'];
  }
}
