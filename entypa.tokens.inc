<?php

/**
 * @file
 * Token integration for Entýpa.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function entypa_token_info() {
  $info['types']['entypa'] = [
    'name' => t('Entýpa'),
    'description' => t("Token relativi alle istanze del personale scolastico."),
  ];
  $info['tokens']['entypa']['mail-istituto'] = [
    'name' => t("Email dell'istituto"),
    'description' => t("Indirizzo email dell'istituto configurato in Entýpa (in mancanza, l'email del sito)."),
  ];

  return $info;
}

/**
 * Implements hook_tokens().
 */
function entypa_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type !== 'entypa') {
    return $replacements;
  }

  foreach ($tokens as $name => $original) {
    if ($name === 'mail-istituto') {
      $bubbleable_metadata->addCacheableDependency(\Drupal::config('entypa.settings'));
      $bubbleable_metadata->addCacheableDependency(\Drupal::config('system.site'));
      $replacements[$original] = entypa_get_mail_istituto();
    }
  }

  return $replacements;
}