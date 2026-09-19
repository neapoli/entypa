<?php

namespace Drupal\entypa_praxis\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Helper service for Entýpa — Praxis module.
 */
class InstanceHelperService {

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
   * Constructs an InstanceHelperService object.
   */
  public function __construct(
    Connection $database,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get nid, tid, and uid from submission ID.
   *
   * @param int $sid
   *   The submission ID.
   * @param int &$nid
   *   The node ID (passed by reference).
   * @param int &$tid
   *   The tid from the entypa_praxis table (passed by reference).
   * @param int &$uid
   *   The user ID (passed by reference).
   */
  public function getNidTidFromSubmission($sid, &$nid, &$tid, &$uid) {
    // FIXED: In Drupal 10, use entity_id instead of nid, and check entity_type
    $query = $this->database->select('entypa_praxis', 't');
    $query->condition('t.submission_id', $sid, '=');
    $query->join('webform_submission', 's', 't.submission_id = s.sid');
    $query->fields('t', ['tid']);
    $query->addField('s', 'entity_id', 'nid');
    $query->addField('s', 'uid');
    $query->condition('s.entity_type', 'node');

    $result = $query->execute()->fetchAssoc();

    $nid = $result['nid'] ?? 0;
    $tid = $result['tid'] ?? 0;
    $uid = $result['uid'] ?? 0;
  }

  /**
   * Get instance data by submission ID.
   *
   * @param int $sid
   *   The submission ID.
   *
   * @return array|null
   *   Instance data or NULL if not found.
   */
  public function getInstanceData($sid) {
    $query = $this->database->select('entypa_praxis', 't');
    $query->fields('t');
    $query->condition('submission_id', $sid);

    return $query->execute()->fetchAssoc();
  }

  /**
   * Get instance data by tid.
   *
   * @param int $tid
   *   The tid.
   *
   * @return array|null
   *   Instance data or NULL if not found.
   */
  public function getInstanceDataByTid($tid) {
    $query = $this->database->select('entypa_praxis', 't');
    $query->fields('t');
    $query->condition('tid', $tid);

    return $query->execute()->fetchAssoc();
  }

  /**
   * Check if a node uses "acquisisci" mode.
   *
   * @param int $nid
   *   The node ID.
   *
   * @return bool
   *   TRUE if node uses acquisisci mode.
   */
  public function usesAcquisisci($nid) {
    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    if (!$node || !$node->hasField('field_si_acquisisci')) {
      return FALSE;
    }

    return (bool) $node->get('field_si_acquisisci')->value;
  }

  /**
   * Check if a node requires DSGA visa.
   *
   * @param int $nid
   *   The node ID.
   *
   * @return bool
   *   TRUE if node requires DSGA visa.
   */
  public function requiresDsgaVisa($nid) {
    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    if (!$node || !$node->hasField('field_si_dsga')) {
      return FALSE;
    }

    return (bool) $node->get('field_si_dsga')->value;
  }

  /**
   * Check if a node requires referente visa.
   *
   * @param int $nid
   *   The node ID.
   *
   * @return bool
   *   TRUE if node requires referente visa.
   */
  public function requiresReferenteVisa($nid) {
    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    if (!$node || !$node->hasField('field_si_referente')) {
      return FALSE;
    }

    return (bool) $node->get('field_si_referente')->value;
  }

  /**
   * Delete an instance record.
   *
   * @param int $submission_id
   *   The submission ID.
   */
  public function deleteInstance($submission_id) {
    $this->database->delete('entypa_praxis')
      ->condition('submission_id', $submission_id)
      ->execute();
  }

  /**
   * Check if instance should be marked as evaso based on field and status.
   *
   * @param string $field
   *   The field being updated.
   * @param int $is_ok
   *   The status value.
   * @param bool $acquisisci
   *   Whether the node uses acquisisci mode.
   * @param bool $usa_visto_dsga
   *   Whether the node requires DSGA visa.
   * @param bool $usa_visto_referente
   *   Whether the node requires referente visa.
   *
   * @return bool
   *   TRUE if instance should be marked as evaso.
   */
  public function shouldMarkAsEvaso($field, $is_ok, $acquisisci, $usa_visto_dsga, $usa_visto_referente) {
    switch ($field) {
      case 'protocollo':
      case 'visto_referente':
        return FALSE;

      case 'concedibile':
        return ($is_ok == 2);

      case 'visto_dsga':
        return ($is_ok == 2 || $acquisisci);

      case 'acquisito':
        // Check if OE mode is enabled.
        $config = \Drupal::config('entypa_praxis.settings');
        $oe = $config->get('oe') ?: FALSE;
        return ($is_ok == 2 || (!$usa_visto_dsga && ($oe == FALSE || $is_ok != 0)));

      case 'visto':
        return TRUE;
    }

    return FALSE;
  }

}
