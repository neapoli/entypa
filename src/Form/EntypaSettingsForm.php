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
    $nomi = ['entypa.settings'];
    // La segnatura sulle istanze evase è di Praxis e si salva nella sua
    // configurazione, ma si imposta qui, accanto all'altra.
    if (\Drupal::moduleHandler()->moduleExists('entypa_praxis')) {
      $nomi[] = 'entypa_praxis.settings';
    }
    return $nomi;
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
    $form['istituto']['nome_scuola'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Denominazione ufficiale dell'istituto"),
      '#description' => $this->t("Compare nelle email e nella segnatura di protocollo. Se lasciata vuota viene usato il nome del sito."),
      '#default_value' => $config->get('nome_scuola') ?: '',
    ];
    $form['istituto']['codice_ipa'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Codice IPA'),
      '#description' => $this->t("Il codice dell'istituto sull'<a href=\":url\" target=\"_blank\">Indice dei domicili digitali della PA</a>: per le scuole è di solito <em>istsc_</em> seguito dal codice meccanografico.", [':url' => 'https://www.indicepa.gov.it']),
      '#default_value' => $config->get('codice_ipa') ?: '',
      '#size' => 30,
    ];
    $form['istituto']['codice_aoo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Codice AOO'),
      '#description' => $this->t("Il codice dell'area organizzativa omogenea, sullo stesso Indice."),
      '#default_value' => $config->get('codice_aoo') ?: '',
      '#size' => 30,
    ];
    $form['istituto']['toponimo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Via o piazza'),
      '#default_value' => $config->get('toponimo') ?: '',
    ];
    $form['istituto']['civico'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Numero civico'),
      '#default_value' => $config->get('civico') ?: '',
      '#size' => 10,
    ];
    $form['istituto']['cap'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CAP'),
      '#default_value' => $config->get('cap') ?: '',
      '#size' => 10,
    ];
    $form['istituto']['comune'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Comune'),
      '#default_value' => $config->get('comune') ?: '',
    ];
    $form['istituto']['provincia'] = [
      '#type' => 'select',
      '#title' => $this->t('Provincia'),
      '#options' => entypa_province(),
      '#empty_option' => $this->t('- Scegliere -'),
      '#default_value' => $config->get('provincia') ?: '',
    ];
    $form['istituto']['telefono'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Telefono'),
      '#default_value' => $config->get('telefono') ?: '',
      '#size' => 20,
    ];
    $form['istituto']['fax'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fax'),
      '#default_value' => $config->get('fax') ?: '',
      '#size' => 20,
    ];

    $form['segnatura'] = [
      '#type' => 'details',
      '#title' => $this->t('Segnatura di protocollo'),
      '#description' => $this->t("Il file <em>Segnatura.xml</em> allegato all'email permette al software di protocollo della scuola di registrarla già compilata. Il formato è quello letto dai software di protocollo scolastici; non è la segnatura firmata prevista dalle Linee guida AgID per le comunicazioni fra amministrazioni."),
      '#open' => FALSE,
    ];
    $form['segnatura']['instance_signature'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Allega la segnatura all'email con cui l'istanza arriva alla scuola"),
      '#default_value' => (bool) $config->get('instance_signature'),
    ];
    if (\Drupal::moduleHandler()->moduleExists('entypa_praxis')) {
      $form['segnatura']['evaded_signature'] = [
        '#type' => 'checkbox',
        '#title' => $this->t("Allega la segnatura all'email di istanza evasa inviata alla segreteria"),
        '#description' => $this->t("Mittente è la scuola: servono il codice IPA e il codice AOO indicati sopra. Allegati: l'esito e l'istanza, in PDF."),
        '#default_value' => (bool) $this->config('entypa_praxis.settings')->get('evaded_signature'),
      ];
    }

    // Referenti di plesso: ricevono solo la notizia dell'assenza, senza la
    // causale.
    $form['referenti'] = [
      '#type' => 'details',
      '#title' => $this->t('Referenti di plesso'),
      '#description' => $this->t("Quando un'istanza di assenza di un docente viene accolta, i referenti dei plessi in cui presta servizio ricevono un'email con il solo periodo di assenza, senza la causale; se l'istanza viene poi annullata, ricevono un secondo avviso. Con l'istruttoria di Praxis «accolta» vuol dire evasa con esito positivo; senza, l'avviso parte all'invio e il ritiro alla richiesta di annullamento. Le sedi sono quelle del campo «Sede di servizio» del profilo del personale; più indirizzi si separano con una virgola. Una sede senza indirizzi non riceve avvisi."),
      '#open' => FALSE,
      '#tree' => TRUE,
    ];
    $form['referenti']['sedi'] = [
      '#type' => 'table',
      '#header' => [$this->t('Sede di servizio'), $this->t('Email dei referenti')],
      '#empty' => $this->t('Il profilo del personale non ha il campo «Sede di servizio».'),
    ];

    $salvati = [];
    foreach ($config->get('referenti_plesso') ?: [] as $riga) {
      $salvati[$riga['sede']] = $riga['email'];
    }

    foreach ($this->sedi() as $chiave => $etichetta) {
      $form['referenti']['sedi'][$chiave]['etichetta'] = ['#plain_text' => $etichetta];
      $form['referenti']['sedi'][$chiave]['email'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Email dei referenti per @sede', ['@sede' => $etichetta]),
        '#title_display' => 'invisible',
        '#default_value' => $salvati[$chiave] ?? '',
        '#maxlength' => 1024,
      ];
    }

    $form['annullamento'] = [
      '#type' => 'details',
      '#title' => $this->t('Richiesta di annullamento'),
      '#description' => $this->t("Quando un dipendente chiede l'annullamento di una propria istanza, la richiesta viene trasmessa alla segreteria per il protocollo."),
      '#open' => FALSE,
    ];
    $form['annullamento']['annullamento_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Invia alla segreteria l'email di richiesta annullamento"),
      '#default_value' => $config->get('annullamento_email') ?? TRUE,
    ];
    $form['annullamento']['annullamento_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Oggetto dell'email"),
      '#default_value' => $config->get('annullamento_subject') ?: "Richiesta di annullamento istanza n. @istanza_serial - @nome",
      '#states' => [
        'visible' => [':input[name="annullamento_email"]' => ['checked' => TRUE]],
      ],
    ];
    $form['annullamento']['annullamento_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Testo del messaggio'),
      '#rows' => 4,
      '#description' => $this->t('Segnaposto disponibili: @nome, @codice_fiscale, @istanza, @istanza_serial, @data, @motivo, @nomesito. Il messaggio viene spedito in HTML: per andare a capo si usa &lt;br&gt;.'),
      '#default_value' => $config->get('annullamento_body') ?: entypa_annullamento_testo_predefinito(),
      '#states' => [
        'visible' => [':input[name="annullamento_email"]' => ['checked' => TRUE]],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    foreach ($form_state->getValue(['referenti', 'sedi']) ?: [] as $chiave => $riga) {
      foreach ($this->indirizzi($riga['email'] ?? '') as $indirizzo) {
        if (!\Drupal::service('email.validator')->isValid($indirizzo)) {
          $form_state->setErrorByName("referenti][sedi][$chiave][email", $this->t('«@indirizzo» non è un indirizzo email valido.', ['@indirizzo' => $indirizzo]));
        }
      }
    }

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
   * Le sedi di servizio previste dal profilo del personale.
   *
   * @return array
   *   L'etichetta di ogni sede, per valore.
   */
  protected function sedi() {
    $campi = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('user');
    if (!isset($campi['field_sede_di_servizio'])) {
      return [];
    }

    return options_allowed_values($campi['field_sede_di_servizio']);
  }

  /**
   * Raccoglie dal form le sedi che hanno almeno un indirizzo.
   *
   * @return array
   *   Le righe da salvare in configurazione.
   */
  protected function referentiDalForm(FormStateInterface $form_state) {
    $righe = [];
    foreach ($form_state->getValue(['referenti', 'sedi']) ?: [] as $chiave => $riga) {
      $indirizzi = $this->indirizzi($riga['email'] ?? '');
      if ($indirizzi) {
        $righe[] = ['sede' => (string) $chiave, 'email' => implode(', ', $indirizzi)];
      }
    }
    return $righe;
  }

  /**
   * Divide un elenco di indirizzi separati da virgola.
   */
  protected function indirizzi($testo) {
    return array_values(array_filter(array_map('trim', preg_split('/[,;\s]+/', (string) $testo))));
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
      ->set('nome_scuola', trim($form_state->getValue('nome_scuola')))
      ->set('codice_ipa', trim($form_state->getValue('codice_ipa')))
      ->set('codice_aoo', trim($form_state->getValue('codice_aoo')))
      ->set('toponimo', trim($form_state->getValue('toponimo')))
      ->set('civico', trim($form_state->getValue('civico')))
      ->set('cap', trim($form_state->getValue('cap')))
      ->set('comune', trim($form_state->getValue('comune')))
      ->set('provincia', $form_state->getValue('provincia'))
      ->set('telefono', trim($form_state->getValue('telefono')))
      ->set('fax', trim($form_state->getValue('fax')))
      ->set('instance_signature', (bool) $form_state->getValue('instance_signature'))
      ->set('referenti_plesso', $this->referentiDalForm($form_state))
      ->set('annullamento_email', (bool) $form_state->getValue('annullamento_email'))
      ->set('annullamento_subject', $form_state->getValue('annullamento_subject'))
      ->set('annullamento_body', $form_state->getValue('annullamento_body'))
      ->save();

    if (\Drupal::moduleHandler()->moduleExists('entypa_praxis')) {
      $this->config('entypa_praxis.settings')
        ->set('evaded_signature', (bool) $form_state->getValue('evaded_signature'))
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

}