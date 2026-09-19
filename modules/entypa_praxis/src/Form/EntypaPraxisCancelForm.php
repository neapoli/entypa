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
   * Constructs a new EntypaPraxisCancelForm.
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
        'class' => ['button-si', 'deleteButtonClass'],
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
    
    // Get module path for icon.
    $module_path = \Drupal::service('extension.list.module')->getPath('entypa_praxis');
    $timestamp = \Drupal::time()->getRequestTime();
    $formatted_date = $this->dateFormatter->format($timestamp, 'short');
    
    // Replace the cancel button with canceled status.
    $html = sprintf(
      '<span style="white-space:nowrap"><img class="istanza-annullata" src="/%s/icone/no.png" alt="%s" title="%s" /> (%s)</span>',
      $module_path,
      $this->t('istanza annullata'),
      $this->t('istanza annullata'),
      $formatted_date
    );
    
    // Add script to hide the row.
    $html .= sprintf(
      '<script type="text/javascript">
        (function() {
          var node = document.getElementById("can0%d");
          if (node) {
            while (node.nodeName.toLowerCase() !== "tr") {
              node = node.parentNode;
            }
            node.style.display = "none";
          }
        })();
      </script>',
      $sid
    );
    
    $response->addCommand(new ReplaceCommand('#can' . $sid, $html));
    $response->addCommand(new ReplaceCommand('#done' . $sid, '---'));
    
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
      ])
      ->condition('tid', $values['tid'])
      ->execute();

    if ($message) {
      $this->messenger()->addStatus(
        $this->t('Annullata istanza id @sid.', ['@sid' => $values['submission']])
      );
    }
  }

}
