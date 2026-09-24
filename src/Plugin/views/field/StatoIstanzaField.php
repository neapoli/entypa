<?php

namespace Drupal\entypa\Plugin\views\field;

use Drupal\Core\Render\Markup;
use Drupal\entypa\RigaIstanza;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * A che punto è l'istanza.
 *
 * Senza Entýpa — Praxis il sito non segue l'istruttoria — se ne occupa il
 * gestionale della scuola — e può dire solo se l'istanza è stata inviata,
 * se ne è stato chiesto l'annullamento o se è stata annullata. Con praxis
 * installato il racconto si allunga fino all'elaborazione.
 *
 * @ViewsField("entypa_stato")
 */
class StatoIstanzaField extends FieldPluginBase {

  use RigaIstanza;

  /**
   * Le colonne dell'istruttoria che servono a determinare lo stato.
   */
  const COLONNE = [
    'protocollo_stato',
    'concedibile_stato',
    'visto_referente_stato',
    'visto_dsga_stato',
    'visto_stato',
    'evaso_data',
    'annullato_data',
    'elaborato_data',
  ];

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->giungiDatoInvio('richiesta_annullamento');
    $this->giungiIstruttoria(self::COLONNE);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    [$testo, $classe] = $this->stato($values);

    return [
      '#markup' => Markup::create(sprintf(
        '<span class="entypa-stato %s">%s</span>',
        $classe,
        $this->t($testo)
      )),
      '#attached' => ['library' => ['entypa/annullamento']],
    ];
  }

  /**
   * Determina lo stato dell'istanza.
   *
   * L'ordine conta: si sceglie lo stadio più avanzato raggiunto.
   *
   * @param \Drupal\views\ResultRow $values
   *   La riga di risultato.
   *
   * @return array
   *   Etichetta e classe.
   */
  protected function stato(ResultRow $values) {
    $richiesta = (int) $this->valoreEntypa($values, 'richiesta_annullamento', 0);

    if ($richiesta === 2) {
      return ['annullata', 'entypa-stato--annullata'];
    }

    if (!$this->conIstruttoria()) {
      // Senza istruttoria l'unico avanzamento che il sito conosce è la
      // richiesta di annullamento: la decisione avviene fuori dal sito.
      return $richiesta === 1
        ? ['annullamento richiesto', 'entypa-stato--richiesto']
        : ['inviata', 'entypa-stato--inviata'];
    }

    if (!is_null($this->valoreEntypa($values, 'praxis_annullato_data'))) {
      return ['annullata', 'entypa-stato--annullata'];
    }

    // A iter concluso si dice com'è andata, non che è «evasa»: per chi ha
    // presentato la domanda quella parola non significa niente, e accanto a
    // un'email di diniego lascia credere il contrario.
    $esito = entypa_esito_istanza([
      $this->valoreEntypa($values, 'praxis_visto_stato', 0),
      $this->valoreEntypa($values, 'praxis_visto_dsga_stato', 0),
      $this->valoreEntypa($values, 'praxis_concedibile_stato', 0),
    ], $this->valoreEntypa($values, 'praxis_evaso_data'));

    if ($esito) {
      return [$esito['etichetta'], 'entypa-stato--' . $esito['chiave']];
    }

    // Un visto già espresso, in qualunque punto dell'iter, significa che la
    // pratica è in esame.
    foreach (['concedibile_stato', 'visto_referente_stato', 'visto_dsga_stato', 'visto_stato'] as $colonna) {
      if ((int) $this->valoreEntypa($values, 'praxis_' . $colonna, 0) > 0) {
        return ['in verifica', 'entypa-stato--verifica'];
      }
    }

    if ((int) $this->valoreEntypa($values, 'praxis_protocollo_stato', 0) === 1) {
      return ['protocollata', 'entypa-stato--protocollata'];
    }

    return ['inviata', 'entypa-stato--inviata'];
  }

}
