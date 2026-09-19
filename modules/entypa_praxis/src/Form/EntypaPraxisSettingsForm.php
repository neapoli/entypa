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
    $form['entypa_praxis_content3']['entypa_praxis_notification_footer'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Saluti e firma"),
      '#description' => $this->t("È possibile modificare il testo del messaggio utilizzando i riferimenti come sopra."),
      '#default_value' => $config->get('notification_footer') ?: "Cordiali saluti,\n@operatore (@qualifica_operatore).",
    ];

    // File di segnatura allegato email.
    $form['entypa_praxis_content4'] = [
      '#type' => 'details',
      '#title' => $this->t('File di segnatura allegato email'),
      '#open' => FALSE,
    ];
    $form['entypa_praxis_content4']['entypa_praxis_instance_signature'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Segnatura su trasmissione istanza'),
      '#default_value' => $config->get('instance_signature') ?: FALSE,
    ];
    $form['entypa_praxis_content4']['entypa_praxis_evaded_signature'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Segnatura su istanza evasa'),
      '#default_value' => $config->get('evaded_signature') ?: FALSE,
    ];
    $form['entypa_praxis_content4']['entypa_praxis_email_protocollo'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail da filtrare'),
      '#description' => $this->t('Indirizzo e-mail da filtrare'),
      '#default_value' => $config->get('email_protocollo') ?: '',
    ];
    $form['entypa_praxis_content4']['entypa_praxis_nome_scuola'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Denominazione ufficiale dell'Istituto"),
      '#default_value' => $config->get('nome_scuola') ?: '',
    ];
    $form['entypa_praxis_content4']['entypa_praxis_toponimo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Toponimo (via o piazza etc.)'),
      '#default_value' => $config->get('toponimo') ?: '',
    ];
    $form['entypa_praxis_content4']['entypa_praxis_civico'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Numero civico'),
      '#default_value' => $config->get('civico') ?: '',
    ];
    $form['entypa_praxis_content4']['entypa_praxis_cap'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CAP'),
      '#default_value' => $config->get('cap') ?: '',
    ];
    $form['entypa_praxis_content4']['entypa_praxis_comune'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Comune'),
      '#default_value' => $config->get('comune') ?: '',
    ];

    // Province italiane.
    $province = [
      'AG' => 'Agrigento',
      'AL' => 'Alessandria',
      'AN' => 'Ancona',
      'AO' => 'Aosta',
      'AR' => 'Arezzo',
      'AP' => 'Ascoli Piceno',
      'AT' => 'Asti',
      'AV' => 'Avellino',
      'BA' => 'Bari',
      'BT' => 'Barletta-Andria-Trani',
      'BL' => 'Belluno',
      'BN' => 'Benevento',
      'BG' => 'Bergamo',
      'BI' => 'Biella',
      'BO' => 'Bologna',
      'BZ' => 'Bolzano',
      'BS' => 'Brescia',
      'BR' => 'Brindisi',
      'CA' => 'Cagliari',
      'CL' => 'Caltanissetta',
      'CB' => 'Campobasso',
      'CI' => 'Carbonia-Iglesias',
      'CE' => 'Caserta',
      'CT' => 'Catania',
      'CZ' => 'Catanzaro',
      'CH' => 'Chieti',
      'CO' => 'Como',
      'CS' => 'Cosenza',
      'CR' => 'Cremona',
      'KR' => 'Crotone',
      'CN' => 'Cuneo',
      'EN' => 'Enna',
      'FM' => 'Fermo',
      'FE' => 'Ferrara',
      'FI' => 'Firenze',
      'FG' => 'Foggia',
      'FC' => 'Forlì-Cesena',
      'FR' => 'Frosinone',
      'GE' => 'Genova',
      'GO' => 'Gorizia',
      'GR' => 'Grosseto',
      'IM' => 'Imperia',
      'IS' => 'Isernia',
      'SP' => 'La Spezia',
      'AQ' => 'L\'Aquila',
      'LT' => 'Latina',
      'LE' => 'Lecce',
      'LC' => 'Lecco',
      'LI' => 'Livorno',
      'LO' => 'Lodi',
      'LU' => 'Lucca',
      'MC' => 'Macerata',
      'MN' => 'Mantova',
      'MS' => 'Massa-Carrara',
      'MT' => 'Matera',
      'ME' => 'Messina',
      'MI' => 'Milano',
      'MO' => 'Modena',
      'MB' => 'Monza e della Brianza',
      'NA' => 'Napoli',
      'NO' => 'Novara',
      'NU' => 'Nuoro',
      'OT' => 'Olbia-Tempio',
      'OR' => 'Oristano',
      'PD' => 'Padova',
      'PA' => 'Palermo',
      'PR' => 'Parma',
      'PV' => 'Pavia',
      'PG' => 'Perugia',
      'PU' => 'Pesaro e Urbino',
      'PE' => 'Pescara',
      'PC' => 'Piacenza',
      'PI' => 'Pisa',
      'PT' => 'Pistoia',
      'PN' => 'Pordenone',
      'PZ' => 'Potenza',
      'PO' => 'Prato',
      'RG' => 'Ragusa',
      'RA' => 'Ravenna',
      'RC' => 'Reggio Calabria',
      'RE' => 'Reggio Emilia',
      'RI' => 'Rieti',
      'RN' => 'Rimini',
      'RM' => 'Roma',
      'RO' => 'Rovigo',
      'SA' => 'Salerno',
      'VS' => 'Medio Campidano',
      'SS' => 'Sassari',
      'SV' => 'Savona',
      'SI' => 'Siena',
      'SR' => 'Siracusa',
      'SO' => 'Sondrio',
      'TA' => 'Taranto',
      'TE' => 'Teramo',
      'TR' => 'Terni',
      'TO' => 'Torino',
      'OG' => 'Ogliastra',
      'TP' => 'Trapani',
      'TN' => 'Trento',
      'TV' => 'Treviso',
      'TS' => 'Trieste',
      'UD' => 'Udine',
      'VA' => 'Varese',
      'VE' => 'Venezia',
      'VB' => 'Verbano-Cusio-Ossola',
      'VC' => 'Vercelli',
      'VR' => 'Verona',
      'VV' => 'Vibo Valentia',
      'VI' => 'Vicenza',
      'VT' => 'Viterbo',
    ];
    $form['entypa_praxis_content4']['entypa_praxis_provincia'] = [
      '#type' => 'select',
      '#title' => $this->t('Provincia'),
      '#options' => $province,
      '#default_value' => $config->get('provincia') ?: 'AG',
    ];
    $form['entypa_praxis_content4']['entypa_praxis_fax'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fax'),
      '#default_value' => $config->get('fax') ?: '',
    ];
    $form['entypa_praxis_content4']['entypa_praxis_telefono'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Telefono'),
      '#default_value' => $config->get('telefono') ?: '',
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
      ->set('notification_footer', $form_state->getValue('entypa_praxis_notification_footer'))
      ->set('instance_signature', $form_state->getValue('entypa_praxis_instance_signature'))
      ->set('evaded_signature', $form_state->getValue('entypa_praxis_evaded_signature'))
      ->set('email_protocollo', $form_state->getValue('entypa_praxis_email_protocollo'))
      ->set('nome_scuola', $form_state->getValue('entypa_praxis_nome_scuola'))
      ->set('toponimo', $form_state->getValue('entypa_praxis_toponimo'))
      ->set('civico', $form_state->getValue('entypa_praxis_civico'))
      ->set('cap', $form_state->getValue('entypa_praxis_cap'))
      ->set('comune', $form_state->getValue('entypa_praxis_comune'))
      ->set('provincia', $form_state->getValue('entypa_praxis_provincia'))
      ->set('fax', $form_state->getValue('entypa_praxis_fax'))
      ->set('telefono', $form_state->getValue('entypa_praxis_telefono'))
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
