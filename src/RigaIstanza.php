<?php

namespace Drupal\entypa;

use Drupal\views\ResultRow;
use Drupal\views\Views;

/**
 * Aiuta i campi Views a leggere lo stato di un'istanza.
 *
 * I dati stanno in due posti diversi. Il valore di un elemento del formulario
 * vive in webform_submission_data, una riga per elemento: si raggiunge con un
 * join vincolato al nome dell'elemento. Lo stato dell'istruttoria vive invece
 * nella tabella di Entýpa — Praxis, che c'è solo se quel modulo è installato:
 * il join si aggiunge allora soltanto quando serve, così questi campi
 * funzionano anche sulle scuole che usano Entýpa da solo.
 */
trait RigaIstanza {

  /**
   * Alias delle colonne aggiunte alla query, per nome logico.
   *
   * @var string[]
   */
  protected $aliasEntypa = [];

  /**
   * Aggiunge alla query il valore di un elemento del formulario.
   *
   * @param string $elemento
   *   La chiave dell'elemento, per esempio «richiesta_annullamento».
   */
  protected function giungiDatoInvio($elemento) {
    $definizione = [
      'table' => 'webform_submission_data',
      'field' => 'sid',
      'left_table' => $this->ensureMyTable(),
      'left_field' => 'sid',
      'type' => 'LEFT',
      // Senza questo vincolo il join restituirebbe una riga per ogni elemento
      // del formulario, moltiplicando i risultati.
      'extra' => [
        ['field' => 'name', 'value' => $elemento],
      ],
    ];

    $join = Views::pluginManager('join')->createInstance('standard', $definizione);
    $tabella = $this->query->addTable('webform_submission_data', $this->relationship, $join, 'entypa_' . $elemento);

    $this->aliasEntypa[$elemento] = $this->query->addField($tabella, 'value');
  }

  /**
   * Aggiunge alla query le colonne dell'istruttoria, se praxis è installato.
   *
   * @param string[] $colonne
   *   I nomi delle colonne di entypa_praxis da leggere.
   */
  protected function giungiIstruttoria(array $colonne) {
    if (!\Drupal::moduleHandler()->moduleExists('entypa_praxis')) {
      return;
    }

    $definizione = [
      'table' => 'entypa_praxis',
      'field' => 'submission_id',
      'left_table' => $this->ensureMyTable(),
      'left_field' => 'sid',
      'type' => 'LEFT',
    ];

    $join = Views::pluginManager('join')->createInstance('standard', $definizione);
    $tabella = $this->query->addTable('entypa_praxis', $this->relationship, $join, 'entypa_istruttoria');

    foreach ($colonne as $colonna) {
      $this->aliasEntypa['praxis_' . $colonna] = $this->query->addField($tabella, $colonna);
    }
  }

  /**
   * Legge dalla riga uno dei valori aggiunti alla query.
   *
   * @param \Drupal\views\ResultRow $values
   *   La riga di risultato.
   * @param string $nome
   *   Il nome logico usato al momento del join.
   * @param mixed $default
   *   Il valore da restituire se la colonna non fa parte della query.
   *
   * @return mixed
   *   Il valore letto.
   */
  protected function valoreEntypa(ResultRow $values, $nome, $default = NULL) {
    $alias = $this->aliasEntypa[$nome] ?? NULL;

    if ($alias === NULL || !property_exists($values, $alias)) {
      return $default;
    }

    return $values->{$alias};
  }

  /**
   * Dice se l'istruttoria è disponibile su questo sito.
   *
   * @return bool
   *   TRUE se Entýpa — Praxis è installato.
   */
  protected function conIstruttoria() {
    return \Drupal::moduleHandler()->moduleExists('entypa_praxis');
  }

}
