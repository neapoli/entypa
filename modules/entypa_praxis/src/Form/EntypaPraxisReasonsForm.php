<?php

namespace Drupal\entypa_praxis\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Form for managing instance reasons (acquisition, visa, concession).
 */
class EntypaPraxisReasonsForm extends FormBase {

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
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

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
   * Constructs a new EntypaPraxisReasonsForm.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    MessengerInterface $messenger,
    MailManagerInterface $mail_manager,
    AccountInterface $current_user
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->mailManager = $mail_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('plugin.manager.mail'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entypa_praxis_reasons_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL, $submission = NULL, $tid = NULL, $field = NULL) {
    $config = $this->configFactory->get('entypa_praxis.settings');
    
    if (!$node || !$submission || !$tid || !$field) {
      $this->messenger->addError($this->t('Parametri mancanti.'));
      return $form;
    }

    // Check if node uses visto_dsga or visto_referente.
    $usa_visto_dsga = FALSE;
    $usa_visto_referente = FALSE;
    
    if ($node->hasField('field_si_dsga')) {
      $usa_visto_dsga = (bool) $node->get('field_si_dsga')->value;
    }
    if ($node->hasField('field_si_referente')) {
      $usa_visto_referente = (bool) $node->get('field_si_referente')->value;
    }

    // Get data from database.
    $database = Database::getConnection();
    $query = $database->select('entypa_praxis', 't')
      ->fields('t')
      ->condition('tid', $tid)
      ->execute();
    
    $data = $query->fetchAssoc();
    
    if (!$data) {
      $this->messenger->addWarning($this->t('Dati non trovati'));
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Salva'),
        '#disabled' => TRUE,
      ];
      return $form;
    }

    $uid = $data['user_id'];
    $campo_reale = $field === 'acquisito' ? 'concedibile' : $field;
    $checked = $data[$campo_reale . '_stato'] ?? 1;
    $reasons = $data['motivazioni'] ?? '';

    // Hidden fields.
    $form['nid'] = [
      '#type' => 'value',
      '#value' => $node->id(),
    ];
    $form['tid'] = [
      '#type' => 'value',
      '#value' => $tid,
    ];
    $form['uid'] = [
      '#type' => 'value',
      '#value' => $uid,
    ];
    $form['submission'] = [
      '#type' => 'value',
      '#value' => $submission,
    ];
    $form['field'] = [
      '#type' => 'value',
      '#value' => $campo_reale,
    ];
    $form['realfield'] = [
      '#type' => 'value',
      '#value' => $field,
    ];

    // Radio buttons based on field type.
    switch ($field) {
      case 'acquisito':
        $options = [
          1 => $usa_visto_dsga ? 
            $this->t('Acquisisci istanza (salvo visto DSGA contrario)') : 
            $this->t('Acquisisci istanza'),
          2 => $this->t('Respingi istanza'),
        ];
        $oe = $config->get('oe') ?: FALSE;
        if ($oe) {
          $options[0] = $this->t('Sospendi istanza');
        }
        $form['is_ok'] = [
          '#type' => 'radios',
          '#title' => $this->t('Acquisisci o respingi:'),
          '#options' => $options,
          '#default_value' => $checked ?: 1,
          '#required' => TRUE,
        ];
        break;

      case 'visto':
        $form['is_ok'] = [
          '#type' => 'radios',
          '#title' => $this->t('Concedi o respingi:'),
          '#options' => [
            1 => $this->t('Si concede'),
            2 => $this->t('Non si concede'),
          ],
          '#default_value' => $checked ?: 1,
          '#required' => TRUE,
        ];
        break;

      case 'visto_dsga':
        $form['is_ok'] = [
          '#type' => 'radios',
          '#title' => $this->t('Accetta o respingi:'),
          '#options' => [
            1 => $this->t('Istanza valida'),
            2 => $this->t('Istanza non valida'),
          ],
          '#default_value' => $checked ?: 1,
          '#required' => TRUE,
        ];
        break;

      case 'visto_referente':
        $form['is_ok'] = [
          '#type' => 'radios',
          '#title' => $this->t('Accetta o respingi:'),
          '#options' => [
            1 => $this->t('Istanza valida'),
            2 => $this->t('Istanza non valida'),
          ],
          '#default_value' => $checked ?: 1,
          '#required' => TRUE,
        ];
        break;

      case 'concedibile':
        $form['is_ok'] = [
          '#type' => 'radios',
          '#title' => $this->t('Valuta istanza:'),
          '#options' => [
            1 => $this->t('Istanza concedibile'),
            2 => $this->t('Istanza non concedibile'),
          ],
          '#default_value' => $checked ?: 1,
          '#required' => TRUE,
        ];
        break;
    }

    // Reasons textarea.
    $form['reasons'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Motivazioni, in caso di istanza respinta:'),
      '#default_value' => $reasons,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Salva'),
      '#ajax' => FALSE, // Explicitly disable AJAX
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];

    // Disable AJAX form submission
    $form['#attributes']['class'][] = 'no-ajax-submit';
    
    // Attach library to force normal HTML submission (disable AJAX)
    $form['#attached']['library'][] = 'entypa_praxis/force-normal-submit';
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('entypa_praxis.settings');
    $oe = $config->get('oe') ?: FALSE;
    $is_ok = $form_state->getValue('is_ok');
    $reasons = trim($form_state->getValue('reasons'));

