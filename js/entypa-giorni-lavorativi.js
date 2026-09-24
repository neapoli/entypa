/**
 * @file
 * Conteggio dei giorni lavorativi, condiviso dai formulari Entýpa.
 *
 * Le assenze a giornata si computano in giorni lavorativi (art. 13, comma 2,
 * del CCNL 29/11/2007): domeniche e festività non si scalano. Lo stesso conto
 * vive anche in PHP, in entypa_giorni_lavorativi(), ed è quello il valore che
 * viene salvato: qui si calcola per mostrarlo mentre si compila, e i due lati
 * devono dire la stessa cosa.
 */
(function (Drupal, drupalSettings) {

  'use strict';

  var GIORNO_MS = 86400000;

  var FESTIVITA_FISSE = [
    '01-01', '01-06', '04-25', '05-01', '06-02',
    '08-15', '11-01', '12-08', '12-25', '12-26'
  ];

  var pasquetteNote = {};

  /**
   * Il lunedì dell'Angelo di un anno, in millisecondi UTC.
   *
   * Pasqua gregoriana con l'algoritmo di Meeus/Jones/Butcher. La domenica di
   * Pasqua non serve elencarla: è già esclusa fra le domeniche.
   */
  function pasquetta(anno) {
    if (pasquetteNote[anno] === undefined) {
      var a = anno % 19;
      var b = Math.floor(anno / 100);
      var c = anno % 100;
      var d = Math.floor(b / 4);
      var e = b % 4;
      var f = Math.floor((b + 8) / 25);
      var g = Math.floor((b - f + 1) / 3);
      var h = (19 * a + b - d - g + 15) % 30;
      var i = Math.floor(c / 4);
      var k = c % 4;
      var l = (32 + 2 * e + 2 * i - h - k) % 7;
      var m = Math.floor((a + 11 * h + 22 * l) / 451);
      var mese = Math.floor((h + l - 7 * m + 114) / 31);
      var giorno = ((h + l - 7 * m + 114) % 31) + 1;

      pasquetteNote[anno] = Date.UTC(anno, mese - 1, giorno) + GIORNO_MS;
    }

    return pasquetteNote[anno];
  }

  /**
   * Le festività da escludere, comprese quelle locali.
   *
   * La ricorrenza del santo patrono cambia con la sede e non si può dedurre:
   * arriva dalle impostazioni di Entýpa. Se manca si conta senza, e il server
   * applicherà comunque il valore configurato.
   */
  function festivita() {
    var patrono = (drupalSettings.entypa && drupalSettings.entypa.patrono) || '';

    return patrono ? FESTIVITA_FISSE.concat([patrono]) : FESTIVITA_FISSE;
  }

  function festivo(ms) {
    var d = new Date(ms);

    if (d.getUTCDay() === 0) { return true; }

    var mm = ('0' + (d.getUTCMonth() + 1)).slice(-2);
    var gg = ('0' + d.getUTCDate()).slice(-2);
    if (festivita().indexOf(mm + '-' + gg) !== -1) { return true; }

    return ms === pasquetta(d.getUTCFullYear());
  }

  Drupal.entypa = Drupal.entypa || {};

  /**
   * Converte in millisecondi UTC una data scritta dal campo o dal datepicker.
   *
   * @param {string} valore
   *   Data in formato "aaaa-mm-gg" oppure "gg/mm/aaaa".
   *
   * @return {number|null}
   *   I millisecondi UTC, oppure null se il valore non è una data.
   */
  Drupal.entypa.toData = function (valore) {
    if (!valore) { return null; }

    var m = valore.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (m) { return Date.UTC(+m[1], +m[2] - 1, +m[3]); }

    m = valore.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    if (m) { return Date.UTC(+m[3], +m[2] - 1, +m[1]); }

    return null;
  };

  /**
   * I giorni lavorativi di un periodo, estremi inclusi.
   *
   * @param {number} inizio
   *   Millisecondi UTC del primo giorno.
   * @param {number} fine
   *   Millisecondi UTC dell'ultimo giorno.
   *
   * @return {number}
   *   I giorni lavorativi del periodo.
   */
  Drupal.entypa.giorniLavorativi = function (inizio, fine) {
    var giorni = 0;
    var passi = 0;

    // Un periodo non supera l'anno: il limite evita cicli su dati assurdi.
    for (var ms = inizio; ms <= fine && passi < 400; ms += GIORNO_MS) {
      if (!festivo(ms)) { giorni += 1; }
      passi += 1;
    }

    return giorni;
  };

}(Drupal, drupalSettings));
