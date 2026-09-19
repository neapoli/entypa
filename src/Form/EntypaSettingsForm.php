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
    $form['istituto']['patrono'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ricorrenza del santo patrono'),
      '#description' => $this->t("Giorno e mese nella forma <em>GG/MM</em>, ad esempio <em>24/06</em>. È considerato giorno festivo per la sede di servizio (art. 14 del CCNL 29/11/2007) e viene escluso dal conteggio dei giorni di ferie. Lasciare vuoto se non si vuole applicarlo."),
      '#default_value' => _entypa_patrono_in_formato_italiano($config->get('patrono') ?: ''),
      '#size' => 10,
      '#placeholder' => '24/06',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $patrono = trim((string) $form_state->getValue('patrono'));
    if ($patrono === '') {
      return;
    }

    // Si accetta GG/MM: l'anno non serve, la ricorrenza è fissa. Il 2024 è
    // bisestile, così un eventuale 29/02 non viene rifiutato.
    if (!preg_match('#^(\\d{1,2})/(\\d{1,2})$#', $patrono, $parti)
      || !checkdate((int) $parti[2], (int) $parti[1], 2024)) {
      $form_state->setErrorByName('patrono', $this->t('Indicare la ricorrenza nella forma GG/MM, ad esempio 24/06.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Memorizzato come mm-gg, la stessa forma con cui sono elencate le altre
    // festività nel conteggio dei giorni lavorativi.
    $patrono = trim((string) $form_state->getValue('patrono'));
    if ($patrono !== '' && preg_match('#^(\\d{1,2})/(\\d{1,2})$#', $patrono, $parti)) {
      $patrono = sprintf('%02d-%02d', (int) $parti[2], (int) $parti[1]);
    }

    $this->config('entypa.settings')
      ->set('mail_istituto', trim($form_state->getValue('mail_istituto')))
      ->set('patrono', $patrono)
      ->save();

    parent::submitForm($form, $form_state);
  }

}