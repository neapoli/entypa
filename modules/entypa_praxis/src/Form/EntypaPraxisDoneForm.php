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

/**
 * Form for marking instances as done/elaborated.
 */
class EntypaPraxisDoneForm extends FormBase {

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
   * Constructs a new EntypaPraxisDoneForm.
   */
  public function __construct(AccountInterface $current_user, DateFormatterInterface $date_formatter) {
    $this->currentUser = $current_user;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entypa_praxis_done_form';
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

    $form['is_ok_done'] = [
      '#type' => 'button',
      '#value' => $this->t('Elabora'),
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'event' => 'doneInstanceEvent_' . $tid,
        'progress' => ['type' => 'none'],
      ],
      '#attributes' => [
        'title' => $this->t('Fare clic per marcare come elaborata'),
        'class' => ['button-si', 'doneButtonClass'],
        'data-tid' => $tid,
        'style' => 'position:relative;top:-1.4em;',
      ],
    ];

    $form['#attributes'] = ['onsubmit' => 'return false'];

    // Attach JavaScript library.
    $form['#attached']['library'][] = 'entypa_praxis/entypa_praxis';

    return $form;
  }

  /**
   * AJAX callback for done button.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $sid = $values['submission'];
    
    // Check permission.
    if (!$this->currentUser->hasPermission('elaborato')) {
      return new AjaxResponse();
    }

    // Mark instance as done.
    $this->markInstanceDone($values, FALSE);
    
    $response = new AjaxResponse();
    
    // Get module path for icon.
    $module_path = \Drupal::service('extension.list.module')->getPath('entypa_praxis');
    $timestamp = \Drupal::time()->getRequestTime();
    $formatted_date = $this->dateFormatter->format($timestamp, 'short');
    
    // Replace the done button with completed status.
    $html = sprintf(
      '<span style="white-space:nowrap"><img src="/%s/icone/si.png" alt="%s" title="%s" /> (%s)</span>',
      $module_path,
      $this->t('istanza elaborata'),
      $this->t('istanza elaborata'),
      $formatted_date
    );
    
    $response->addCommand(new ReplaceCommand('#done' . $sid, $html));
    
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->markInstanceDone($values);
  }

  /**
   * Mark instance as done/elaborated.
   */
  protected function markInstanceDone($values, $message = TRUE) {
    if ($values['tid'] == 0) {
      return;
    }

    $database = Database::getConnection();
    $database->update('entypa_praxis')
      ->fields([
        'elaborato_data' => \Drupal::time()->getRequestTime(),
      ])
      ->condition('tid', $values['tid'])
      ->execute();

    if ($message) {
      $this->messenger()->addStatus(
        $this->t('Elaborata istanza id @sid.', ['@sid' => $values['submission']])
      );
    }
  }

}
