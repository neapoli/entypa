<?php

namespace Drupal\entypa_praxis\Plugin\views\filter;

use Drupal\entypa_praxis\Plugin\views\field\RichiestaAnnullamentoField;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filtra le istanze per stato dell'annullamento.
 *
 * @ViewsFilter("entypa_praxis_richiesta_annullamento_filter")
 */
class RichiestaAnnullamentoFilter extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    $this->valueOptions = [
      0 => $this->t('nessuna richiesta'),
      1 => $this->t('annullamento richiesto'),
      2 => $this->t("annullata dall'ufficio"),
      3 => $this->t('richiesta non accolta'),
    ];

    // L'elenco deve restare allineato alle condizioni del campo.
    assert(array_keys($this->valueOptions) === array_keys(RichiestaAnnullamentoField::condizioni()));

    return $this->valueOptions;
  }

}
