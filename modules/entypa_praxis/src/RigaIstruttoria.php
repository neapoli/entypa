<?php

namespace Drupal\entypa_praxis;

use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\ResultRow;

/**
 * Legge le colonne della riga di risultato di Views senza indovinarne il nome.
 *
 * Views costruisce l'alias di ogni colonna concatenando alias di tabella e nome
 * del campo, poi lo abbassa a minuscolo e lo tronca a 60 caratteri
 * (Sql::addField(), per il limite di PostgreSQL). In una vista con più join
 * l'alias diventa lungo e viene tagliato: «annullato_data» sulla tabella
 * dell'istruttoria arriva a chiamarsi
 * «webform_submission_node_field_data__entypa_praxis_annullato_».
 *
 * Scrivere quei nomi a mano nel codice, come faceva il modulo originale,
 * significa sbagliarli ogni volta che la vista cambia forma. Qui l'alias si
 * chiede invece alla query stessa, che lo conosce.
 */
trait RigaIstruttoria {

  /**
   * Legge una colonna della tabella dell'istruttoria.
   *
   * @param \Drupal\views\ResultRow $values
   *   La riga di risultato.
   * @param string $colonna
   *   Il nome della colonna in tabella, per esempio «tid» o «evaso_data».
   * @param mixed $default
   *   Il valore da restituire se la colonna non fa parte della query.
   *
   * @return mixed
   *   Il valore letto dalla riga.
   */
  protected function colonnaIstruttoria(ResultRow $values, $colonna, $default = NULL) {
    return $this->colonnaDallaQuery($values, $colonna, $this->ensureMyTable(), $default);
  }

  /**
   * Legge uno stato dell'istruttoria, già convertito a intero.
   *
   * NULL resta NULL: significa che per quell'istanza non esiste ancora una
   * riga di istruttoria, ed è diverso da uno stato pari a zero.
   *
   * @param \Drupal\views\ResultRow $values
   *   La riga di risultato.
   * @param string $colonna
   *   Il nome della colonna.
   * @param mixed $default
   *   Il valore da restituire se la colonna non fa parte della query.
   *
   * @return int|null
   *   Lo stato letto dalla riga.
   */
  protected function statoIstruttoria(ResultRow $values, $colonna, $default = NULL) {
    $valore = $this->colonnaIstruttoria($values, $colonna, $default);
    return $valore === NULL ? NULL : (int) $valore;
  }

  /**
   * Legge una colonna qualsiasi della query, per esempio «sid» o «uid».
   *
   * @param \Drupal\views\ResultRow $values
   *   La riga di risultato.
   * @param string $colonna
   *   Il nome della colonna.
   * @param mixed $default
   *   Il valore da restituire se la colonna non fa parte della query.
   *
   * @return mixed
   *   Il valore letto dalla riga.
   */
  protected function colonnaRiga(ResultRow $values, $colonna, $default = NULL) {
    return $this->colonnaDallaQuery($values, $colonna, NULL, $default);
  }

  /**
   * Risolve l'alias di una colonna interrogando la query e legge la riga.
   *
   * @param \Drupal\views\ResultRow $values
   *   La riga di risultato.
   * @param string $colonna
   *   Il nome della colonna.
   * @param string|null $tabella
   *   L'alias di tabella a cui limitarsi, oppure NULL per cercare ovunque.
   * @param mixed $default
   *   Il valore da restituire se la colonna non fa parte della query.
   *
   * @return mixed
   *   Il valore letto dalla riga.
   */
  private function colonnaDallaQuery(ResultRow $values, $colonna, $tabella, $default) {
    $query = $this->view->query ?? NULL;

    if ($query instanceof Sql) {
      foreach ($query->fields as $alias => $info) {
        if (($info['field'] ?? NULL) !== $colonna) {
          continue;
        }
        if ($tabella !== NULL && ($info['table'] ?? NULL) !== $tabella) {
          continue;
        }
        if (property_exists($values, $alias)) {
          return $values->{$alias};
        }
      }
    }

    // Ripiego sul nome semplice, che Views usa quando non c'è ambiguità.
    return property_exists($values, $colonna) ? $values->{$colonna} : $default;
  }

}
