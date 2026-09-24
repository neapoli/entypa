<?php

namespace Drupal\entypa;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Avvisa i referenti di plesso delle assenze dei docenti.
 *
 * Il referente deve organizzare le sostituzioni: gli serve sapere chi manca e
 * quando, non perché. Il messaggio non porta la causale né altri dati
 * dell'istanza, perché per alcune — la malattia, la legge 104 — dire il motivo
 * significherebbe comunicare dati sulla salute.
 *
 * Quando l'avviso parta dipende da chi decide sull'istanza. Se passa
 * dall'istruttoria di Praxis, parte quando è accolta, e Praxis se ne occupa.
 * Altrimenti la scuola accetta quello che riceve: l'avviso parte all'invio, e
 * il ritiro alla richiesta di annullamento.
 */
class ReferentiPlesso {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new ReferentiPlesso.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler) {
    $this->configFactory = $config_factory;
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Il docente ha inviato l'istanza.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   L'istanza appena inviata.
   */
  public function dopoInvio(WebformSubmissionInterface $submission) {
    if (!$submission->isDraft() && !$this->gestitaDaPraxis($submission) && $this->riguardaDocente($submission)) {
      $this->avvisa($submission, 'assenza');
    }
  }

  /**
   * Il docente ha chiesto di annullare l'istanza.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   L'istanza.
   */
  public function dopoRichiestaAnnullamento(WebformSubmissionInterface $submission) {
    if (!$this->gestitaDaPraxis($submission) && $this->riguardaDocente($submission)) {
      $this->avvisa($submission, 'ritiro');
    }
  }

  /**
   * Dice se sull'istanza decide l'istruttoria di Praxis.
   *
   * Vale il tipo di contenuto da cui arriva, lo stesso criterio con cui
   * Praxis apre le sue righe: al momento dell'invio la riga potrebbe non
   * esserci ancora.
   */
  public function gestitaDaPraxis(WebformSubmissionInterface $submission) {
    if (!$this->moduleHandler->moduleExists('entypa_praxis')) {
      return FALSE;
    }

    $nodo = $submission->getSourceEntity();
    return $nodo && $nodo->getEntityTypeId() === 'node'
      && \Drupal::service('entypa_praxis.field_manager')->appliesToBundle($nodo->bundle());
  }

  /**
   * Dice se l'istanza riguarda l'orario di un docente.
   *
   * Sono i formulari dei docenti — l'identificativo termina in «_doc» — che
   * hanno date di assenza, più lo scambio di ore: il docente non manca, ma
   * cambia chi è in classe e quando, ed è questo che al referente serve.
   */
  public function riguardaDocente(WebformSubmissionInterface $submission) {
    return str_ends_with($submission->getWebform()->id(), '_doc')
      && ($this->periodi($submission) || $this->scambio($submission));
  }

  /**
   * Spedisce l'avviso ai referenti dei plessi del docente.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   L'istanza.
   * @param string $tipo
   *   «assenza» oppure «ritiro».
   *
   * @return bool
   *   TRUE se il messaggio è partito.
   */
  public function avvisa(WebformSubmissionInterface $submission, $tipo) {
    $utente = $submission->getOwner();
    $destinatari = $utente ? $this->destinatari($utente) : [];

    if (!$destinatari) {
      return FALSE;
    }

    $nome = $utente->getDisplayName();
    if ($utente->hasField('field_cognome') && $utente->get('field_cognome')->value) {
      $nome = trim($utente->get('field_nome')->value . ' ' . $utente->get('field_cognome')->value);
    }

    $risultato = $this->mailManager->mail(
      'entypa',
      'referenti_' . $tipo,
      implode(', ', $destinatari),
      $this->languageManager->getDefaultLanguage()->getId(),
      [
        'nome' => $nome,
        'periodi' => $this->periodi($submission),
        'scambio' => $this->scambio($submission),
      ]
    );

    return !empty($risultato['result']);
  }

  /**
   * Gli indirizzi dei referenti dei plessi in cui il docente presta servizio.
   *
   * @return string[]
   *   Gli indirizzi, senza doppioni.
   */
  protected function destinatari($utente) {
    if (!$utente->hasField('field_sede_di_servizio')) {
      return [];
    }

    $per_sede = [];
    foreach ($this->configFactory->get('entypa.settings')->get('referenti_plesso') ?: [] as $riga) {
      $per_sede[$riga['sede']] = $riga['email'];
    }

    $indirizzi = [];
    foreach ($utente->get('field_sede_di_servizio') as $sede) {
      foreach (explode(',', $per_sede[$sede->value] ?? '') as $indirizzo) {
        $indirizzo = trim($indirizzo);
        if ($indirizzo !== '') {
          $indirizzi[$indirizzo] = $indirizzo;
        }
      }
    }

    return array_values($indirizzi);
  }

