<?php

namespace Drupal\entypa_praxis\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Mostra in una finestra i testi che non stanno in una colonna.
 *
 * Le motivazioni dell'istruttoria e il motivo dell'annullamento sono discorsi
 * di più righe: nella tabella della segreteria occuperebbero lo spazio di
 * tutte le altre colonne messe insieme.
 */
class MotivoController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new MotivoController.
   */
  public function __construct(Connection $database, AccountInterface $current_user) {
    $this->database = $database;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('current_user')
    );
  }

  /**
   * Costruisce il pulsante che apre la finestra.
   *
   * @param string $tipo
   *   «istruttoria» oppure «annullamento».
   * @param int $id
   *   La riga di istruttoria o l'invio del formulario, secondo il tipo.
   * @param string $etichetta
   *   Il testo del pulsante.
   *
   * @return array
   *   L'elemento di rendering.
   */
  public static function pulsante($tipo, $id, $etichetta) {
    $collegamento = Link::fromTextAndUrl($etichetta, Url::fromRoute('entypa_praxis.motivo', [
      'tipo' => $tipo,
      'id' => $id,
    ]))->toRenderable();

    $collegamento['#attributes'] = [
      // «use-ajax» più «data-dialog-type» sono quanto basta a Drupal per
      // aprire il contenuto in una finestra, senza JavaScript nostro.
      'class' => ['use-ajax', 'button', 'button--small', 'entypa-praxis-motivo__apri'],
      'data-dialog-type' => 'modal',
      'data-dialog-options' => '{"width":520}',
    ];
    $collegamento['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $collegamento['#attached']['library'][] = 'entypa_praxis/entypa_praxis';

    return $collegamento;
  }

  /**
   * Restituisce il testo richiesto.
   *
   * @param string $tipo
   *   «istruttoria» per le motivazioni dell'ufficio, «annullamento» per il
   *   motivo indicato dal dipendente.
   * @param int $id
   *   La riga di istruttoria per il primo caso, l'invio del formulario per il
   *   secondo.
   *
   * @return array
   *   Il contenuto della finestra.
   */
  public function mostra($tipo, $id) {
    switch ($tipo) {
      case 'istruttoria':
        return $this->motivazioniIstruttoria($id);

      case 'annullamento':
        return $this->motivoAnnullamento($id);
    }

    throw new NotFoundHttpException();
  }

  /**
   * Il titolo della finestra.
   *
   * @param string $tipo
   *   Il tipo di testo richiesto.
   *
   * @return string
   *   Il titolo.
   */
  public function titolo($tipo) {
    return $tipo === 'annullamento'
      ? $this->t("Motivo della richiesta di annullamento")
      : $this->t('Motivazioni');
  }

  /**
   * Le motivazioni scritte dall'ufficio durante l'istruttoria.
   *
   * @param int $tid
   *   La riga di istruttoria.
   *
   * @return array
   *   Il contenuto della finestra.
   */
  protected function motivazioniIstruttoria($tid) {
    $riga = $this->database->select('entypa_praxis', 'x')
      ->fields('x', ['motivazioni', 'submission_id'])
      ->condition('tid', $tid)
      ->execute()
      ->fetchAssoc();

    if (!$riga) {
      throw new NotFoundHttpException();
    }

    $contenuto = $this->testo($riga['motivazioni']);

    // Da qui si arriva al modulo che permette di riscriverle.
    if ($this->currentUser->hasPermission('acquisito')) {
      $submission = $this->entityTypeManager()
        ->getStorage('webform_submission')
        ->load($riga['submission_id']);
      $nodo = $submission ? $submission->getSourceEntity() : NULL;

      if ($nodo) {
        $contenuto['modifica'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['entypa-praxis-motivo__azioni']],
          'collegamento' => Link::fromTextAndUrl($this->t('Modifica le motivazioni'), Url::fromRoute('entypa_praxis.concedi', [
            'node' => $nodo->id(),
            'submission' => $riga['submission_id'],
            'tid' => $tid,
          ]))->toRenderable(),
        ];
      }
    }

    return $contenuto;
  }

  /**
   * Il motivo indicato dal dipendente nel chiedere l'annullamento.
   *
   * @param int $sid
   *   L'invio del formulario.
   *
   * @return array
   *   Il contenuto della finestra.
   */
  protected function motivoAnnullamento($sid) {
    $submission = $this->entityTypeManager()
      ->getStorage('webform_submission')
      ->load($sid);

    if (!$submission) {
      throw new NotFoundHttpException();
    }

    $motivo = $submission->getWebform()->getElement('motivo_annullamento')
      ? $submission->getElementData('motivo_annullamento')
      : '';

    return $this->testo($motivo);
  }

  /**
   * Impagina un testo, o dice che non c'è.
   *
   * @param string $testo
   *   Il testo da mostrare.
   *
   * @return array
   *   Il contenuto della finestra.
   */
  protected function testo($testo) {
    $testo = trim((string) $testo);

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['entypa-praxis-motivo']],
      'testo' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $testo === '' ? $this->t('Nessun testo indicato.') : nl2br(htmlspecialchars($testo, ENT_QUOTES)),
      ],
    ];
  }

}
