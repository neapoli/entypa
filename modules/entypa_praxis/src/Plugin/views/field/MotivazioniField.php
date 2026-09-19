<?php

namespace Drupal\entypa_praxis\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\entypa_praxis\RigaIstruttoria;
use Drupal\Core\Url;

/**
 * Field handler for Motivazioni with link to modify.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entypa_praxis_motivazioni")
 */
class MotivazioniField extends FieldPluginBase {

  use RigaIstruttoria;

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
    // Get values using adaptive method
    $tid = $this->colonnaIstruttoria($values, 'tid');

    $sid = $this->colonnaRiga($values, 'sid');

    $nid = $this->colonnaRiga($values, 'nid');

    // Get motivazioni - read ALWAYS from DB for fresh data
    $value = (string) $this->colonnaIstruttoria($values, 'motivazioni', '');

    // If empty, return nothing
    if (empty($value)) {
      return ['#markup' => ''];
    }

    // Check if user can edit
    $editable = property_exists($this->view, 'si_editable') ? $this->view->si_editable : FALSE;

    if ($editable &&
      \Drupal::currentUser()->hasPermission('acquisito') &&
      !is_null($nid) &&
      !is_null($sid) &&
      !is_null($tid)) {

      // Build edit link
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

      try {
        $url = Url::fromRoute('entypa_praxis.concedi', [
          'node' => $nid,
          'submission' => $sid,
          'tid' => $tid,
        ], [
          'query' => ['destination' => $destination],
          'attributes' => [
            'title' => $this->t('clicca qui per modificare'),
            'data-drupal-link-system-path' => 'node/' . $nid . '/submission/' . $sid . '/istanza-concedi/' . $tid,
          ],
        ]);

        // Use Link API to render proper link without AJAX
        $link = \Drupal\Core\Link::fromTextAndUrl($value, $url)->toRenderable();
        $link['#attributes']['class'][] = 'no-ajax';
        
        return $link;
      }
      catch (\Exception $e) {
        // If URL generation fails, just show text
        return ['#markup' => htmlspecialchars($value)];
      }
    }
    else {
      // Just show text (not editable or missing permissions)
      return ['#markup' => htmlspecialchars($value)];
    }
  }

}
