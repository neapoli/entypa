<?php

namespace Drupal\entypa_praxis\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Render\Markup;
use Drupal\views\ResultRow;
use Drupal\entypa_praxis\RigaIstruttoria;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Field handler for Protocollo status.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entypa_praxis_protocollo")
 */
class ProtocolloField extends FieldPluginBase {

  use RigaIstruttoria;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- this field is computed.
  }


  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Check if view is editable.
    $editable = property_exists($this->view, 'si_editable') ? $this->view->si_editable : FALSE;
    
    // Get SID - try multiple possible keys
    $sid = $this->colonnaRiga($values, 'sid');
    
    // Get TID - try multiple possible keys
    $tid = $this->colonnaIstruttoria($values, 'tid');
    
    // Get UID - try multiple possible keys
    $uid = $this->colonnaRiga($values, 'uid');
    
    // Get protocollo_stato (value) - try multiple possible keys
    $value = $this->statoIstruttoria($values, 'protocollo_stato', 0);
    
    
    // Get concedibile_stato - try multiple possible keys
    $concedibile = $this->statoIstruttoria($values, 'concedibile_stato', 0);
    
    $nodata = is_null($tid);
    
    // Check if user has permission and if field should be editable.
    if (\Drupal::currentUser()->hasPermission('protocollato') &&
        $editable &&
        !$nodata &&
        $value == 0 &&
        $concedibile == 0) {
      
      // Render inline form.
      $form = \Drupal::formBuilder()->getForm(
        'Drupal\entypa_praxis\Form\EntypaPraxisInlineForm',
        $sid,
        $uid,
        $tid,
        $value,
        'protocollo'
      );
      
      return \Drupal::service('renderer')->render($form);
    }
    elseif ($nodata || $value == 0) {
      return ['#markup' => '<span id="pro' . $sid . '">---</span>'];
    }
    else {
      // Show icon - try to use si.png, fallback to SVG
      $module_path = \Drupal::service('extension.list.module')->getPath('entypa_praxis');
      $icon_path = DRUPAL_ROOT . '/' . $module_path . '/icone/si.png';
      
      if (file_exists($icon_path)) {
        // Use PNG image
        $icon_url = '/' . $module_path . '/icone/si.png';
        return [
          '#markup' => '<span id="pro' . $sid . '"><img src="' . $icon_url . '" alt="Protocollato" style="width: 20px; height: 20px;" /></span>',
        ];
      }
      else {
        // Fallback to inline SVG checkmark
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="green" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
        return [
          '#markup' => Markup::create('<span id="pro' . $sid . '">' . $svg . '</span>'),
        ];
      }
    }
  }

}
