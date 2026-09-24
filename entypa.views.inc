<?php

/**
 * @file
 * Integrazione con Views di Entýpa.
 */

/**
 * Implements hook_views_data_alter().
 */
function entypa_views_data_alter(array &$data) {
  // Due colonne calcolate sugli invii dei formulari: non hanno dati propri in
  // tabella, li ricavano dal valore degli elementi e — se c'è — dalla tabella
  // dell'istruttoria.
  $data['webform_submission']['entypa_annullamento'] = [
    'title' => t('Annullamento'),
    'help' => t("Permette a chi ha presentato l'istanza di chiederne l'annullamento, e mostra come è andata."),
    'field' => [
      'id' => 'entypa_annullamento',
    ],
  ];

  $data['webform_submission']['entypa_stato'] = [
    'title' => t("Stato dell'istanza"),
    'help' => t("A che punto è l'istanza. Con Entýpa — Praxis installato segue anche l'istruttoria."),
    'field' => [
      'id' => 'entypa_stato',
    ],
  ];
}
