<?php

namespace Drupal\entypa_praxis\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\Standard;

/**
 * Sort handler for Concedibile status field.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("entypa_praxis_concedibile_sort")
 */
class ConcedibileSort extends Standard {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    // Add the order by clause
    $this->query->addOrderBy($this->tableAlias, 'concedibile_stato', $this->options['order']);
  }

}
