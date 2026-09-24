<?php

namespace Drupal\entypa\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Con questo il dipendente chiede l'annullamento di una propria istanza.
 *
 * La richiesta non annulla niente: mette un segno sull'istanza e manda alla
 * segreteria un messaggio da protocollare. Chi decide è la scuola — sul sito,
 * se è installato Entýpa — Praxis, altrimenti sul proprio gestionale.
 */
class EntypaRichiestaAnnullamentoForm extends FormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new EntypaRichiestaAnnullamentoForm.
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, MailManagerInterface $mail_manager, DateFormatterInterface $date_formatter, ConfigFactoryInterface $config_factory) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->mailManager = $mail_manager;
    $this->dateFormatter = $date_formatter;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.mail'),
      $container->get('date.formatter'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entypa_richiesta_annullamento_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $sid = NULL) {
    // Il modulo si apre in una finestra: gli errori di compilazione vanno
    // rimessi lì dentro, non nella pagina sotto.
    $form['#prefix'] = '<div id="entypa-richiesta-annullamento">';
    $form['#suffix'] = '</div>';
    $form['messaggi'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $form['submission'] = [
      '#type' => 'value',
      '#value' => $sid,
    ];

    $form['motivo'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Motivo della richiesta'),
      '#description' => $this->t('Il motivo viene trasmesso alla segreteria insieme alla richiesta.'),
      '#rows' => 4,
      '#required' => TRUE,
      '#attributes' => ['class' => ['entypa-motivo-annullamento']],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['chiedi'] = [
      '#type' => 'submit',
      '#value' => $this->t('Invia la richiesta'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'entypa-richiesta-annullamento',
      ],
    ];

    return $form;
  }

  /**
   * Controllo di accesso della rotta che apre la finestra.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   L'utente che chiede di aprirla.
   * @param int $sid
   *   L'identificativo dell'invio del formulario.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   L'esito.
   */
  public function accesso(AccountInterface $account, $sid = NULL) {
    // Lo stato cambia a ogni richiesta: non si può tenere in cache.
    return AccessResult::allowedIf($this->istanzaAnnullabile($sid, $account))
      ->setCacheMaxAge(0);
  }

  /**
   * Il titolo della finestra.
   *
   * @return string
   *   Il titolo.
   */
  public function titolo() {
    return $this->t("Chiedi l'annullamento dell'istanza");
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$this->istanzaAnnullabile($form_state->getValue('submission'))) {
      $form_state->setErrorByName('motivo', $this->t("Per questa istanza non si può più chiedere l'annullamento."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->registra($form_state->getValue('submission'), $form_state->getValue('motivo'))) {
      $this->messenger()->addStatus($this->t('La richiesta di annullamento è stata trasmessa alla segreteria.'));
    }
  }

  /**
   * Risposta AJAX: al posto del comando resta scritto che è stata chiesta.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    // Con errori di compilazione la finestra resta aperta e li mostra.
    if ($form_state->getErrors()) {
      return $form;
    }

    $sid = $form_state->getValue('submission');
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    $response->addCommand(new ReplaceCommand(
      '#entypa-annullamento-' . $sid,
      '<span id="entypa-annullamento-' . $sid . '">'
      . '<span class="entypa-annullamento-stato entypa-annullamento--richiesto">'
      . $this->t('annullamento richiesto') . '</span></span>'
    ));

    return $response;
  }

  /**
   * Verifica che l'istanza sia ancora annullabile su richiesta.
   *
   * @param int $sid
   *   L'identificativo dell'invio del formulario.
   *
   * @return bool
   *   TRUE se chi guarda può chiederne l'annullamento.
   */
  protected function istanzaAnnullabile($sid, AccountInterface $account = NULL) {
    $account = $account ?: $this->currentUser;
    $submission = $sid ? $this->entityTypeManager->getStorage('webform_submission')->load($sid) : NULL;

    // Solo le proprie istanze. Il controllo sta qui e non solo nella vista:
    // la vista decide che cosa mostrare, non che cosa si può fare.
    if (!$submission || (int) $submission->getOwnerId() !== (int) $account->id()) {
      return FALSE;
    }

    if (!$submission->getWebform()->getElement('richiesta_annullamento')) {
      return FALSE;
    }

    // Una richiesta già presente non si ripete, in nessuno dei suoi esiti.
    if ((int) $submission->getElementData('richiesta_annullamento') !== 0) {
      return FALSE;
    }

    return $this->inLavorazione($submission->id());
  }

  /**
   * Dice se l'istanza è ancora in lavorazione.
   *
   * Quando c'è l'istruttoria, dopo l'evasione l'annullamento lo dispone solo
   * la segreteria: la regola va applicata qui e non solo nella vista, perché
   * la vista decide che cosa mostrare, non che cosa si può fare. Senza
   * istruttoria il sito non sa dove sia arrivata la pratica e non oppone
   * nessun limite.
   *
   * @param int $sid
   *   L'identificativo dell'invio del formulario.
   *
   * @return bool
   *   TRUE se si può ancora chiedere l'annullamento.
   */
  protected function inLavorazione($sid) {
    if (!\Drupal::moduleHandler()->moduleExists('entypa_praxis')) {
      return TRUE;
    }

    $riga = \Drupal::database()->select('entypa_praxis', 'x')
      ->fields('x', ['evaso_data', 'annullato_data'])
      ->condition('submission_id', $sid)
      ->execute()
      ->fetchAssoc();

    // Senza riga di istruttoria la pratica non è ancora stata avviata.
    if (!$riga) {
      return TRUE;
    }

    return is_null($riga['evaso_data']) && is_null($riga['annullato_data']);
  }

  /**
   * Scrive la richiesta e avvisa la segreteria.
   *
   * @param int $sid
   *   L'identificativo dell'invio del formulario.
   * @param string $motivo
   *   Il motivo indicato dal dipendente.
   *
   * @return bool
   *   TRUE se la richiesta è stata registrata.
   */
  protected function registra($sid, $motivo) {
    if (!$this->istanzaAnnullabile($sid)) {
      return FALSE;
    }

    $submission = $this->entityTypeManager->getStorage('webform_submission')->load($sid);
    $submission->setElementData('richiesta_annullamento', 1);
    $submission->setElementData('motivo_annullamento', $motivo);
    $submission->save();

    $this->avvisaSegreteria($submission, $motivo);

    // Senza istruttoria la richiesta vale come accolta: i referenti di plesso
    // sanno subito che l'assenza non avrà luogo. Con Praxis lo sapranno se e
    // quando l'ufficio annulla.
    \Drupal::service('entypa.referenti')->dopoRichiestaAnnullamento($submission);

    return TRUE;
  }

  /**
   * Manda alla segreteria il messaggio da protocollare.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   L'invio del formulario.
   * @param string $motivo
   *   Il motivo indicato dal dipendente.
   */
  protected function avvisaSegreteria($submission, $motivo) {
    $config = $this->configFactory->get('entypa.settings');

    if (!($config->get('annullamento_email') ?? TRUE)) {
      return;
    }

    $destinatario = entypa_get_mail_istituto();
    if (!$destinatario) {
      $this->getLogger('entypa')->warning("Richiesta di annullamento dell'istanza @sid non trasmessa: manca l'email dell'istituto.", [
        '@sid' => $submission->id(),
      ]);
      return;
    }

    $utente = $submission->getOwner();
    $nodo = $submission->getSourceEntity();

    $this->mailManager->mail(
      'entypa',
      'richiesta_annullamento',
      $destinatario,
      $utente->getPreferredLangcode(),
      [
        'nome' => $this->nomeEsteso($utente),
        'codice_fiscale' => $this->codiceFiscale($utente),
        'istanza' => $nodo ? $nodo->label() : $submission->getWebform()->label(),
        'istanza_serial' => $submission->serial(),
        'data' => $this->dateFormatter->format($submission->getCreatedTime(), 'custom', 'd/m/Y'),
        'motivo' => $motivo,
        'mail_richiedente' => $utente->getEmail(),
      ],
      NULL,
      TRUE
    );
  }

  /**
   * Nome e cognome del dipendente, se il sito li tiene in campi dedicati.
   *
   * @param \Drupal\user\UserInterface $utente
   *   L'utente.
   *
   * @return string
   *   Il nome da mostrare.
   */
  protected function nomeEsteso($utente) {
    if ($utente->hasField('field_nome') && $utente->hasField('field_cognome')) {
      $nome = $utente->get('field_nome')->value;
      $cognome = $utente->get('field_cognome')->value;
      if ($nome && $cognome) {
        return $nome . ' ' . $cognome;
      }
    }

    return $utente->getDisplayName();
  }

  /**
   * Codice fiscale del dipendente, se presente.
   *
   * @param \Drupal\user\UserInterface $utente
   *   L'utente.
   *
   * @return string
   *   Il codice fiscale, oppure stringa vuota.
   */
  protected function codiceFiscale($utente) {
    if ($utente->hasField('field_codice_fiscale') && !$utente->get('field_codice_fiscale')->isEmpty()) {
      return (string) $utente->get('field_codice_fiscale')->value;
    }

    return '';
  }

}
