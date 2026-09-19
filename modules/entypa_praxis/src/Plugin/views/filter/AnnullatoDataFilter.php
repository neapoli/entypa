<?php

namespace Drupal\entypa_praxis\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\BooleanOperator;

/**
 * Filter for annullato_data field (NULL vs NOT NULL).
 *
 * @ViewsFilter("entypa_praxis_annullato_data_filter")
 */
class AnnullatoDataFilter extends BooleanOperator {

  protected function valueForm(&$form, $form_state) {
    $form['value'] = [
      '#type' => 'radios',
      '#title' => $this->t('Annullato'),
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
      $this->query->addWhere($this->options['group'], $field, NULL, 'IS NULL');
    }
    else {
      $this->query->addWhere($this->options['group'], $field, NULL, 'IS NOT NULL');
    }
  }
}
