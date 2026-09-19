<?php

namespace Drupal\entypa_praxis\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Pseudo-field to enable editing in the view.
 *
 * @ViewsField("entypa_praxis_modifica")
 */
class ModificaField extends FieldPluginBase {

  public function preQuery() {
    // Set editable flag on the view.
    $this->view->si_editable = TRUE;
  }

  public function query() {
    // Do nothing.
  }

  public function render(ResultRow $values) {
    return ['#markup' => $this->t('Modifica abilitata')];
  }
}
