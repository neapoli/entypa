<?php

namespace Drupal\entypa_praxis\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Database\Database;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Form for referente visa with select dropdown.
 */
class EntypaPraxisReferenteForm extends FormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new EntypaPraxisReferenteForm.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entypa_praxis_referente_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $sid = NULL, $uid = NULL, $tid = NULL, $checked = NULL, $field = NULL) {
    
    // Store values in form state storage so they persist across AJAX callbacks
    $form_state->set('submission_id', $sid);
    $form_state->set('user_id', $uid);
    $form_state->set('tid', $tid);
    $form_state->set('field', $field);
    
    // CRITICAL: Use both #value AND #default_value for hidden fields
    $form['user'] = [
      '#type' => 'hidden',
      '#value' => $uid,
      '#default_value' => $uid,
    ];
    $form['submission'] = [
      '#type' => 'hidden',
      '#value' => $sid,
      '#default_value' => $sid,
    ];
    $form['tid'] = [
      '#type' => 'hidden',
      '#value' => $tid,
      '#default_value' => $tid,
    ];
    $form['field'] = [
      '#type' => 'hidden',
      '#value' => $field,
      '#default_value' => $field,
    ];

    // Store destination for redirect.
    $request = \Drupal::request();
    $current_path = $request->getPathInfo();
    $tempstore = \Drupal::service('tempstore.private')->get('entypa_praxis');
    
    if ($current_path != '/system/ajax') {
      $tempstore->set('si_ref_dest_' . $sid, $current_path);
    }
    
    $destination = $tempstore->get('si_ref_dest_' . $sid) ?: $current_path;
    
    $form['destination'] = [
      '#type' => 'hidden',
      '#value' => $destination,
    ];

    $options = [
      0 => $this->t('Nessun visto'),
      1 => $this->t('Positivo'),
      2 => $this->t('Negativo'),
    ];

    $form['is_ok_ref'] = [
      '#type' => 'select',
      '#default_value' => $checked,
      '#options' => $options,
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'progress' => ['type' => 'none'],
      ],
      '#attributes' => [
        'title' => $this->t('Selezionare per cambiare lo stato'),
      ],
    ];

    $form['hidden_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Invia'),
      '#attributes' => [
        'style' => 'display:none',
      ],
    ];

    return $form;
  }

  /**
   * AJAX callback.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $uid = $values['user'] ?? NULL;
    
    // Check permission - must have "visto referente" permission.
    // TODO: Also check if user is the right referente for this submission.
    if (!$this->currentUser->hasPermission('visto referente')) {
      return new AjaxResponse();
    }

    // Save selection - pass form_state to access stored values
    $this->saveSelection($values, FALSE, $form_state);
    
    return new AjaxResponse();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->saveSelection($values, TRUE, $form_state);
  }

  /**
   * Save selection to database.
   */
  protected function saveSelection($values, $message = TRUE, FormStateInterface $form_state = NULL) {
    $database = Database::getConnection();
    
    // Convert is_ok_ref to is_ok for compatibility.
    if (isset($values['is_ok_ref'])) {
      $values['is_ok'] = $values['is_ok_ref'];
    }
    
    // Normalize values.
    $is_ok = $values['is_ok'] ?? 0;
    $field = $values['field'] ?? NULL;
    $submission_id = $values['submission'] ?? NULL;
    $user_id = $values['user'] ?? NULL;
    $tid = $values['tid'] ?? 0;
    
    // If values are missing, try to get them from form_state storage
    if ($form_state) {
      if (empty($field)) {
        $field = $form_state->get('field');
      }
      if (empty($submission_id)) {
        $submission_id = $form_state->get('submission_id');
      }
      if (empty($user_id)) {
        $user_id = $form_state->get('user_id');
      }
      if (empty($tid)) {
        $tid = $form_state->get('tid');
      }
    }
    
    // Validate required values.
    if (empty($submission_id)) {
      \Drupal::logger('entypa_praxis')->error('Missing submission_id in saveSelection (Referente). user: @user, tid: @tid, field: @field', [
        '@user' => $user_id ?? 'NULL',
        '@tid' => $tid,
        '@field' => $field ?? 'NULL',
      ]);
      return;
    }
    
    if (empty($field)) {
      \Drupal::logger('entypa_praxis')->error('Missing field in saveSelection (Referente) for submission @sid', [
        '@sid' => $submission_id,
      ]);
      return;
    }
    
    // Get tid and user_id if not set.
    if ($tid == 0 || empty($user_id)) {
      $result = $database->query(
        'SELECT t.tid, s.entity_id, s.uid FROM {entypa_praxis} t 
         INNER JOIN {webform_submission} s ON t.submission_id = s.sid 
         WHERE t.submission_id = :sid',
        [':sid' => $submission_id]
      )->fetchAssoc();
      
      if ($result) {
        if ($tid == 0) {
          $tid = $result['tid'];
        }
        if (empty($user_id)) {
          $user_id = $result['uid'];
        }
      }
    }
    
    // If still no user_id, get it from webform_submission.
    if (empty($user_id)) {
      $user_id = $database->query(
        'SELECT uid FROM {webform_submission} WHERE sid = :sid',
        [':sid' => $submission_id]
      )->fetchField();
    }
    
    // Final validation.
    if (empty($user_id)) {
      \Drupal::logger('entypa_praxis')->error('Could not determine user_id for submission @sid (Referente)', [
        '@sid' => $submission_id,
      ]);
      return;
    }
    
    // Determine evaso_data - visto_referente doesn't set evaso_data.
    $evaso_data = NULL;
    if (!in_array($field, ['protocollo', 'visto_referente']) && 
        !($field == 'concedibile' && $is_ok < 2)) {
      $evaso_data = \Drupal::time()->getRequestTime();
    }

    if ($tid == 0) {
      // Insert new record.
      try {
        $database->insert('entypa_praxis')
          ->fields([
            'user_id' => $user_id,
            'submission_id' => $submission_id,
            $field . '_stato' => $is_ok,
            'evaso_data' => $evaso_data,
          ])
          ->execute();
          
        \Drupal::logger('entypa_praxis')->info('Created record for submission @sid with user_id @uid, field @field = @value (Referente)', [
          '@sid' => $submission_id,
          '@uid' => $user_id,
          '@field' => $field,
          '@value' => $is_ok,
        ]);
      }
      catch (\Exception $e) {
        \Drupal::logger('entypa_praxis')->error('Error creating record for submission @sid (Referente): @error', [
          '@sid' => $submission_id,
          '@error' => $e->getMessage(),
        ]);
        return;
      }
    }
    else {
      // Update existing record.
      try {
        $database->update('entypa_praxis')
          ->fields([
            'user_id' => $user_id,
            'submission_id' => $submission_id,
            $field . '_stato' => $is_ok,
            'evaso_data' => $evaso_data,
          ])
          ->condition('tid', $tid)
          ->execute();
          
        \Drupal::logger('entypa_praxis')->info('Updated record @tid for submission @sid, field @field = @value (Referente)', [
          '@tid' => $tid,
          '@sid' => $submission_id,
          '@field' => $field,
          '@value' => $is_ok,
        ]);
      }
      catch (\Exception $e) {
        \Drupal::logger('entypa_praxis')->error('Error updating record @tid (Referente): @error', [
          '@tid' => $tid,
          '@error' => $e->getMessage(),
        ]);
        return;
      }
    }

    if ($message) {
      $this->messenger()->addStatus(
        $this->t('Salvato campo @field istanza @sid.', [
          '@field' => $field,
          '@sid' => $submission_id,
        ])
      );
    }
  }

}
