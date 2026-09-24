<?php

namespace Drupal\entypa_praxis\Plugin\views\field;

use Drupal\Core\Render\Markup;
use Drupal\entypa_praxis\Controller\MotivoController;
use Drupal\entypa_praxis\RigaIstruttoria;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Mostra a che punto è l'annullamento di un'istanza.
 *
 * @ViewsField("entypa_praxis_richiesta_annullamento")
 */
class RichiestaAnnullamentoField extends FieldPluginBase {

  use RigaIstruttoria;

  /**
   * Gli invii, fra quelli della pagina, su cui il dipendente ha scritto un motivo.
   *
   * @var array
   */
  protected $conMotivo = [];

  /**
   * Le quattro condizioni possibili.
   *
   * @return array
   *   Etichetta e classe per ogni valore della colonna.
   */
  public static function condizioni() {
    // Un'icona e non una parola: la colonna deve restare stretta, e il testo
    // per esteso arriva come suggerimento al passaggio del mouse. Sono le
    // stesse icone che il dipendente vede nel proprio elenco.
    return [
      1 => ['icona' => 'it-clock', 'colore' => '#a5540a', 'etichetta' => 'Annullamento richiesto'],
      2 => ['icona' => 'it-close-circle', 'colore' => '#dc3545', 'etichetta' => 'Istanza annullata'],
      3 => ['icona' => 'it-ban', 'colore' => '#5d7083', 'etichetta' => 'Richiesta di annullamento non accolta'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    parent::preRender($values);

    // Lo stato 2 vale sia per l'istanza annullata su richiesta del dipendente
    // sia per quella annullata d'ufficio: a distinguerle è il motivo, che nella
    // richiesta è obbligatorio. Si legge una volta per tutta la pagina.
    $sids = [];
    foreach ($values as $riga) {
      if ((int) $this->getValue($riga) === 2 && ($sid = $this->colonnaRiga($riga, 'sid'))) {
        $sids[] = $sid;
      }
    }

    $this->conMotivo = [];
    if ($sids) {
      $this->conMotivo = \Drupal::database()->select('webform_submission_data', 'd')
        ->fields('d', ['sid'])
        ->condition('d.sid', $sids, 'IN')
        ->condition('d.name', 'motivo_annullamento')
        ->condition('d.value', '', '<>')
        ->execute()
        ->fetchAllKeyed(0, 0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $valore = (int) $this->getValue($values);
    $condizioni = static::condizioni();
    $sid = $this->colonnaRiga($values, 'sid');

    // Annullata d'ufficio senza che il dipendente l'avesse chiesto: non c'è
    // stato ritiro, e la colonna «Annullato» lo dice già.
    if ($valore === 2 && !is_null($sid) && !isset($this->conMotivo[$sid])) {
      $valore = 0;
    }

    if (!isset($condizioni[$valore])) {
      return ['#markup' => '---'];
    }

    $etichetta = [
      '#markup' => entypa_icona(
        $condizioni[$valore]['icona'],
        $condizioni[$valore]['colore'],
        $this->t($condizioni[$valore]['etichetta'])
      ),
    ];

    if (is_null($sid)) {
      return $etichetta;
    }

    // Il motivo lo ha scritto il dipendente ed è di più righe: si legge in
    // una finestra, perché in colonna non ci sta.
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['entypa-praxis-richiesta__cella']],
      'etichetta' => $etichetta,
      'motivo' => MotivoController::pulsante('annullamento', $sid, $this->t('Motivo')),
    ];
  }

}
