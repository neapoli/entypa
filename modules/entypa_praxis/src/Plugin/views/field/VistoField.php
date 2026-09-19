<?php

namespace Drupal\entypa_praxis\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\entypa_praxis\RigaIstruttoria;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;

/**
 * Field handler for Visto DS status.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entypa_praxis_visto")
 */
class VistoField extends FieldPluginBase {

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
    
    // Get values using adaptive method
    $sid = $this->colonnaRiga($values, 'sid');
    
    $tid = $this->colonnaIstruttoria($values, 'tid');
    
    $nid = $this->colonnaRiga($values, 'nid');
    
    // Get visto_stato - read ALWAYS from DB for fresh data
    $value = $this->statoIstruttoria($values, 'visto_stato', 0);
    
    // Get concedibile_stato
    $concedibile = $this->statoIstruttoria($values, 'concedibile_stato', 0);
    
    // Get visto_dsga_stato - read ALWAYS from DB for fresh data
    $visto_dsga = $this->statoIstruttoria($values, 'visto_dsga_stato');
    
    $nodata = is_null($tid);
    
    // Load node to check settings
    $usa_visto_dsga = FALSE;
    $acquisisci = FALSE;
    
    if ($nid) {
      try {
        $node = $this->entityTypeManager->getStorage('node')->load($nid);
        if ($node) {
          if ($node->hasField('field_si_dsga')) {
            $usa_visto_dsga = (bool) $node->get('field_si_dsga')->value;
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
    
    // Show link ONLY if not yet evaluated (value == 0)
    // Once chosen, shows icon (not editable)
    if (\Drupal::currentUser()->hasPermission('visto') &&
        $editable &&
        !$nodata &&
        $concedibile == 1 &&
        (!$usa_visto_dsga || $visto_dsga == 1) &&  // If DSGA required, must be approved
        !$acquisisci &&
        $value == 0) {  // CRITICAL: Only show link if NOT yet evaluated
      
      // Show "da valutare" link
      // Get clean path without Ajax parameters
      $request = \Drupal::request();
      $destination = $request->getPathInfo();
      
      // Clean up any Ajax-related query parameters from current URL
      $query_params = $request->query->all();
      $clean_params = [];
      foreach ($query_params as $key => $value) {
        // Exclude Ajax and wrapper format parameters
        if (!in_array($key, ['ajax_form', '_wrapper_format', 'ajax_page_state', '_drupal_ajax'])) {
          $clean_params[$key] = $value;
        }
      }
      
      // Build destination with clean parameters
      if (!empty($clean_params)) {
        $destination .= '?' . http_build_query($clean_params);
      }
      
      $url = Url::fromRoute('entypa_praxis.visto', [
        'node' => $nid,
        'submission' => $sid,
        'tid' => $tid,
      ], [
        'query' => ['destination' => $destination],
        'attributes' => [
          'title' => $this->t('clicca qui per valutare'),
          'data-drupal-link-system-path' => 'node/' . $nid . '/submission/' . $sid . '/istanza-visto/' . $tid,
        ],
      ]);
      
      // Use Link API to render proper link without AJAX
      $link = \Drupal\Core\Link::fromTextAndUrl($this->t('da valutare'), $url)->toRenderable();
      $link['#attributes']['class'][] = 'no-ajax';
      
      return [
        '#type' => 'container',
        '#attributes' => ['id' => 'visto' . $sid],
        'link' => $link,
      ];
    }
    elseif ($nodata || $value == 0) {
      return ['#markup' => '<span id="visto' . $sid . '">---</span>'];
    }
    else {
      // Show icon - NOT EDITABLE anymore
      // Use si.png / no.png like other fields
      $module_path = \Drupal::service('extension.list.module')->getPath('entypa_praxis');
      $icon_file = '';
      $alt_text = '';
      
      switch ($value) {
        case 1:
          $icon_file = 'si.png';
          $alt_text = 'si concede';
          break;
        case 2:
          $icon_file = 'no.png';
          $alt_text = 'non si concede';
          break;
      }
      
      // Check if file exists, fallback to emoji
      $icon_path = DRUPAL_ROOT . '/' . $module_path . '/icone/' . $icon_file;
      
      if (file_exists($icon_path)) {
        // Use PNG image
        $icon_url = '/' . $module_path . '/icone/' . $icon_file;
        return [
          '#markup' => sprintf(
            '<span id="visto%d"><img src="%s" alt="%s" title="%s" style="width: 20px; height: 20px;" /></span>',
            $sid,
            $icon_url,
            $alt_text,
            $alt_text
          ),
        ];
      }
      else {
        // Fallback to emoji
        $icon = ($value == 1) ? '✓' : '✗';
        return [
          '#markup' => sprintf(
            '<span id="visto%d" title="%s" style="font-size: 20px;">%s</span>',
            $sid,
            $alt_text,
            $icon
          ),
        ];
      }
    }
  }

}
