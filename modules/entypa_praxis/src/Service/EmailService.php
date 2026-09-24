<?php

namespace Drupal\entypa_praxis\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface;
use Drupal\entypa\Segnatura;

/**
 * Service for handling email notifications for instances.
 */
class EmailService {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Il costruttore della segnatura di protocollo.
   *
   * @var \Drupal\entypa\Segnatura
   */
  protected $segnatura;

  /**
   * The print engine plugin manager.
   *
   * @var \Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface
   */
  protected $printEngineManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs an EmailService object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    MailManagerInterface $mail_manager,
    AccountInterface $current_user,
    LanguageManagerInterface $language_manager,
    DateFormatterInterface $date_formatter,
    Connection $database,
    Segnatura $segnatura,
    EntityPrintPluginManagerInterface $print_engine_manager,
    MessengerInterface $messenger
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->mailManager = $mail_manager;
    $this->currentUser = $current_user;
    $this->languageManager = $language_manager;
    $this->dateFormatter = $date_formatter;
    $this->database = $database;
    $this->segnatura = $segnatura;
    $this->printEngineManager = $print_engine_manager;
    $this->messenger = $messenger;
  }

  /**
   * Send notification email for an instance.
   *
   * @param int $submission_id
   *   The webform submission ID.
   * @param int $uid
   *   The user ID who submitted the instance.
   * @param int $nid
   *   The node ID.
   * @param array $params
   *   Additional parameters (is_ok, reasons, etc).
   */
  public function sendNotification($submission_id, $uid, $nid, array $params = []) {
    $config = $this->configFactory->get('entypa_praxis.settings');

    // Load user.
    $user = $this->entityTypeManager->getStorage('user')->load($uid);
    if (!$user) {
      return;
    }

    // Get submission data from webform.
    $submission_data = $this->getSubmissionData($submission_id);
    if (!$submission_data) {
      return;
    }

    // Prepare email parameters.
    $email_params = [
      'uid' => $uid,
      'user' => $user,
      'submission' => $submission_id,
      'nomenodo' => $submission_data['title'],
      'timestamp' => $submission_data['submitted'],
      'submission_serial' => $submission_data['serial'],
      'is_ok' => $params['is_ok'] ?? 1,
      'reasons' => $params['reasons'] ?? '',
    ];

    // Send email to user.
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $this->mailManager->mail(
      'entypa_praxis',
      'notifica',
      $user->getEmail(),
      $langcode,
      $email_params,
      NULL,
      TRUE
    );

    // Check if we should send email to secretary.
    if ($this->shouldSendToSecretary($nid)) {
      $secretary_email = $config->get('email');
      if (!empty($secretary_email)) {
        $this->mailManager->mail(
          'entypa_praxis',
          'segreteria',
          $secretary_email,
          $langcode,
          $email_params,
          NULL,
          TRUE
        );
      }
    }
  }

  /**
   * Avvisa il dipendente che la sua istanza è stata annullata.
   *
   * L'annullamento è una decisione dell'ufficio, non l'esito dell'istruttoria:
   * il messaggio va solo a chi ha presentato l'istanza, non alla segreteria.
   *
   * @param int $submission_id
   *   L'identificativo dell'invio del formulario.
   * @param int $uid
   *   L'identificativo di chi ha presentato l'istanza.
   *
   * @return bool
   *   TRUE se il messaggio è stato consegnato al sistema di posta.
   */
  public function sendCancellationNotice($submission_id, $uid) {
    $user = $this->entityTypeManager->getStorage('user')->load($uid);
    if (!$user || !$user->getEmail()) {
      return FALSE;
    }

    $submission_data = $this->getSubmissionData($submission_id);
    if (!$submission_data) {
      return FALSE;
    }

    $esito = $this->mailManager->mail(
      'entypa_praxis',
      'annullata',
      $user->getEmail(),
      $this->languageManager->getCurrentLanguage()->getId(),
      [
        'uid' => $uid,
        'user' => $user,
        'submission' => $submission_id,
        'nomenodo' => $submission_data['title'],
        'timestamp' => $submission_data['submitted'],
        'submission_serial' => $submission_data['serial'],
        // 4 è l'esito «annullata» in prepareEmailVariables().
        'is_ok' => 4,
        'reasons' => '',
      ],
      NULL,
      TRUE
    );

    return !empty($esito['result']);
  }

  /**
   * Check if email should be sent to secretary.
   *
   * @param int $nid
   *   The node ID.
   *
   * @return bool
   *   TRUE if email should be sent to secretary.
   */
  protected function shouldSendToSecretary($nid) {
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    if (!$node || !$node->hasField('field_si_email')) {
      return FALSE;
    }

    return (bool) $node->get('field_si_email')->value;
  }

  /**
   * Get submission data.
   *
   * @param int $submission_id
   *   The submission ID.
   *
   * @return array|null
   *   Submission data or NULL.
   */
  protected function getSubmissionData($submission_id) {
    // FIXED: In Drupal 10, get entity_id and load the node to get the title
    $query = $this->database->select('webform_submission', 's');
    $query->fields('s', ['sid', 'created', 'serial', 'webform_id', 'entity_id', 'entity_type']);
    $query->condition('s.sid', $submission_id);

    $result = $query->execute()->fetchAssoc();

    if ($result) {
      $title = $result['webform_id']; // Default to webform ID

      // Try to get the node title if entity_type is node
      if ($result['entity_type'] === 'node' && !empty($result['entity_id'])) {
        $node = $this->entityTypeManager->getStorage('node')->load($result['entity_id']);
        if ($node) {
          $title = $node->getTitle();
        }
      }

      return [
        'title' => $title,
        'submitted' => $result['created'],
        'serial' => $result['serial'],
      ];
    }

    return NULL;
  }

  /**
   * Prepare email variables for token replacement.
   *
   * Public so that entypa_praxis_mail() can reuse it instead of repeating the
   * same logic: in the old module the two copies had already drifted apart.
   *
   * @param array $params
   *   Email parameters.
   *
   * @return array
   *   Variables for token replacement.
   */
  public function prepareEmailVariables(array $params) {
    $config = $this->configFactory->get('entypa_praxis.settings');
    $site_config = $this->configFactory->get('system.site');
    $oe = $config->get('oe') ?: FALSE;

    // Determine status text.
    $is_ok = $params['is_ok'] ?? 1;

    $stato = '';
    if ($oe && $is_ok == 0) {
      $stato = $this->t('sospesa');
    }
    elseif ($is_ok == 1) {
      $stato = $this->t('accolta');
    }
    elseif ($is_ok == 2) {
      $stato = $this->t('respinta');
    }
    elseif ($is_ok == 3) {
      $stato = $this->t('sospesa');
    }
    elseif ($is_ok == 4) {
      $stato = $this->t('annullata');
    }

    // Get user names.
    $user = $params['user'] ?? NULL;
    if (!$user) {
      return [];
    }
    $current_user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());

    $requestor_name = $user->getDisplayName();
    if ($user->hasField('field_nome') && $user->hasField('field_cognome')) {
      $nome = $user->get('field_nome')->value;
      $cognome = $user->get('field_cognome')->value;
      if ($nome && $cognome) {
        $requestor_name = $nome . ' ' . $cognome;
      }
    }

    $operator_name = $current_user->getDisplayName();
    if ($current_user->hasField('field_nome') && $current_user->hasField('field_cognome')) {
      $nome = $current_user->get('field_nome')->value;
      $cognome = $current_user->get('field_cognome')->value;
      if ($nome && $cognome) {
        $operator_name = $nome . ' ' . $cognome;
      }
    }

    $operator_role = '';
    if ($current_user->hasField('field_qualifica')) {
      $operator_role = $current_user->get('field_qualifica')->value ?: '';
    }

    $codice_fiscale = '';
    if ($user->hasField('field_codice_fiscale')) {
      $cf_field = $user->get('field_codice_fiscale');
      if (!$cf_field->isEmpty()) {
        $codice_fiscale = $cf_field->value;
      }
    }

    return [
      '@nomesito' => $site_config->get('name'),
      // La denominazione ufficiale sta nelle impostazioni di Entýpa; se la
      // scuola non l'ha compilata vale il nome del sito.
      '@istituto' => $this->configFactory->get('entypa.settings')->get('nome_scuola') ?: $site_config->get('name'),
      '@nome' => $requestor_name,
      '@codice_fiscale' => $codice_fiscale,
      '@istanza' => $params['nomenodo'] ?? '',
      '@istanza_id' => $params['submission'] ?? '',
      '@istanza_serial' => $params['submission_serial'] ?? '',
      '@data' => isset($params['timestamp']) ? $this->dateFormatter->format($params['timestamp'], 'custom', 'd/m/Y') : '',
      '@esito' => $stato,
      '@motivazioni' => $params['reasons'] ?? '',
      '@operatore' => $operator_name,
      '@qualifica_operatore' => $operator_role,
    ];
  }

  /**
   * Allega all'email di istanza evasa la segnatura e i due documenti.
   *
   * A scrivere è la scuola, che comunica alla propria segreteria l'esito: il
   * documento principale è l'esito, in PDF, e l'istanza a cui si riferisce
   * viaggia con esso. Senza i codici IPA e AOO la scuola non si può
   * presentare come mittente, e la segnatura non si allega.
   *
   * @param array $message
   *   Il messaggio in composizione, da hook_mail().
   * @param array $params
   *   I parametri del messaggio.
   */
  public function allegaSegnaturaEvasione(array &$message, array $params) {
    // La segnatura è un di più: se qualcosa va storto l'email parte senza, e
    // l'errore resta nel registro.
    try {
      $this->costruisciSegnaturaEvasione($message, $params);
    }
    catch (\Throwable $e) {
      \Drupal::logger('entypa_praxis')->error("Segnatura non allegata all'email di istanza evasa n. @serial: @errore", [
        '@serial' => $params['submission_serial'] ?? '',
        '@errore' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Costruisce gli allegati per allegaSegnaturaEvasione().
   */
  protected function costruisciSegnaturaEvasione(array &$message, array $params) {
    $mittente = $this->segnatura->mittenteScuola();
    if ($mittente['codice_amministrazione'] === '' || $mittente['codice_aoo'] === '') {
      $this->messenger->addWarning($this->t("Segnatura di protocollo non allegata all'email di istanza evasa: mancano il codice IPA o il codice AOO nelle impostazioni di Entýpa."));
      return;
    }

    $submission = $this->entityTypeManager->getStorage('webform_submission')->load($params['submission'] ?? 0);
    if (!$submission) {
      return;
    }

    $serial = $params['submission_serial'] ?? $submission->serial();
    $allegati = [];

    $esito = $this->pdfEsito($message, (string) $serial);
    if ($esito) {
      $allegati[] = $esito;
    }

    $istanza = entypa_pdf_istanza($submission);
    if ($istanza && !empty($istanza['filecontent'])) {
      $allegati[] = $istanza;
    }

    if (!$allegati) {
      return;
    }

    $documenti = array_map(fn($allegato) => [
      'nome' => $allegato['filename'],
      'mime' => $allegato['filemime'],
    ], $allegati);

    $xml = $this->segnatura->componi(
      $mittente,
      $this->segnatura->numeroScuola(),
      $message['to'],
      (string) $message['subject'],
      $documenti
    );

    $allegati[] = $this->segnatura->allegato($xml);

    foreach ($allegati as $allegato) {
      $message['params']['attachments'][] = $allegato;
    }
  }

  /**
   * Riduce il testo dell'email a testo semplice.
   *
   * I testi delle notifiche li scrive la scuola, e dove la posta parte in
   * HTML vanno a capo con «<br>»: nell'email funziona, nel PDF si leggerebbe
   * il tag. Gli a capo — in HTML o in chiaro — restano a capo, gli altri tag
   * si tolgono.
   */
  protected function testoSemplice(array $corpo) {
    $testo = implode("\n", array_map('strval', $corpo));
    $testo = preg_replace('#<br\s*/?>|</p>#i', "\n", $testo);
    $testo = html_entity_decode(strip_tags($testo), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $testo = str_replace("\r", '', $testo);
    // Il testo in chiaro e i <br> si sommano: più di una riga vuota di
    // seguito non serve a nessuno.
    $testo = preg_replace("/[ \t]*\n[ \t]*/", "\n", $testo);
    $testo = preg_replace("/\n{3,}/", "\n\n", $testo);

    return trim($testo);
  }

  /**
   * Stampa in PDF il testo dell'esito.
   *
   * @param array $message
   *   Il messaggio, di cui si stampano oggetto e testo.
   * @param string $serial
   *   Il numero dell'istanza, per il nome del file.
   *
   * @return array|null
   *   L'allegato, oppure NULL se la stampa non è riuscita.
   */
  protected function pdfEsito(array $message, $serial) {
    $html = '<html><head><meta charset="utf-8"><style>body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; line-height: 1.5; } h1 { font-size: 13pt; }</style></head><body>'
      . '<p>' . htmlspecialchars($this->segnatura->nomeScuola(), ENT_QUOTES) . '</p>'
      . '<h1>' . htmlspecialchars((string) $message['subject'], ENT_QUOTES) . '</h1>'
      . '<p>' . nl2br(htmlspecialchars($this->testoSemplice($message['body']), ENT_QUOTES)) . '</p>'
      . '</body></html>';

    try {
      $motore = $this->printEngineManager->createSelectedInstance('pdf');
      $motore->addPage($html);
      $pdf = $motore->getBlob();
    }
    catch (\Throwable $e) {
      \Drupal::logger('entypa_praxis')->error("PDF dell'esito non generato per l'istanza n. @serial: @errore", [
        '@serial' => $serial,
        '@errore' => $e->getMessage(),
      ]);
      return NULL;
    }

    return [
      'filecontent' => $pdf,
      'filename' => 'Esito istanza n. ' . $serial . '.pdf',
      'filemime' => 'application/pdf',
    ];
  }

}
