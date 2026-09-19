<?php

namespace Drupal\entypa_praxis\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Service for managing fields for Entýpa — Praxis.
 */
class FieldManagerService {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a FieldManagerService object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Reset/create all required fields.
   */
  public function resetFields() {
    // Validate bundles first.
    $valid_bundles = $this->validateBundles();

    if (empty($valid_bundles)) {
      \Drupal::messenger()->addWarning($this->t('Nessun content type valido selezionato.'));
      return;
    }

    $this->resetField(
      'field_si_acquisisci',
      $this->t('Acquisisci soltanto, senza visto del DS'),
      $this->t('Questo campo serve per attivare la modalità acquisisci.')
    );

    $this->resetField(
      'field_si_dsga',
      $this->t('Questa istanza richiede il visto del DSGA'),
      $this->t('Questo campo serve per attivare il visto del DSGA.')
    );

    $this->resetField(
      'field_si_referente',
      $this->t('Questa istanza richiede il visto del Referente'),
      $this->t('Questo campo serve per attivare il visto del referente di plesso.')
    );

    $this->resetField(
      'field_si_email',
      $this->t('Email di istanza evasa alla segreteria'),
      $this->t('Questo campo serve per inviare anche alla segreteria una email di istanza evasa.')
    );
  }

  /**
   * Validate that selected bundles are healthy.
   *
   * @return array
   *   Array of valid bundle names.
   */
  protected function validateBundles() {
    $config = $this->configFactory->get('entypa_praxis.settings');
    $node_types = $config->get('node_types') ?: [];
    $enabled_node_types = array_filter($node_types);

    $valid_bundles = [];

    foreach ($enabled_node_types as $bundle => $enabled) {
      if (!$enabled) {
        continue;
      }

      // Check if bundle exists.
      $node_type = $this->entityTypeManager->getStorage('node_type')->load($bundle);
      if (!$node_type) {
        \Drupal::messenger()->addWarning($this->t('Content type @bundle non trovato.', ['@bundle' => $bundle]));
        continue;
      }

      // Check for corrupted fields.
      $has_corrupted = $this->hasCorruptedFields($bundle);
      if ($has_corrupted) {
        \Drupal::messenger()->addError($this->t('Content type @bundle ha campi corrotti. Sistemalo prima di usarlo con Entýpa — Praxis.', ['@bundle' => $bundle]));
        continue;
      }

      $valid_bundles[$bundle] = $bundle;
    }

    return $valid_bundles;
  }

  /**
   * Check if a bundle has corrupted fields.
   *
   * @param string $bundle
   *   The bundle name.
   *
   * @return bool
   *   TRUE if bundle has corrupted fields.
   */
  protected function hasCorruptedFields($bundle) {
    try {
      $fields = $this->entityTypeManager
        ->getStorage('field_config')
        ->loadByProperties([
          'entity_type' => 'node',
          'bundle' => $bundle,
        ]);

      foreach ($fields as $field) {
        $field_name = $field->getName();
        $storage = FieldStorageConfig::loadByName('node', $field_name);

        if (!$storage) {
          // Found a field without storage = corrupted.
          \Drupal::logger('entypa_praxis')->error('Campo corrotto trovato: @field su @bundle', [
            '@field' => $field_name,
            '@bundle' => $bundle,
          ]);
          return TRUE;
        }
      }

      return FALSE;
    }
    catch (\Exception $e) {
      \Drupal::logger('entypa_praxis')->error('Errore validazione bundle @bundle: @error', [
        '@bundle' => $bundle,
        '@error' => $e->getMessage(),
      ]);
      return TRUE;
    }
  }

  /**
   * Reset/create a single field.
   *
   * @param string $field_name
   *   The field name.
   * @param string $label
   *   The field label.
   * @param string $description
   *   The field description.
   */
  public function resetField($field_name, $label, $description) {
    $config = $this->configFactory->get('entypa_praxis.settings');

    // Get enabled node types.
    $node_types = $config->get('node_types') ?: [];
    $enabled_node_types = array_filter($node_types);

    // If no node types are enabled, don't create anything.
    if (empty($enabled_node_types)) {
      return;
    }

    // Create field storage if it doesn't exist and we have enabled bundles.
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    if (!$field_storage) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'node',
        'type' => 'boolean',
        'cardinality' => 1,
      ]);
      $field_storage->save();
    }

    // Get all node types.
    $all_node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();

    foreach ($all_node_types as $bundle => $node_type) {
      $field_config = FieldConfig::loadByName('node', $bundle, $field_name);

      // If this bundle is not in enabled list, remove the field.
      if (!isset($enabled_node_types[$bundle])) {
        if ($field_config) {
          $field_config->delete();
        }
        continue;
      }

      // Create field instance if it doesn't exist and bundle is enabled.
      if (!$field_config) {
        $default_value = FALSE;
        if ($field_name === 'field_si_email') {
          $default_value = $config->get('notification_email') ?: FALSE;
        }

        $field_config = FieldConfig::create([
          'field_name' => $field_name,
          'entity_type' => 'node',
          'bundle' => $bundle,
          'label' => $label,
          'description' => $description,
          'required' => FALSE,
          'default_value' => [['value' => $default_value]],
          'settings' => [
            'on_label' => $this->t('Sì'),
            'off_label' => $this->t('No'),
          ],
        ]);
        $field_config->save();

        // Configure form display.
        $form_display = $this->entityTypeManager
          ->getStorage('entity_form_display')
          ->load('node.' . $bundle . '.default');

        if ($form_display) {
          $form_display->setComponent($field_name, [
            'type' => 'boolean_checkbox',
            'weight' => 10,
            'settings' => [
              'display_label' => TRUE,
            ],
          ])->save();
        }

        // Configure view display (hide by default).
        $view_display = $this->entityTypeManager
          ->getStorage('entity_view_display')
          ->load('node.' . $bundle . '.default');

        if ($view_display) {
          $view_display->removeComponent($field_name)->save();
        }
      }
    }

    // Clean up field storage if no bundles are using it.
    $this->cleanupUnusedFieldStorage($field_name);
  }

  /**
   * Remove field storage if no field configs are using it.
   *
   * @param string $field_name
   *   The field name.
   */
  protected function cleanupUnusedFieldStorage($field_name) {
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);

    if ($field_storage) {
      // Check if any field configs still exist.
      $bundles = $field_storage->getBundles();

      if (empty($bundles)) {
        // No bundles using this field storage, safe to delete.
        $field_storage->delete();
      }
    }
  }

  /**
   * Check if a node type should have fields applied.
   *
   * @param string $bundle
   *   The node bundle.
   *
   * @return bool
   *   TRUE if fields should be applied.
   */
  public function appliesToBundle($bundle) {
    $config = $this->configFactory->get('entypa_praxis.settings');
    $node_types = $config->get('node_types') ?: [];

    // Filter out empty values.
    $node_types = array_filter($node_types);

    return isset($node_types[$bundle]) && $node_types[$bundle] === $bundle;
  }

  /**
   * Get the codice fiscale field key (value or code).
   *
   * @return string
   *   The key to use for codice fiscale field.
   */
  public function getCodiceFiscaleKey() {
    $field_storage = FieldStorageConfig::loadByName('user', 'field_codice_fiscale');

    if (!$field_storage) {
      // Field doesn't exist, return default.
      return 'value';
    }

    // Check field type.
    $field_type = $field_storage->getType();
    return ($field_type === 'text') ? 'value' : 'code';
  }

}
