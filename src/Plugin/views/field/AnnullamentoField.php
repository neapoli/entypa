<?php

namespace Drupal\entypa\Plugin\views\field;

use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\entypa\RigaIstanza;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Il comando con cui il dipendente chiede l'annullamento della propria istanza.
 *
 * Finché non c'è una richiesta mostra il comando; dopo, come è andata.
 *
 * @ViewsField("entypa_annullamento")
 */
class AnnullamentoField extends FieldPluginBase {

  use RigaIstanza;

  /**
   * Come si legge ogni valore di «richiesta_annullamento».
   *
   * @return array
   *   Etichetta e classe per ciascuno stato.
   */
  public static function condizioni() {
    // Nella colonna sta solo un'icona: il testo lo dice già la colonna «Stato»,
    // e ripeterlo sfasava la tabella. L'etichetta resta come suggerimento.
    return [
      1 => ['icona' => 'it-clock', 'colore' => '#a5540a', 'etichetta' => 'Annullamento richiesto'],
      2 => ['icona' => 'it-close-circle', 'colore' => '#dc3545', 'etichetta' => 'Istanza annullata'],
      3 => ['icona' => 'it-ban', 'colore' => '#5d7083', 'etichetta' => 'Richiesta di annullamento non accolta'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->giungiDatoInvio('richiesta_annullamento');
    // Servono solo a sapere se l'istanza è ancora in lavorazione.
    $this->giungiIstruttoria(['evaso_data', 'annullato_data']);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $sid = $this->identificativoInvio($values);
    $stato = (int) $this->valoreEntypa($values, 'richiesta_annullamento', 0);
    $condizioni = static::condizioni();

    if (isset($condizioni[$stato])) {
      return $this->cella($sid, entypa_icona(
        $condizioni[$stato]['icona'],
        $condizioni[$stato]['colore'],
        $this->t($condizioni[$stato]['etichetta'])
      ));
    }

    // Con l'istruttoria in funzione, dopo l'evasione decide solo la segreteria.
    if ($this->conIstruttoria()) {
      $evaso = $this->valoreEntypa($values, 'praxis_evaso_data');
      $annullato = $this->valoreEntypa($values, 'praxis_annullato_data');
      if (!is_null($evaso) || !is_null($annullato)) {
        return $this->cella($sid, '---');
      }
    }

    // Il modulo con la casella del motivo si apre in una finestra: qui sta
    // solo il comando, perché la colonna è stretta.
    $collegamento = Link::fromTextAndUrl(
      $this->t('Chiedi annullamento'),
      Url::fromRoute('entypa.richiesta_annullamento', ['sid' => $sid])
    )->toRenderable();

    $collegamento['#attributes'] = [
      'class' => ['use-ajax', 'button', 'button--small', 'entypa-chiedi-annullamento'],
      'data-dialog-type' => 'modal',
      'data-dialog-options' => '{"width":520}',
    ];
    $collegamento['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $collegamento['#attached']['library'][] = 'entypa/annullamento';

    return [
      '#type' => 'container',
      '#attributes' => ['id' => 'entypa-annullamento-' . $sid],
      'comando' => $collegamento,
    ];
  }

  /**
   * L'identificativo dell'invio, quale che sia l'alias nella query.
   *
   * @param \Drupal\views\ResultRow $values
   *   La riga di risultato.
   *
   * @return int|null
   *   L'identificativo dell'invio.
   */
  protected function identificativoInvio(ResultRow $values) {
    $alias = $this->query->fields ?? [];

    foreach ($alias as $nome => $info) {
      if (($info['field'] ?? NULL) === 'sid' && property_exists($values, $nome)) {
        return $values->{$nome};
      }
    }

    return property_exists($values, 'sid') ? $values->sid : NULL;
  }

  /**
   * Costruisce la cella non interattiva.
   *
   * @param int $sid
   *   L'identificativo dell'invio.
   * @param string $contenuto
   *   L'icona, oppure il segno di cella muta.
   *
   * @return array
   *   L'elemento di rendering.
   */
  protected function cella($sid, $contenuto) {
    return [
      '#markup' => Markup::create('<span id="entypa-annullamento-' . $sid . '">' . $contenuto . '</span>'),
      '#attached' => ['library' => ['entypa/annullamento']],
    ];
  }

}
