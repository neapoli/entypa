<?php

namespace Drupal\entypa_praxis\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Database\Database;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entypa_praxis\Service\EmailService;

/**
 * Form for canceling instances.
 */
class EntypaPraxisCancelForm extends FormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The email service.
   *
   * @var \Drupal\entypa_praxis\Service\EmailService
   */
  protected $emailService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntypaPraxisCancelForm.
   */
  public function __construct(AccountInterface $current_user, DateFormatterInterface $date_formatter, EmailService $email_service, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->dateFormatter = $date_formatter;
    $this->emailService = $email_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('date.formatter'),
      $container->get('entypa_praxis.email'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entypa_praxis_cancel_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $sid = NULL, $uid = NULL, $tid = NULL, $checked = NULL, $field = NULL) {
    
    $form['submission'] = [
      '#type' => 'value',
      '#value' => $sid,
    ];
    $form['tid'] = [
      '#type' => 'value',
      '#value' => $tid,
    ];

    $form['is_ok_cancel'] = [
      '#type' => 'button',
      '#value' => $this->t('Annulla'),
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'event' => 'cancelInstanceEvent_' . $tid,
        'progress' => ['type' => 'none'],
      ],
      '#attributes' => [
        'title' => $this->t('Fare clic per annullare'),
        'class' => ['button', 'button--small', 'deleteButtonClass'],
        'data-tid' => $tid,
      ],
    ];

    $form['#attributes'] = ['onsubmit' => 'return false'];

    // Attach JavaScript library.
    $form['#attached']['library'][] = 'entypa_praxis/entypa_praxis';

    return $form;
  }

  /**
   * AJAX callback for cancel button.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $sid = $values['submission'];
    $tid = $values['tid'];
    
    // Check permission.
    if (!$this->currentUser->hasPermission('annullato')) {
      return new AjaxResponse();
    }

    // Cancel the instance.
    $this->cancelInstance($values, FALSE);
    
    $response = new AjaxResponse();
    
    $timestamp = \Drupal::time()->getRequestTime();
    $formatted_date = $this->dateFormatter->format($timestamp, 'short');
    
    // La classe «entypa-praxis-riga-annullata» è l'aggancio per il
    // comportamento JavaScript che nasconde la riga: Drupal riesegue i
    // comportamenti sul contenuto inserito via AJAX, mentre uno <script>
    // incollato nel markup non è detto che venga eseguito.
    $html = sprintf(
      '<span class="entypa-praxis-riga-annullata">%s</span>',
      entypa_icona('it-close-circle', '#dc3545', $this->t('Annullata il @data', ['@data' => $formatted_date]))
    );
    
    $response->addCommand(new ReplaceCommand('#can' . $sid, $html));
    $response->addCommand(new ReplaceCommand('#done' . $sid, '<span id="done' . $sid . '">---</span>'));
    
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->cancelInstance($values);
  }

  /**
   * Cancel an instance.
   */
  protected function cancelInstance($values, $message = TRUE) {
    if ($values['tid'] == 0) {
      return;
    }

    $database = Database::getConnection();
    $database->update('entypa_praxis')
      ->fields([
        'annullato_data' => \Drupal::time()->getRequestTime(),
        // 2 = annullata dall'ufficio.
        'richiesta_annullamento' => 2,
      ])
      ->condition('tid', $values['tid'])
      ->execute();

    $uid = $this->segnaSullIstanza($values['submission']);

    // Se i referenti di plesso erano stati avvisati dell'assenza, ora sanno
    // che non avrà luogo.
    \Drupal::service('entypa_praxis.referenti')->aggiorna($values['submission']);

    // L'avviso parte solo se l'istanza esiste e ha un intestatario.
    if ($uid) {
      $this->emailService->sendCancellationNotice($values['submission'], $uid);
    }

    if ($message) {
      $this->messenger()->addStatus(
        $this->t('Annullata istanza id @sid.', ['@sid' => $values['submission']])
      );
    }
  }

  /**
   * Scrive l'annullamento anche dentro l'invio del formulario.
   *
   * La riga di istruttoria è una tabella di servizio: il segno deve stare
   * anche sull'istanza, così viaggia con i dati — esportazioni, PDF, elenco
   * del dipendente. I gestori email dei formulari sono configurati sullo
   * stato «completed», quindi salvare un invio già concluso non li riattiva.
   *
   * @param int $submission_id
   *   L'identificativo dell'invio del formulario.
   *
   * @return int|null
   *   L'identificativo di chi ha presentato l'istanza, oppure NULL.
   */
  protected function segnaSullIstanza($submission_id) {
    $submission = $this->entityTypeManager
      ->getStorage('webform_submission')
      ->load($submission_id);

    if (!$submission) {
      return NULL;
    }

    // Il campo c'è in tutti i formulari di Entýpa, ma non si può dare per
    // scontato su un formulario estraneo agganciato allo stesso tipo di nodo.
    if ($submission->getWebform()->getElement('richiesta_annullamento')) {
      $submission->setElementData('richiesta_annullamento', 2);
      $submission->save();
    }

    return $submission->getOwnerId();
  }

}
