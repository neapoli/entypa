<?php

namespace Drupal\entypa_praxis\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\BooleanOperator;

/**
 * Filter for evaso_data field (NULL vs NOT NULL).
 *
 * @ViewsFilter("entypa_praxis_evaso_data")
 */
class EvasoDataFilter extends BooleanOperator {

  protected function valueForm(&$form, $form_state) {
    $form['value'] = [
      '#type' => 'radios',
      '#title' => $this->t('Evaso'),
      '#options' => [
        1 => $this->t('Sì'),
        0 => $this->t('No'),
      ],
      '#default_value' => !empty($this->value) ? 1 : 0,
    ];
  }

  public function query() {
    $this->ensureMyTable();
    $field = "$this->tableAlias.$this->realField";
    
    if (empty($this->value)) {
      // Not evaso - IS NULL.
      $this->query->addWhere($this->options['group'], $field, NULL, 'IS NULL');
    }
    else {
      // Evaso - IS NOT NULL.
      $this->query->addWhere($this->options['group'], $field, NULL, 'IS NOT NULL');
    }
  }
}