  /**
   * I periodi di assenza scritti nell'istanza, in italiano.
   *
   * I formulari li tengono in tre forme: un dal/al unico, più periodi dal/al,
   * più giorni con orario.
   *
   * @return string[]
   *   Per esempio «dal 12/10/2026 al 14/10/2026», «il 12/10/2026 dalle 10:00
   *   alle 12:00».
   */
  public function periodi(WebformSubmissionInterface $submission) {
    $dati = $submission->getData();
    $periodi = [];

    if (!empty($dati['data_inizio'])) {
      $periodi[] = $this->dalAl($dati['data_inizio'], $dati['data_fine'] ?? '');
    }

    foreach ((array) ($dati['periodi_richiesti'] ?? []) as $riga) {
      if (!empty($riga['data_inizio'])) {
        $periodi[] = $this->dalAl($riga['data_inizio'], $riga['data_fine'] ?? '');
      }
    }

    foreach ((array) ($dati['permesso_orario'] ?? []) as $riga) {
      if (!empty($riga['giorno'])) {
        $testo = 'il ' . $this->data($riga['giorno']);
        if (!empty($riga['ora_inizio']) && !empty($riga['ora_fine'])) {
          $testo .= ' dalle ' . substr($riga['ora_inizio'], 0, 5) . ' alle ' . substr($riga['ora_fine'], 0, 5);
        }
        $periodi[] = $testo;
      }
    }

    return $periodi;
  }

  /**
   * Lo scambio di ore scritto nell'istanza, se è un cambio turno.
   *
   * Il motivo non c'è, per la stessa ragione per cui manca la causale delle
   * assenze. C'è invece come saranno coperte le lezioni: è l'informazione
   * organizzativa che il docente scrive proprio per chi deve coprirle.
   *
   * @return array|null
   *   «previsto» e «modificato», in italiano, più «collega» e «copertura»;
   *   NULL se l'istanza non è uno scambio di ore.
   */
  public function scambio(WebformSubmissionInterface $submission) {
    $dati = $submission->getData();
    if (empty($dati['turno_previsto_giorno']) || empty($dati['turno_modificato_giorno'])) {
      return NULL;
    }

    return [
      'previsto' => $this->turno($submission, 'turno_previsto'),
      'modificato' => $this->turno($submission, 'turno_modificato'),
      'collega' => trim((string) ($dati['collega'] ?? '')),
      'copertura' => trim((string) ($dati['il_servizio_giornaliero_sara_cosi_riorganizzato'] ?? '')),
    ];
  }

  /**
   * Descrive una delle due righe dello scambio.
   *
   * Per esempio «il 12/10/2026 dalle 10:00 alle 11:00, classe 3A, plesso
   * Rodari».
   */
  protected function turno(WebformSubmissionInterface $submission, $prefisso) {
    $dati = $submission->getData();

    $testo = 'il ' . $this->data($dati[$prefisso . '_giorno']);
    if (!empty($dati[$prefisso . '_dalle_ore']) && !empty($dati[$prefisso . '_alle_ore'])) {
      $testo .= ' dalle ' . substr($dati[$prefisso . '_dalle_ore'], 0, 5) . ' alle ' . substr($dati[$prefisso . '_alle_ore'], 0, 5);
    }

    $parti = [$testo];
    if (!empty($dati[$prefisso . '_classe'])) {
      $parti[] = 'classe ' . trim($dati[$prefisso . '_classe']);
    }

    // Il plesso è una voce di un elenco: si scrive l'etichetta, non la chiave.
    $plesso = $dati[$prefisso . '_plesso_interessato'] ?? '';
    if ($plesso !== '') {
      $elemento = $submission->getWebform()->getElement($prefisso . '_plesso_interessato') ?: [];
      $voci = $elemento ? \Drupal\webform\Entity\WebformOptions::getElementOptions($elemento) : [];
      $parti[] = 'plesso ' . strip_tags((string) ($voci[$plesso] ?? $plesso));
    }

    return implode(', ', $parti);
  }

  /**
   * Scrive un periodo; un giorno solo si dice «il».
   */
  protected function dalAl($inizio, $fine) {
    if ($fine === '' || $fine === $inizio) {
      return 'il ' . $this->data($inizio);
    }
    return 'dal ' . $this->data($inizio) . ' al ' . $this->data($fine);
  }

  /**
   * Porta una data da aaaa-mm-gg a gg/mm/aaaa.
   */
  protected function data($valore) {
    return preg_match('/^(\d{4})-(\d{2})-(\d{2})/', (string) $valore, $parti)
      ? "$parti[3]/$parti[2]/$parti[1]"
      : (string) $valore;
  }

}
