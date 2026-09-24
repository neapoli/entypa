<?php

namespace Drupal\entypa_praxis\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Configure Entýpa — Praxis settings.
 */
class EntypaPraxisSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entypa_praxis_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['entypa_praxis.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('entypa_praxis.settings');

    // Get all node types.
    $node_types = NodeType::loadMultiple();
    $my_nodes = [];
    foreach ($node_types as $node_type) {
      $my_nodes[$node_type->id()] = $node_type->label();
    }

    // Quello che serve anche senza istruttoria sta in Entýpa: qui un rimando,
    // perché chi cerca la segnatura in Praxis sappia dove trovarla.
    $form['rimando_entypa'] = [
      '#type' => 'item',
      '#markup' => $this->t('Dati dell\'istituto, segnatura di protocollo — anche quella sulle istanze evase — e referenti di plesso si impostano nelle <a href=":url">impostazioni di Entýpa</a>.', [
        ':url' => \Drupal\Core\Url::fromRoute('entypa.settings')->toString(),
      ]),
    ];

    // Tipi di contenuto.
    $form['entypa_praxis_content1'] = [
      '#type' => 'details',
      '#title' => $this->t('Tipi di contenuto'),
      '#open' => TRUE,
    ];
    $form['entypa_praxis_content1']['entypa_praxis_node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Tipi di contenuto usati per le istanze'),
      '#default_value' => $config->get('node_types') ?: [],
      '#options' => $my_nodes,
    ];

    // Operatori economici.
    $form['entypa_praxis_content1bis'] = [
      '#type' => 'details',
      '#title' => $this->t('Operatori economici'),
      '#open' => FALSE,
    ];
    $form['entypa_praxis_content1bis']['entypa_praxis_oe'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Attiva modalità operatori economici'),
      '#default_value' => $config->get('oe') ?: FALSE,
    ];

    // Email della segreteria.
    $form['entypa_praxis_content2'] = [
      '#type' => 'details',
      '#title' => $this->t('Email della segreteria'),
      '#open' => TRUE,
    ];
    $form['entypa_praxis_content2']['entypa_praxis_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Indirizzo e-mail'),
      '#maxlength' => 254,
      '#description' => $this->t('Indirizzo e-mail valido'),
      '#required' => TRUE,
      '#default_value' => $config->get('email') ?: '',
    ];
    $form['entypa_praxis_content2']['entypa_praxis_notification_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Invia, in modo predefinito, email di istanza evasa alla segreteria'),
      '#default_value' => $config->get('notification_email') ?: FALSE,
    ];

    // Testo delle email di notifica.
    $form['entypa_praxis_content3'] = [
      '#type' => 'details',
      '#title' => $this->t('Testo delle email di notifica'),
      // I campi qui sotto rimandano ai «riferimenti come sopra»: sopra non
      // c'era niente, ed è questo l'elenco a cui si riferiscono.
      '#description' => $this->t('Riferimenti utilizzabili in tutti i testi di questa sezione:<br>@segnaposto<br><br>@istituto riporta la denominazione ufficiale dell\'Istituto, se compilata più in basso, altrimenti il nome del sito. Se la posta del sito esce in formato HTML, per andare a capo occorre @tag.', [
        '@segnaposto' => '@istituto, @nomesito, @nome, @codice_fiscale, @istanza, @istanza_id, @istanza_serial, @data, @esito, @motivazioni, @operatore, @qualifica_operatore',
        '@tag' => '<br>',
      ]),
      '#open' => FALSE,
    ];
    $form['entypa_praxis_content3']['entypa_praxis_notification_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Notifica all'utente"),
      '#description' => $this->t("È possibile modificare il testo del messaggio utilizzando i riferimenti preceduti da @ come da esempio."),
      '#default_value' => $config->get('notification_body') ?: "La sua istanza @istanza, n. @istanza_id del @data, è stata @esito.",
    ];
    $form['entypa_praxis_content3']['entypa_praxis_segreteria_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Oggetto email alla segreteria"),
      '#description' => $this->t("È possibile modificare il testo dell'oggetto utilizzando i riferimenti come sopra."),
      '#default_value' => $config->get('segreteria_subject') ?: "Notifica istanza evasa - @istanza n. @istanza_serial - @nome",
    ];
    $form['entypa_praxis_content3']['entypa_praxis_segreteria_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Email alla segreteria"),
      '#description' => $this->t("È possibile modificare il testo del messaggio utilizzando i riferimenti come sopra."),
      '#default_value' => $config->get('segreteria_body') ?: "L'istanza @istanza, n. @istanza_id del @data presentata da @nome, è stata @esito.",
    ];
    $form['entypa_praxis_content3']['entypa_praxis_annullata_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Oggetto email di istanza annullata"),
      '#default_value' => $config->get('annullata_subject') ?: "Istanza annullata - @istanza n. @istanza_serial",
    ];
    $form['entypa_praxis_content3']['entypa_praxis_annullata_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Notifica di annullamento all'utente"),
      '#rows' => 2,
      '#default_value' => $config->get('annullata_body') ?: "La sua istanza @istanza, n. @istanza_id del @data, è stata annullata.",
    ];
    $form['entypa_praxis_content3']['entypa_praxis_notification_footer'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Saluti e firma"),
      '#description' => $this->t("È possibile modificare il testo del messaggio utilizzando i riferimenti come sopra."),
      '#default_value' => $config->get('notification_footer') ?: "Cordiali saluti,\n@istituto",
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('entypa_praxis.settings')
      ->set('node_types', $form_state->getValue('entypa_praxis_node_types'))
      ->set('oe', $form_state->getValue('entypa_praxis_oe'))
      ->set('email', $form_state->getValue('entypa_praxis_email'))
      ->set('notification_email', $form_state->getValue('entypa_praxis_notification_email'))
      ->set('notification_body', $form_state->getValue('entypa_praxis_notification_body'))
      ->set('segreteria_subject', $form_state->getValue('entypa_praxis_segreteria_subject'))
      ->set('segreteria_body', $form_state->getValue('entypa_praxis_segreteria_body'))
      ->set('annullata_subject', $form_state->getValue('entypa_praxis_annullata_subject'))
      ->set('annullata_body', $form_state->getValue('entypa_praxis_annullata_body'))
      ->set('notification_footer', $form_state->getValue('entypa_praxis_notification_footer'))
      ->save();

    // Reset fields after saving configuration.
    $this->resetFields($form_state);

    parent::submitForm($form, $form_state);
  }

  /**
   * Reset fields helper function.
   */
  protected function resetFields(FormStateInterface $form_state) {
    // Use the field manager service to reset fields.
    $field_manager = \Drupal::service('entypa_praxis.field_manager');
    $field_manager->resetFields();
  }

}
