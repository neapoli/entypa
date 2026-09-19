<?php

/**
 * @file
 * Integrazione con Views di Entýpa — Praxis.
 */

/**
 * Implements hook_views_data().
 */
function entypa_praxis_views_data() {
  $data = [];

  // Define the base table.
  $data['entypa_praxis']['table']['group'] = t('Entýpa — Praxis');

  // Define the join to webform_submission.
  $data['entypa_praxis']['table']['join'] = [
    'webform_submission' => [
      'left_field' => 'sid',
      'field' => 'submission_id',
    ],
  ];

  // Primary key.
  $data['entypa_praxis']['tid'] = [
    'title' => t('Chiave di istanza'),
    'help' => t('Indice primario tabella entypa_praxis.'),
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];

  // Submission ID relationship.
  $data['entypa_praxis']['submission_id'] = [
    'title' => t('Chiave di submission'),
    'help' => t('Indice primario tabella webform_submission.'),
    'relationship' => [
      'base' => 'webform_submission',
      'base field' => 'sid',
      'id' => 'standard',
      'label' => t('Relazione entypa_praxis'),
    ],
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // User ID.
  $data['entypa_praxis']['user_id'] = [
    'title' => t('User ID'),
    'help' => t('ID utente che ha creato l\'istanza.'),
    'field' => [
      'id' => 'numeric',
    ],
    'relationship' => [
      'base' => 'users_field_data',
      'base field' => 'uid',
      'id' => 'standard',
      'label' => t('Utente istanza'),
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // Protocollo field.
  $data['entypa_praxis']['protocollo_stato'] = [
    'title' => t('Protocollo'),
    'help' => t('Istanza protocollata.'),
    'field' => [
      'id' => 'entypa_praxis_protocollo',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];

  // Concedibile field.
  $data['entypa_praxis']['concedibile_stato'] = [
    'title' => t('Verifica ufficio'),
    'help' => t('Istanza concedibile.'),
    'field' => [
      'id' => 'entypa_praxis_concedibile',
    ],
    'sort' => [
      'id' => 'entypa_praxis_concedibile_sort',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];

  // Visto DSGA field.
  $data['entypa_praxis']['visto_dsga_stato'] = [
    'title' => t('Visto DSGA'),
    'help' => t('Istanza conforme.'),
    'field' => [
      'id' => 'entypa_praxis_visto_dsga',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];

  // Visto Referente field.
  $data['entypa_praxis']['visto_referente_stato'] = [
    'title' => t('Visto Referente'),
    'help' => t('Istanza conforme.'),
    'field' => [
      'id' => 'entypa_praxis_visto_referente',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];

  // Visto DS field.
  $data['entypa_praxis']['visto_stato'] = [
    'title' => t('Visto DS'),
    'help' => t('Istanza accolta.'),
    'field' => [
      'id' => 'entypa_praxis_visto',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];

  // Evaso data field.
  $data['entypa_praxis']['evaso_data'] = [
    'title' => t('Evaso'),
    'help' => t('Istanza evasa il...'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'entypa_praxis_evaso_data',
    ],
  ];

  // Annullato data field.
  $data['entypa_praxis']['annullato_data'] = [
    'title' => t('Annullato'),
    'help' => t('Istanza annullata il...'),
    'field' => [
      'id' => 'entypa_praxis_annullato_data',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'entypa_praxis_annullato_data_filter',
    ],
  ];

  // Elaborato data field.
  $data['entypa_praxis']['elaborato_data'] = [
    'title' => t('Elaborato'),
    'help' => t('Istanza finalizzata il...'),
    'field' => [
      'id' => 'entypa_praxis_elaborato_data',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'entypa_praxis_elaborato_data_filter',
    ],
  ];

  // Motivazioni field.
  $data['entypa_praxis']['motivazioni'] = [
    'title' => t('Motivazioni'),
    'help' => t('Le motivazioni per il rifiuto della istanza.'),
    'field' => [
      'id' => 'entypa_praxis_motivazioni',
    ],
  ];

  // Modifica pseudo-field.
  $data['entypa_praxis']['modifica'] = [
    'title' => t('Modifica istanze'),
    'help' => t('Inserire questo campo per permettere la modifica delle istanze.'),
    'field' => [
      'id' => 'entypa_praxis_modifica',
    ],
  ];

  return $data;
}
