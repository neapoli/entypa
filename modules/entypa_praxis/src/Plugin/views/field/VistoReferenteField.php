<?php

namespace Drupal\entypa_praxis\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\entypa_praxis\RigaIstruttoria;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Field handler for Visto Referente status.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entypa_praxis_visto_referente")
 */
class VistoReferenteField extends FieldPluginBase {

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
    $editable = property_exists($this->view, 'si_editable') ? $this->view->si_editable : FALSE;

    // Get values
    $sid = $this->colonnaRiga($values, 'sid');

    $uid = $this->colonnaRiga($values, 'uid');

    $tid = $this->colonnaIstruttoria($values, 'tid');

    $nid = $this->colonnaRiga($values, 'nid');

    // Get visto_referente_stato from DB
    $value = $this->statoIstruttoria($values, 'visto_referente_stato', 0);

    // Get protocollo_stato from DB
    $protocollo = $this->statoIstruttoria($values, 'protocollo_stato', 0);

    // Get concedibile_stato from DB
    $concedibile = $this->statoIstruttoria($values, 'concedibile_stato', 0);

    $nodata = is_null($tid);

    // Load node to check settings
    $usa_visto_referente = FALSE;
    $acquisisci = FALSE;

    if ($nid) {
      try {
        $node = $this->entityTypeManager->getStorage('node')->load($nid);
        if ($node) {
          if ($node->hasField('field_si_referente')) {
            $usa_visto_referente = (bool) $node->get('field_si_referente')->value;
          }
          if ($node->hasField('field_si_acquisisci')) {
            $acquisisci = (bool) $node->get('field_si_acquisisci')->value;
          }
        }
      }
      catch (\Exception $e) {
        \Drupal::logger('entypa_praxis')->error('Error loading node @nid: @error', [
          '@nid' => $nid,
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // Show form when:
    // - User has permission
    // - Field is editable
    // - Protocollo done (= 1)
    // - Concedibile NOT done yet (= 0) - Referente gives opinion BEFORE office evaluation
    // - Referente is required
    // - NOT in "acquisci only" mode
    if (\Drupal::currentUser()->hasPermission('visto referente') &&
      $editable &&
      $protocollo == 1 &&
      $concedibile == 0 &&
      $usa_visto_referente &&
      !$acquisisci) {

      // Render inline form
      $form = \Drupal::formBuilder()->getForm(
        'Drupal\entypa_praxis\Form\EntypaPraxisReferenteForm',
        $sid,
        $uid,
        $nodata ? 0 : $tid,
        $nodata ? 0 : $value,
        'visto_referente'
      );

      return [
        '#markup' => '<span id="ref' . $sid . '">' . \Drupal::service('renderer')->render($form) . '</span>',
      ];
    }
    elseif ($nodata || $value == 0 || !$usa_visto_referente) {
      return ['#markup' => '<span id="ref' . $sid . '">---</span>'];
    }
    else {
      // Show icon based on value
      $icon = '';
      $text = '';
      switch ($value) {
        case 1:
          $icon = '✓';
          $text = 'conforme';
          break;
        case 2:
          $icon = '✗';
          $text = 'non conforme';
          break;
      }

      return [
        '#markup' => sprintf(
          '<span id="ref%d" title="%s" style="font-size: 20px;">%s</span>',
          $sid,
          $text,
          $icon
        ),
      ];
    }
  }

}
