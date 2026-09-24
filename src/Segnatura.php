<?php

namespace Drupal\entypa;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\State\StateInterface;
use Drupal\user\UserInterface;

/**
 * Costruisce il file Segnatura.xml da allegare alle email.
 *
 * La segnatura permette al software di protocollo della scuola di registrare
 * l'email già compilata: mittente, oggetto, documento. Il formato è quello
 * della circolare AgID 60/2013, l'unico che i software scolastici leggono su
 * una email ordinaria. Non è la segnatura firmata delle Linee guida AgID del
 * 2021: quella vale fra amministrazioni e richiede un sigillo elettronico
 * qualificato, e nessuno dei due usi di Entýpa vi rientra — l'istanza la manda
 * un dipendente, l'esito la scuola a se stessa.
 *
 * Le scelte seguono la DTD della circolare, non il modulo di Drupal 7 da cui
 * la funzione viene: niente elementi vuoti o assenti dalla DTD, codifica UTF-8
 * dichiarata e reale, numero di sette cifre.
 */
class Segnatura {

  /**
   * Nome del file allegato, quello che i software di protocollo cercano.
   */
  const NOME_FILE = 'Segnatura.xml';

  /**
   * Chiave di stato del contatore della scuola.
   */
  const CONTATORE = 'entypa.segnatura.numero';

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new Segnatura.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, LockBackendInterface $lock, TimeInterface $time, DateFormatterInterface $date_formatter) {
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->lock = $lock;
    $this->time = $time;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * Il mittente quando a scrivere è un dipendente.
   *
   * Lo schema ammette come mittente solo un'amministrazione: il dipendente vi
   * entra con un codice suo, lo stesso che produceva il modulo di Drupal 7
   * (cognome in minuscolo e identificativo dell'utente). Cambiarlo farebbe
   * trovare al software di protocollo un mittente nuovo per ogni dipendente
   * già registrato in anagrafica.
   *
   * @param \Drupal\user\UserInterface $utente
   *   Il dipendente.
   * @param string $email
   *   L'indirizzo del dipendente.
   *
   * @return array
   *   Il mittente, nella forma attesa da componi().
   */
  public function mittenteDipendente(UserInterface $utente, $email) {
    $nome = $this->campo($utente, 'field_nome');
    $cognome = $this->campo($utente, 'field_cognome') ?: $utente->getDisplayName();
    $codice_fiscale = $this->campo($utente, 'field_codice_fiscale');

    $denominazione = implode(' - ', array_filter([
      $this->nomeScuola(),
      trim($cognome . ' ' . $nome),
      $codice_fiscale,
    ]));

    return [
      'denominazione' => $denominazione,
      'codice_amministrazione' => mb_strtolower($cognome) . '_' . $utente->id(),
      'codice_aoo' => 'AOO' . $utente->id(),
      'email' => $email,
      'persona' => [
        'nome' => $nome,
        'cognome' => $cognome,
        'codice_fiscale' => $codice_fiscale,
      ],
    ];
  }

  /**
   * Il mittente quando a scrivere è la scuola.
   *
   * @return array
   *   Il mittente, nella forma attesa da componi().
   */
  public function mittenteScuola() {
    $config = $this->configFactory->get('entypa.settings');

    return [
      'denominazione' => $this->nomeScuola(),
      'codice_amministrazione' => (string) $config->get('codice_ipa'),
      'codice_aoo' => (string) $config->get('codice_aoo'),
      'email' => $this->emailScuola(),
    ];
  }

  /**
   * Assegna il prossimo numero della scuola.
   *
   * Si ricomincia da 1 a ogni anno solare, come la numerazione di protocollo.
   * Il lucchetto impedisce che due email partite nello stesso istante ricevano
   * lo stesso numero.
   *
   * @return int
   *   Il numero assegnato.
   */
  public function numeroScuola() {
    $anno = (int) $this->dateFormatter->format($this->time->getRequestTime(), 'custom', 'Y');

    // Un'attesa lunga qui vorrebbe dire un'email ferma: dopo qualche secondo
    // si procede comunque, perché un numero doppio è meno grave di un'istanza
    // che non parte.
    $preso = $this->lock->acquire(self::CONTATORE, 5);
    if (!$preso) {
      $this->lock->wait(self::CONTATORE, 5);
      $preso = $this->lock->acquire(self::CONTATORE, 5);
    }

    $contatore = $this->state->get(self::CONTATORE, []);
    $numero = ($contatore['anno'] ?? NULL) === $anno ? $contatore['numero'] + 1 : 1;
    $this->state->set(self::CONTATORE, ['anno' => $anno, 'numero' => $numero]);

    if ($preso) {
      $this->lock->release(self::CONTATORE);
    }

    return $numero;
  }

  /**
   * Scrive la segnatura.
   *
   * @param array $mittente
   *   Da mittenteDipendente() o mittenteScuola().
   * @param int $numero
   *   Il numero di registrazione.
   * @param string $destinatario
   *   L'indirizzo a cui va l'email.
   * @param string $oggetto
   *   L'oggetto dell'email.
   * @param array $documenti
   *   I documenti allegati all'email, ciascuno con «nome» (il nome del file
   *   allegato, come lo vede il software di protocollo) e «mime». Il primo è
   *   il documento principale, gli altri i suoi allegati.
   *
   * @return string
   *   Il contenuto di Segnatura.xml.
   */
  public function componi(array $mittente, $numero, $destinatario, $oggetto, array $documenti) {
    $x = new \XMLWriter();
    $x->openMemory();
    $x->setIndent(TRUE);
    $x->startDocument('1.0', 'UTF-8');

    $x->startElement('Segnatura');

    $x->startElement('Intestazione');

    $x->startElement('Identificatore');
    $x->writeElement('CodiceAmministrazione', $mittente['codice_amministrazione']);
    $x->writeElement('CodiceAOO', $mittente['codice_aoo']);
    $x->writeElement('NumeroRegistrazione', sprintf('%07d', $numero));
    $x->writeElement('DataRegistrazione', $this->dateFormatter->format($this->time->getRequestTime(), 'custom', 'Y-m-d'));
    $x->endElement();

    $x->startElement('Origine');
    $this->indirizzoTelematico($x, $mittente['email']);
    $x->startElement('Mittente');
    $x->startElement('Amministrazione');
    $x->writeElement('Denominazione', $mittente['denominazione']);
    $x->writeElement('CodiceAmministrazione', $mittente['codice_amministrazione']);
    if (!empty($mittente['persona'])) {
      $this->persona($x, $mittente['persona']);
    }
    $this->recapiti($x);
    $x->endElement();
    $x->startElement('AOO');
    $x->writeElement('Denominazione', $mittente['denominazione']);
    $x->writeElement('CodiceAOO', $mittente['codice_aoo']);
    $x->endElement();
    $x->endElement();
    $x->endElement();

    $x->startElement('Destinazione');
    $x->writeAttribute('confermaRicezione', 'no');
    $this->indirizzoTelematico($x, $destinatario);
    $x->endElement();

    // Le risposte vanno a chi ha scritto.
    $x->startElement('Risposta');
    $this->indirizzoTelematico($x, $mittente['email']);
    $x->endElement();

    $x->writeElement('Oggetto', $oggetto);

    $x->endElement();

    $x->startElement('Descrizione');
    $principale = array_shift($documenti);
    $this->documento($x, $principale);
    if ($documenti) {
      $x->startElement('Allegati');
      foreach ($documenti as $documento) {
        $this->documento($x, $documento);
      }
      $x->endElement();
    }
    $x->endElement();

    $x->endElement();
    $x->endDocument();

    return $x->outputMemory();
  }

  /**
   * Prepara l'allegato nella forma che si aggiunge ai parametri dell'email.
   *
   * @param string $xml
   *   Il contenuto restituito da componi().
   *
   * @return array
   *   L'allegato.
   */
  public function allegato($xml) {
    return [
      'filecontent' => $xml,
      'filename' => self::NOME_FILE,
      'filemime' => 'text/xml',
    ];
  }

  /**
   * Dice se fra gli allegati c'è già una segnatura.
   *
   * @param array $allegati
   *   Gli allegati dell'email.
   *
   * @return bool
   *   TRUE se la segnatura c'è già.
   */
  public function giaPresente(array $allegati) {
    foreach ($allegati as $allegato) {
      if (($allegato['filename'] ?? NULL) === self::NOME_FILE) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * La denominazione della scuola, o il nome del sito se manca.
   *
   * @return string
   *   La denominazione.
   */
  public function nomeScuola() {
    return $this->configFactory->get('entypa.settings')->get('nome_scuola')
      ?: (string) $this->configFactory->get('system.site')->get('name');
  }

  /**
   * L'indirizzo della scuola, o quello del sito se manca.
   *
   * @return string
   *   L'indirizzo.
   */
  public function emailScuola() {
    return $this->configFactory->get('entypa.settings')->get('mail_istituto')
      ?: (string) $this->configFactory->get('system.site')->get('mail');
  }

  /**
   * Scrive un indirizzo email.
   */
  protected function indirizzoTelematico(\XMLWriter $x, $email) {
    $x->startElement('IndirizzoTelematico');
    $x->writeAttribute('tipo', 'smtp');
    $x->text((string) $email);
    $x->endElement();
  }

  /**
   * Scrive nome, cognome e codice fiscale del dipendente.
   */
  protected function persona(\XMLWriter $x, array $persona) {
    $x->startElement('Persona');
    if ($persona['nome'] !== '') {
      $x->writeElement('Nome', $persona['nome']);
    }
    $x->writeElement('Cognome', $persona['cognome']);
    if ($persona['codice_fiscale'] !== '') {
      $x->writeElement('CodiceFiscale', $persona['codice_fiscale']);
    }
    $x->endElement();
  }

  /**
   * Scrive indirizzo postale, telefono e fax della scuola.
   *
   * Lo schema vuole l'indirizzo o tutto intero o come testo libero: se ne
   * manca un pezzo, si scrive in una riga quello che c'è.
   */
  protected function recapiti(\XMLWriter $x) {
    $config = $this->configFactory->get('entypa.settings');
    $parti = [];
    foreach (['toponimo', 'civico', 'cap', 'comune', 'provincia'] as $chiave) {
      $parti[$chiave] = trim((string) $config->get($chiave));
    }

    $x->startElement('IndirizzoPostale');
    if (!in_array('', $parti, TRUE)) {
      $x->writeElement('Toponimo', $parti['toponimo']);
      $x->writeElement('Civico', $parti['civico']);
      $x->writeElement('CAP', $parti['cap']);
      $x->writeElement('Comune', $parti['comune']);
      $x->writeElement('Provincia', $parti['provincia']);
    }
    else {
      $riga = trim($parti['toponimo'] . ' ' . $parti['civico']);
      $riga = implode(', ', array_filter([$riga, trim($parti['cap'] . ' ' . $parti['comune']), $parti['provincia']]));
      $x->writeElement('Denominazione', $riga ?: $this->nomeScuola());
    }
    $x->endElement();

    foreach (['telefono' => 'Telefono', 'fax' => 'Fax'] as $chiave => $elemento) {
      $valore = trim((string) $config->get($chiave));
      if ($valore !== '') {
        $x->writeElement($elemento, $valore);
      }
    }
  }

  /**
   * Scrive il riferimento a un documento allegato all'email.
   *
   * Il nome è quello del file allegato: è così che lo schema lega la
   * segnatura alla parte dell'email. L'impronta non si può mettere: lo schema
   * la ammette solo per i documenti esterni all'email.
   */
  protected function documento(\XMLWriter $x, array $documento) {
    $x->startElement('Documento');
    $x->writeAttribute('nome', $documento['nome']);
    $x->writeAttribute('tipoMIME', $documento['mime'] ?? 'application/pdf');
    $x->writeAttribute('tipoRiferimento', 'MIME');
    $x->endElement();
  }

  /**
   * Legge un campo di testo dell'utente.
   */
  protected function campo(UserInterface $utente, $campo) {
    return $utente->hasField($campo) ? trim((string) $utente->get($campo)->value) : '';
  }

}
