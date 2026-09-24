<?php

namespace Drupal\entypa_praxis\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entypa\ReferentiPlesso;

/**
 * Lega l'avviso ai referenti di plesso all'esito dell'istruttoria.
 *
 * Chi avvisare e cosa scrivere lo sa Entýpa (\Drupal\entypa\ReferentiPlesso),
 * che se ne occupa da solo per le istanze senza istruttoria. Qui si decide
 * soltanto quando, per quelle che passano da Praxis: l'avviso parte quando
 * l'istanza è accolta; se in seguito viene annullata, o l'esito cambia, parte
 * un secondo avviso che la ritira. La colonna «referenti_avvisati» ricorda a
 * che punto si è, così un'istanza evasa due volte non avvisa due volte.
 */
class ReferentiPlessoService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * L'avviso ai referenti di Entýpa.
   *
   * @var \Drupal\entypa\ReferentiPlesso
   */
  protected $referenti;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new ReferentiPlessoService.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager, ReferentiPlesso $referenti, TimeInterface $time) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->referenti = $referenti;
    $this->time = $time;
  }

  /**
   * Allinea l'avviso ai referenti allo stato dell'istanza.
   *
   * Si chiama dopo ogni passaggio dell'istruttoria che può evadere, cambiare
   * esito o annullare: decide da sé se c'è qualcosa da mandare.
   *
   * @param int $submission_id
   *   L'istanza: una sola riga di istruttoria le corrisponde.
   */
  public function aggiorna($submission_id) {
    $riga = $this->database->select('entypa_praxis', 'x')
      ->fields('x')
      ->condition('submission_id', $submission_id)
      ->execute()
      ->fetchAssoc();

    if (!$riga) {
      return;
    }

    $accolta = FALSE;
    if (empty($riga['annullato_data'])) {
      $esito = entypa_esito_istanza([
        $riga['visto_stato'],
        $riga['visto_dsga_stato'],
        $riga['concedibile_stato'],
      ], $riga['evaso_data']);
      $accolta = $esito && $esito['is_ok'] == 1;
    }

    $avvisati = !empty($riga['referenti_avvisati']);
    if ($accolta === $avvisati) {
      return;
    }

    $submission = $this->entityTypeManager->getStorage('webform_submission')->load($riga['submission_id']);
    if (!$submission || !$this->referenti->riguardaDocente($submission)) {
      return;
    }

    if ($accolta) {
      if ($this->referenti->avvisa($submission, 'assenza')) {
        $this->segna($riga['tid'], $this->time->getRequestTime());
      }
    }
    else {
      // L'avviso di ritiro va agli stessi plessi: se nel frattempo la sede del
      // docente è cambiata, qualcuno potrebbe non riceverlo, ma inventare a
      // chi era andato il primo sarebbe peggio.
      $this->referenti->avvisa($submission, 'ritiro');
      $this->segna($riga['tid'], NULL);
    }
  }

  /**
   * Registra se i referenti risultano avvisati.
   */
  protected function segna($tid, $quando) {
    $this->database->update('entypa_praxis')
      ->fields(['referenti_avvisati' => $quando])
      ->condition('tid', $tid)
      ->execute();
  }

}
