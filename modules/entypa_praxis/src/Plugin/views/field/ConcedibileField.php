<?php

namespace Drupal\entypa_praxis\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\entypa_praxis\RigaIstruttoria;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;

/**
 * Field handler for Concedibile status with inline edit.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entypa_praxis_concedibile")
 */
class ConcedibileField extends FieldPluginBase {

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
    // Add the field to the query so it can be sorted.
    $this->ensureMyTable();
    
    // Add the concedibile_stato field from entypa_praxis table
    $this->field_alias = $this->query->addField(
      $this->tableAlias,
      'concedibile_stato',
      'concedibile_stato'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function clickSortable() {
    // Enable click sorting on column header
    return TRUE;
  }


  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $editable = property_exists($this->view, 'si_editable') ? $this->view->si_editable : FALSE;
    
    // Get values using adaptive method
    $sid = $this->colonnaRiga($values, 'sid');
    
    $tid = $this->colonnaIstruttoria($values, 'tid');
    
    // If tid is null but we have sid, try to get tid from database
    // This handles the case where protocollo was just checked and created a new record
    if (is_null($tid) && !is_null($sid)) {
      try {
        $connection = \Drupal::database();
        $db_tid = $connection->select('entypa_praxis', 's')
          ->fields('s', ['tid'])
          ->condition('submission_id', $sid)
          ->execute()
          ->fetchField();
        if ($db_tid !== FALSE) {
          $tid = (int) $db_tid;
        }
      }
      catch (\Exception $e) {
        // Log error but continue
      }
    }
    
    $value = $this->statoIstruttoria($values, 'concedibile_stato', 0);
    
    
    $protocollo = $this->statoIstruttoria($values, 'protocollo_stato');
    
    
    $visto_dsga = $this->statoIstruttoria($values, 'visto_dsga_stato');
    
    
    $visto = $this->statoIstruttoria($values, 'visto_stato');
    
    
    // Get node ID from entity_id
    $nid = $this->colonnaRiga($values, 'nid');
    
    $nodata = is_null($tid);
    
    // Load node to check settings.
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
    // This makes it like Protocollo: once chosen, it's final (shows icon, not link)
    // Note: visto and visto_dsga can be NULL or 0 (both mean "not set")
    if (\Drupal::currentUser()->hasPermission('acquisito') &&
        $editable &&
        !$nodata &&
        $protocollo == 1 &&
        $value == 0 &&  // CRITICAL: Only show link if NOT yet evaluated
        (is_null($visto) || $visto == 0) &&
        (!$usa_visto_dsga || is_null($visto_dsga) || $visto_dsga == 0)) {
      
      // Show "da valutare" link
      $status = 'da valutare';
      
      // Build URL to form.
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
      
      $url = Url::fromRoute('entypa_praxis.concedi', [
        'node' => $nid,
        'submission' => $sid,
        'tid' => $tid,
      ], [
        'query' => ['destination' => $destination],
        'attributes' => [
          'title' => $this->t('clicca qui per valutare'),
          'data-drupal-link-system-path' => 'node/' . $nid . '/submission/' . $sid . '/istanza-concedi/' . $tid,
        ],
      ]);
      
      // Use Link API to render proper link without AJAX
      $link = \Drupal\Core\Link::fromTextAndUrl($status, $url)->toRenderable();
      $link['#attributes']['class'][] = 'no-ajax';
      
      return [
        '#type' => 'container',
        '#attributes' => ['id' => 'sid' . $sid],
        'link' => $link,
      ];
    }
    elseif ($nodata || $value == 0) {
      return ['#markup' => '<span id="sid' . $sid . '">---</span>'];
    }
    else {
      // Show icon based on value - NOT EDITABLE anymore
      // Use si.png / no.png like Protocollo field
      $module_path = \Drupal::service('extension.list.module')->getPath('entypa_praxis');
      $icon_file = '';
      $alt_text = '';
      
      switch ($value) {
        case 1:
          $icon_file = 'si.png';
          $alt_text = $acquisisci ? 'acquisito' : 'concedibile';
          break;
        case 2:
          $icon_file = 'no.png';
          $alt_text = $acquisisci ? 'non acquisito' : 'non concedibile';
          break;
        case 3:
          $icon_file = 'sospeso.png'; // You may need to create this
          $alt_text = 'sospeso';
          break;
      }
      
      // Check if file exists, fallback to SVG
      $icon_path = DRUPAL_ROOT . '/' . $module_path . '/icone/' . $icon_file;
      
      if (file_exists($icon_path)) {
        // Use PNG image
        $icon_url = '/' . $module_path . '/icone/' . $icon_file;
        return [
          '#markup' => '<span id="sid' . $sid . '"><img src="' . $icon_url . '" alt="' . $alt_text . '" title="' . $alt_text . '" style="width: 20px; height: 20px;" /></span>',
        ];
      }
      else {
        // Fallback to emoji
        $icon = '';
        switch ($value) {
          case 1:
            $icon = '✓';
            break;
          case 2:
            $icon = '✗';
            break;
          case 3:
            $icon = '⊗';
            break;
        }
        return [
          '#markup' => '<span id="sid' . $sid . '" style="font-size: 20px;" title="' . $alt_text . '">' . $icon . '</span>',
        ];
      }
    }
  }

}
