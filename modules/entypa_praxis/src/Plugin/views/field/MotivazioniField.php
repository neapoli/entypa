<?php

namespace Drupal\entypa_praxis\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\entypa_praxis\RigaIstruttoria;
use Drupal\entypa_praxis\Controller\MotivoController;

/**
 * Field handler for Motivazioni with link to modify.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entypa_praxis_motivazioni")
 */
class MotivazioniField extends FieldPluginBase {

  use RigaIstruttoria;

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $tid = $this->colonnaIstruttoria($values, 'tid');
    $motivazioni = trim((string) $this->colonnaIstruttoria($values, 'motivazioni', ''));

    // Senza motivazioni non c'è niente da aprire.
    if ($motivazioni === '' || is_null($tid)) {
      return ['#markup' => ''];
    }

    return MotivoController::pulsante('istruttoria', $tid, $this->t('Motivazioni'));
  }

}
