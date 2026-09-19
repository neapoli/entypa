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
   * Constructs an EmailService object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    MailManagerInterface $mail_manager,
    AccountInterface $current_user,
    LanguageManagerInterface $language_manager,
    DateFormatterInterface $date_formatter,
    Connection $database
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->mailManager = $mail_manager;
    $this->currentUser = $current_user;
    $this->languageManager = $language_manager;
    $this->dateFormatter = $date_formatter;
    $this->database = $database;
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

}
