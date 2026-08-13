<?php

namespace Drupal\entypa\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Entýpa settings.
 */
class EntypaSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entypa_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['entypa.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('entypa.settings');

    $form['istituto'] = [
      '#type' => 'details',
      '#title' => $this->t("Dati dell'istituto"),
      '#open' => TRUE,
    ];
    $form['istituto']['mail_istituto'] = [
      '#type' => 'email',
      '#title' => $this->t("Email dell'istituto"),
      '#description' => $this->t("Indirizzo a cui vengono inviate le istanze del personale. Il valore viene usato dal campo <em>mail_istituto</em> di tutti i formulari e dal token <em>[entypa:mail-istituto]</em>. Se lasciato vuoto viene usata l'email del sito (@mail).", [
        '@mail' => $this->config('system.site')->get('mail'),
      ]),
      '#default_value' => $config->get('mail_istituto') ?: '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('entypa.settings')
      ->set('mail_istituto', trim($form_state->getValue('mail_istituto')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}