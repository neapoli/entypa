<?php

namespace Drupal\entypa_praxis\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Form for inline field updates (protocollo, etc).
 */
class EntypaPraxisInlineForm extends FormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new EntypaPraxisInlineForm.
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
    // Make form ID unique per submission to avoid confusion when multiple forms are on the same page
    // The SID will be appended when the form is built
    return 'entypa_praxis_inline_form';
  }
  
  /**
   * Get a unique form ID for this specific submission.
   */
  protected function getUniqueFormId($sid) {
    return 'entypa_praxis_inline_form_' . $sid;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $sid = NULL, $uid = NULL, $tid = NULL, $checked = NULL, $field = NULL) {

    // Set unique form ID to avoid conflicts with multiple forms on same page
    $form['#id'] = $this->getUniqueFormId($sid);
    
    // Store values in form state storage so they persist across AJAX callbacks
    $form_state->set('submission_id', $sid);
    $form_state->set('user_id', $uid);
    $form_state->set('tid', $tid);
    $form_state->set('field', $field);

    // Wrap the entire form in a span with ID for AJAX replacement
    // Always use SID for Protocollo field because it remains constant
    // (TID might be 0 initially and get created on first save)
    $form['#prefix'] = '<span id="pro' . $sid . '">';
    $form['#suffix'] = '</span>';

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
      $tempstore->set('si_dest_' . $sid, $current_path);
    }

    $destination = $tempstore->get('si_dest_' . $sid) ?: $current_path;

    $form['destination'] = [
      '#type' => 'hidden',
      '#value' => $destination,
    ];

    $form['form_instance_id'] = [
      '#type' => 'hidden',
      '#value' => $sid,
    ];

    $form['is_ok'] = [
      '#type' => 'checkbox',
      '#default_value' => $checked,
      // Remove Drupal AJAX - we'll handle it with custom JS
      '#attributes' => [
        'title' => $this->t('Selezionare per cambiare lo stato'),
        'data-sid' => $sid,
        'data-tid' => $tid,
        'class' => ['entypa-praxis-protocollo-checkbox'],
      ],
    ];
    
    // Attach custom JavaScript library
    $form['#attached']['library'][] = 'entypa_praxis/protocollo-ajax';

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
   * Note: This is the old Drupal AJAX callback that had issues with multiple forms.
   * We now use ajaxProtocolloEndpoint() with custom JavaScript instead.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    // Get SID from the form_instance_id hidden field
    $values = $form_state->getValues();
    $sid = $values['form_instance_id'] ?? $values['submission'] ?? NULL;
    $tid = $values['tid'] ?? NULL;
    $is_checked = isset($values['is_ok']) && ($values['is_ok'] === 'on' || $values['is_ok'] == 1);
    
    // Use values from attributes if available, otherwise from form values
    if (!$sid) {
      $sid = $sid_from_values;
    }
    if (!$tid) {
      $tid = $tid_from_values;
    }

    // Check permission.
    if (!$this->currentUser->hasPermission('protocollato')) {
      return new AjaxResponse();
    }

    // Save selection - we need to pass the correct values
    $save_values = [
      'submission' => $sid,
      'tid' => $tid,
      'is_ok' => $is_checked,
      'field' => 'protocollo',
    ];
    $this->saveSelection($save_values, FALSE, $form_state);

    $response = new AjaxResponse();
    $module_path = \Drupal::service('extension.list.module')->getPath('entypa_praxis');

    // Update protocollo icon/checkbox (always use SID, not TID)
    if ($is_checked) {
      // Show icon when checked
      $icon_html = sprintf(
        '<span id="pro%d"><img src="/%s/icone/si.png" alt="%s" title="%s" /></span>',
        $sid,
        $module_path,
        $this->t('protocollato'),
        $this->t('protocollato')
      );
      $response->addCommand(new ReplaceCommand('#pro' . $sid, $icon_html));
    } else {
      // Show "---" when unchecked
      $response->addCommand(new ReplaceCommand('#pro' . $sid, '<span id="pro' . $sid . '">---</span>'));
    }

    // Update "verifica ufficio" link if checked and user has permission
    if ($is_checked &&
      $this->currentUser->hasPermission('acquisito') &&
      !empty($sid) &&
      !empty($tid)) {

      $database = Database::getConnection();

      // Get node id
      $nid = $database->query(
        'SELECT s.entity_id FROM {webform_submission} s
         INNER JOIN {entypa_praxis} t ON s.sid = t.submission_id
         WHERE t.tid = :tid AND s.entity_type = :entity_type',
        [
          ':tid' => $tid,
          ':entity_type' => 'node'
        ]
      )->fetchField();

      if ($nid) {
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
        $acquisisci = FALSE;
        if ($node && $node->hasField('field_si_acquisisci')) {
          $acquisisci = (bool) $node->get('field_si_acquisisci')->value;
        }

        $status = 'da valutare';

        // Get clean destination without Ajax parameters
        $request = \Drupal::request();
        $destination = $request->getPathInfo();
        
        // Clean up any Ajax-related query parameters
        $query_params = $request->query->all();
        $clean_params = [];
        foreach ($query_params as $key => $value) {
          // Exclude Ajax and wrapper format parameters
          if (!in_array($key, ['ajax_form', '_wrapper_format', 'ajax_page_state', '_drupal_ajax'])) {
            $clean_params[$key] = $value;
          }
        }
        
        // Build destination with clean parameters
        if (!empty($clean_params)) {
          $destination .= '?' . http_build_query($clean_params);
        }

        // Create URL using Url::fromRoute
        $url = Url::fromRoute(
          $acquisisci ? 'entypa_praxis.acquisisci' : 'entypa_praxis.concedi',
          [
            'node' => $nid,
            'submission' => $sid,
            'tid' => $tid,
          ],
          [
            'query' => ['destination' => $destination],
            'attributes' => [
              'title' => $this->t('clicca qui per valutare'),
              'class' => ['no-ajax'],
              'data-drupal-link-system-path' => 'node/' . $nid . '/submission/' . $sid . '/istanza-' . ($acquisisci ? 'acquisisci' : 'concedi') . '/' . $tid,
            ],
          ]
        );

        $html = sprintf(
          '<span id="sid%d"><a title="%s" href="%s" class="no-ajax">%s</a></span>',
          $sid,
          $this->t('clicca qui per valutare'),
          $url->toString(),
          $status
        );

        $response->addCommand(new ReplaceCommand('#sid' . $sid, $html));
      }
    }
    elseif (!$is_checked && !empty($sid)) {
      // Remove link when unchecked
      $response->addCommand(new ReplaceCommand('#sid' . $sid, '<span id="sid' . $sid . '">---</span>'));
    }

    return $response;
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

    // Normalize is_ok value (checkbox returns 'on' or 0).
    $is_ok = isset($values['is_ok']) && ($values['is_ok'] === 'on' || $values['is_ok'] == 1) ? 1 : 0;

    // Get values from form values first, then fall back to form_state storage
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
      \Drupal::logger('entypa_praxis')->error('Missing submission_id in saveSelection. user: @user, tid: @tid, field: @field', [
        '@user' => $user_id ?? 'NULL',
        '@tid' => $tid,
        '@field' => $field ?? 'NULL',
      ]);
      return;
    }

    if (empty($field)) {
      \Drupal::logger('entypa_praxis')->error('Missing field in saveSelection for submission @sid', [
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
      \Drupal::logger('entypa_praxis')->error('Could not determine user_id for submission @sid', [
        '@sid' => $submission_id,
      ]);
      return;
    }

    // Determine evaso_data based on workflow and field_si_acquisisci flag.
    $evaso_data = NULL;
    
    // Get node to check field_si_acquisisci and field_si_dsga
    $nid = $database->query(
      'SELECT s.entity_id FROM {webform_submission} s WHERE s.sid = :sid',
      [':sid' => $submission_id]
    )->fetchField();
    
    $usa_visto_dsga = FALSE;
    $acquisisci = FALSE;
    
    if ($nid) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      if ($node) {
        if ($node->hasField('field_si_dsga')) {
          $usa_visto_dsga = (bool) $node->get('field_si_dsga')->value;
        }
        if ($node->hasField('field_si_acquisisci')) {
          $acquisisci = (bool) $node->get('field_si_acquisisci')->value;
        }
      }
    }
    
    // Determine when to set evaso_data
    
    // ALWAYS set evaso if rejected (2) or suspended (3)
    if ($is_ok == 2 || $is_ok == 3) {
      $evaso_data = \Drupal::time()->getRequestTime();
    }
    elseif ($acquisisci) {
      // Modalità acquisizione (senza visto DS): evaso quando finisce il workflow
      if ($usa_visto_dsga) {
        // Con DSGA: evaso quando DSGA firma
        if ($field == 'visto_dsga' && $is_ok == 1) {
          $evaso_data = \Drupal::time()->getRequestTime();
        }
      }
      else {
        // Senza DSGA: evaso quando verifica ufficio approva
        if ($field == 'concedibile' && $is_ok == 1) {
          $evaso_data = \Drupal::time()->getRequestTime();
        }
      }
    }
    else {
      // Modalità normale (con visto DS): evaso SOLO quando DS firma
      // Il visto DSGA è un passaggio intermedio, NON finale
      if ($field == 'visto' && $is_ok == 1) {
        $evaso_data = \Drupal::time()->getRequestTime();
      }
    }
    
    // Protocollo e visto_referente non impostano mai evaso_data
    if (in_array($field, ['protocollo', 'visto_referente'])) {
      $evaso_data = NULL;
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

        \Drupal::logger('entypa_praxis')->info('Created record for submission @sid with user_id @uid, field @field = @value', [
          '@sid' => $submission_id,
          '@uid' => $user_id,
          '@field' => $field,
          '@value' => $is_ok,
        ]);
      }
      catch (\Exception $e) {
        \Drupal::logger('entypa_praxis')->error('Error creating record for submission @sid: @error', [
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

        \Drupal::logger('entypa_praxis')->info('Updated record @tid for submission @sid, field @field = @value', [
          '@tid' => $tid,
          '@sid' => $submission_id,
          '@field' => $field,
          '@value' => $is_ok,
        ]);
      }
      catch (\Exception $e) {
        \Drupal::logger('entypa_praxis')->error('Error updating record @tid: @error', [
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
      )
;
    }
  }

  /**
   * Custom AJAX endpoint for protocollo checkbox.
   * This bypasses Drupal's form AJAX system which gets confused with multiple forms.
   */
  public function ajaxProtocolloEndpoint() {
    $request = \Drupal::request();
    $sid = $request->request->get('sid');
    $tid = $request->request->get('tid');
    $is_checked = $request->request->get('checked') == 1;

    // Check permission
    if (!\Drupal::currentUser()->hasPermission('protocollato')) {
      return new AjaxResponse();
    }

    // Save selection
    $values = [
      'submission' => $sid,
      'tid' => $tid,
      'is_ok' => $is_checked ? 1 : 0,
      'field' => 'protocollo',
    ];
    $this->saveSelection($values, FALSE, NULL);

    $response = new AjaxResponse();
    $module_path = \Drupal::service('extension.list.module')->getPath('entypa_praxis');

    // Update protocollo icon/checkbox
    if ($is_checked) {
      $icon_html = sprintf(
        '<span id="pro%d"><img src="/%s/icone/si.png" alt="%s" title="%s" /></span>',
        $sid,
        $module_path,
        $this->t('protocollato'),
        $this->t('protocollato')
      );
      $response->addCommand(new ReplaceCommand('#pro' . $sid, $icon_html));
    }
    else {
      $response->addCommand(new ReplaceCommand('#pro' . $sid, '<span id="pro' . $sid . '">---</span>'));
    }

    // Update "verifica ufficio" link if checked and user has permission
    if ($is_checked &&
      $this->currentUser->hasPermission('acquisito') &&
      !empty($sid) &&
      !empty($tid)) {

      $database = Database::getConnection();

      // Get node id
      $nid = $database->query(
        'SELECT s.entity_id FROM {webform_submission} s
         INNER JOIN {entypa_praxis} t ON s.sid = t.submission_id
         WHERE t.tid = :tid AND s.entity_type = :entity_type',
        [
          ':tid' => $tid,
          ':entity_type' => 'node'
        ]
      )->fetchField();

      if ($nid) {
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
        $acquisisci = FALSE;
        if ($node && $node->hasField('field_si_acquisisci')) {
          $acquisisci = (bool) $node->get('field_si_acquisisci')->value;
        }

        $status = 'da valutare';

        // Get destination from the current_path sent by JavaScript
        // (not from request->getPathInfo() which would be the AJAX endpoint itself)
        $destination = $request->request->get('current_path');
        if (empty($destination)) {
          // Fallback to referer if current_path is not provided
          $destination = $request->headers->get('referer');
          if ($destination) {
            // Extract just the path from the full URL
            $parsed = parse_url($destination);
            $destination = $parsed['path'] ?? '/';
            if (!empty($parsed['query'])) {
              $destination .= '?' . $parsed['query'];
            }
          }
          else {
            $destination = '/admin/entypa-praxis/inviate';
          }
        }
        
        // Clean up any Ajax-related query parameters from destination
        if (strpos($destination, '?') !== FALSE) {
          list($path, $query_string) = explode('?', $destination, 2);
          parse_str($query_string, $query_params);
          $clean_params = [];
          foreach ($query_params as $key => $value) {
            if (!in_array($key, ['ajax_form', '_wrapper_format', 'ajax_page_state', '_drupal_ajax'])) {
              $clean_params[$key] = $value;
            }
          }
          if (!empty($clean_params)) {
            $destination = $path . '?' . http_build_query($clean_params);
          }
          else {
            $destination = $path;
          }
        }

        $url = Url::fromRoute(
          $acquisisci ? 'entypa_praxis.acquisisci' : 'entypa_praxis.concedi',
          [
            'node' => $nid,
            'submission' => $sid,
            'tid' => $tid,
          ],
          [
            'query' => ['destination' => $destination],
            'attributes' => [
              'title' => $this->t('clicca qui per valutare'),
              'class' => ['no-ajax'],
              'data-drupal-link-system-path' => 'node/' . $nid . '/submission/' . $sid . '/istanza-' . ($acquisisci ? 'acquisisci' : 'concedi') . '/' . $tid,
            ],
          ]
        );

        $html = sprintf(
          '<span id="sid%d"><a title="%s" href="%s" class="no-ajax">%s</a></span>',
          $sid,
          $this->t('clicca qui per valutare'),
          $url->toString(),
          $status
        );

        $response->addCommand(new ReplaceCommand('#sid' . $sid, $html));
      }
    }
    elseif (!$is_checked && !empty($sid)) {
      $response->addCommand(new ReplaceCommand('#sid' . $sid, '<span id="sid' . $sid . '">---</span>'));
    }

    return $response;
  }

}