    // Require reasons if instance is rejected or suspended.
    if (empty($reasons) && ($is_ok == 2 || ($oe && $is_ok == 0))) {
      $form_state->setErrorByName('reasons', $this->t('Campo richiesto per istanza respinta/sospesa.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('entypa_praxis.settings');
    $database = Database::getConnection();

    $values = $form_state->getValues();
    $nid = $values['nid'];
    $tid = $values['tid'];
    $uid = $values['uid'];
    $submission_id = $values['submission'];
    $field = $values['field'];
    $realfield = $values['realfield'];
    $is_ok = $values['is_ok'];
    $reasons = $values['reasons'];

    // Load node to check settings.
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    $usa_visto_dsga = FALSE;
    $usa_visto_referente = FALSE;
    $acquisisci = FALSE;

    if ($node) {
      if ($node->hasField('field_si_acquisisci')) {
        $acquisisci = (bool) $node->get('field_si_acquisisci')->value;
      }
      if ($node->hasField('field_si_dsga')) {
        $usa_visto_dsga = (bool) $node->get('field_si_dsga')->value;
      }
      if ($node->hasField('field_si_referente')) {
        $usa_visto_referente = (bool) $node->get('field_si_referente')->value;
      }
    }

    // Determine if instance should be marked as "evaso" (completed).
    $evaso = FALSE;
    if ($is_ok == 2 || $is_ok == 3) {
      // Rejected or suspended - always evaso.
      $evaso = TRUE;
    }
    elseif ($realfield == 'visto' && $is_ok == 1) {
      // DS visa approved - always evaso (normal workflow).
      $evaso = TRUE;
    }
    elseif ($acquisisci) {
      // Modalità acquisizione (senza visto DS)
      if ($usa_visto_dsga) {
        // Se c'è DSGA: evaso quando DSGA approva
        if ($realfield == 'visto_dsga' && $is_ok == 1) {
          $evaso = TRUE;
        }
      }
      else {
        // Se NON c'è DSGA: evaso quando verifica ufficio (acquisito/concedibile) approva
        if ($realfield == 'acquisito' && $is_ok == 1) {
          $evaso = TRUE;
        }
      }
    }
    // In modalità normale, visto DSGA è solo un passaggio intermedio
    // NON imposta evaso - solo visto DS lo fa

    // Update database.
    // Clear motivazioni if approved (is_ok = 1), keep them only for rejection/suspension
    $motivazioni_to_save = ($is_ok == 1) ? '' : $reasons;
    
    $fields_to_update = [
      $field . '_stato' => $is_ok,
      'motivazioni' => $motivazioni_to_save,
    ];

    if ($evaso) {
      $fields_to_update['evaso_data'] = \Drupal::time()->getRequestTime();
    }
    elseif ($field == 'protocollo' || 
            $field == 'visto_referente' || 
            $field == 'visto_dsga' ||
            ($field == 'concedibile' && $is_ok < 2)) {
      // Clear evaso_data for intermediate steps or when changing from rejected to approved
      $fields_to_update['evaso_data'] = NULL;
    }

    $rows = $database->update('entypa_praxis')
      ->fields($fields_to_update)
      ->condition('tid', $tid)
      ->execute();

    if ($rows > 0) {
      $this->messenger->addStatus($this->t('Salvato campo istanza @sid.', ['@sid' => $submission_id]));

      // Send email if instance is evaso or suspended (for OE mode).
      $oe = $config->get('oe') ?: FALSE;
      if ($evaso || ($oe && $is_ok == 0)) {
        $this->sendNotificationEmail($submission_id, $uid, $nid);
      }
    }
    else {
      $this->messenger->addWarning($this->t('Impossibile salvare campo istanza @sid.', ['@sid' => $submission_id]));
    }

    // Redirect: respect destination parameter if provided, otherwise go to node
    $destination = \Drupal::request()->query->get('destination');
    
    if ($destination) {
      // Use TrustedRedirectResponse for destination parameter
      $form_state->setRedirectUrl(Url::fromUserInput($destination));
    }
    else {
      // Fallback to node canonical if no destination specified
      $form_state->setRedirect('entity.node.canonical', ['node' => $nid]);
    }
  }

  /**
   * Send notification email.
   */
  protected function sendNotificationEmail($submission_id, $uid, $nid) {
    $email_service = \Drupal::service('entypa_praxis.email');
    
    // Get instance data to include in email.
    $database = Database::getConnection();
    $instance_data = $database->select('entypa_praxis', 't')
      ->fields('t')
      ->condition('submission_id', $submission_id)
      ->execute()
      ->fetchAssoc();
    
    if ($instance_data) {
      // Determine which field completed the instance (caused evaso)
      // Priority: visto_stato > visto_dsga_stato > concedibile_stato
      $is_ok = NULL;
      
      if (!empty($instance_data['visto_stato'])) {
        // DS visa exists - use it (normal workflow)
        $is_ok = $instance_data['visto_stato'];
      }
      elseif (!empty($instance_data['visto_dsga_stato'])) {
        // DSGA visa exists (acquisition with DSGA)
        $is_ok = $instance_data['visto_dsga_stato'];
      }
      elseif (!empty($instance_data['concedibile_stato'])) {
        // Concedibile/acquisito (acquisition without DSGA)
        $is_ok = $instance_data['concedibile_stato'];
      }
      else {
        // Fallback - should not happen if evaso is set correctly
        $is_ok = 1;
      }
      
      $params = [
        'is_ok' => $is_ok,
        'reasons' => $instance_data['motivazioni'] ?? '',
      ];
      
      $email_service->sendNotification($submission_id, $uid, $nid, $params);
    }
  }

}
