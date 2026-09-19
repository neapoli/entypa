# Conformità dei formulari Entypa al CCNL Istruzione e Ricerca 2019-2021

Ricognizione dei 22 formulari in `web/modules/custom/entypa/modules/` rispetto al CCNL
comparto «Istruzione e Ricerca» 2019-2021, Sezione Scuola, sottoscritto il 18 gennaio 2024,
e alle fonti di legge che regolano i singoli istituti.

**Ricognizione:** 17 settembre 2026 · **Correzioni:** 17-19 settembre 2026
**Testo normativo di riferimento:** testo definitivo della Sezione Scuola aggiornato alla
sottoscrizione del 18/01/2024, che riporta in corsivo gli articoli del CCNL 29/11/2007
ancora vigenti e indica espressamente le abrogazioni.

> **Avvertenza.** Questa è una ricognizione tecnica sui formulari, non un parere giuridico.
> Dice che cosa ciascun modulo raccoglie e dichiara, e dove questo divergeva dal testo
> contrattuale. La validazione spetta a chi in segreteria risponde degli atti che ne escono.

---

## 1. Quadro normativo accertato

Il rinnovo del 2024 **non ha riscritto l'intera disciplina delle assenze**: ha sostituito
alcuni articoli e ne ha lasciati in vigore altri del CCNL 29/11/2007. Questo è il punto che
determina quasi tutte le righe della tabella.

| Istituto | Norma vigente | Stato |
|---|---|---|
| Ferie | art. 13 CCNL 29/11/2007 | vigente, commi 14 e 15 sostituiti dall'art. 38 CCNL 19/21 |
| Festività (4 giornate L. 937/77) | art. 14 CCNL 29/11/2007 | vigente |
| Permessi retribuiti, tempo indeterminato | art. 15 CCNL 29/11/2007 | vigente (commi 2 e 6: solo docenti) |
| Permessi brevi | art. 16 CCNL 29/11/2007 | vigente |
| Assenze per malattia | art. 17 CCNL 29/11/2007 | vigente |
| Ferie, permessi e assenze del personale a tempo **determinato** | **art. 35 CCNL 19/21** | nuovo — **abroga l'art. 19 CCNL 29/11/2007** (art. 35, c. 16) |
| Diritto allo studio (150 ore) | **art. 37 CCNL 19/21** | nuovo — **disapplica l'art. 3 del DPR 395/1988** (art. 37, c. 7) |
| Permessi orari motivi personali/familiari **ATA** (18 ore) | **art. 67 CCNL 19/21** | nuovo — per gli ATA ha sostituito l'art. 15 c. 2 |
| Permessi di legge **ATA**, incluso L. 104 ad ore | **art. 68 CCNL 19/21** | nuovo — L. 104 fruibile ad ore entro 18 ore mensili |
| Visite, terapie, prestazioni specialistiche, esami **ATA** (18 ore) | **art. 69 CCNL 19/21** | nuovo |
| Cariche pubbliche elettive | art. 38 (docenti) e art. 52 (ATA) CCNL 29/11/2007 | vigenti |
| Orario di lavoro ATA | art. 51 CCNL 29/11/2007; artt. 63-66 CCNL 19/21 | vigenti |
| Orario dei docenti | art. 43 CCNL 19/21 | vigente |

Fonti di legge: L. 104/1992 art. 33 (**come modificato dal D.Lgs 105/2022, dal 13 agosto
2022**), L. 937/1977, D.Lgs 297/1994 artt. 508 e 676, D.Lgs 165/2001 art. 53, L. 662/1996
art. 1 commi 56 e seguenti, DPR 445/2000, L. 584/1967, L. 52/2001 art. 5, L. 53/2000.

---

## 2. Stato dei 22 formulari

| Webform | Disciplina | Norma applicata | Che cosa è stato corretto | Stato |
|---|---|---|---|---|
| `adesione_commissioni` | Disponibilità per commissioni | — (organizzazione interna) | Nessun rilievo normativo. Corretti: data del PDF che stampava la data di generazione anziché quella dell'istanza, obbligatorietà dei dati anagrafici, categorie | ✅ |
| `cambio_turno_ata` | Turnazioni ATA | art. 66 CCNL 19/21; art. 51 CCNL 2007 | Avviso sulla pausa obbligatoria oltre 7h12 (art. 51 c. 3) e sul divario fra i due turni (art. 51 c. 4, art. 54), anche sul PDF; totale da `webform_time` a testo readonly con il tetto di 9 ore spostato nel JS; rimosso il divieto di scambio all'indietro | ✅ |
| `cambio_turno_doc` | Scambio di ore di lezione | art. 43 c. 4 CCNL 19/21 | Terminologia allineata ai docenti (non esiste turnazione); campo classe; riferimento al piano annuale delle attività; confronto dei totali con l'art. 45 sulle ore eccedenti; stesse correzioni tecniche del gemello | ✅ |
| `ferie_estive_doc` | Ferie | art. 13 CCNL 2007 · art. 38 CCNL 19/21 | **Conteggio in giorni lavorativi** (escluse domeniche, festività nazionali, Pasquetta, patrono); monte ferie e citazioni puntuali; motivo del mancato godimento per l'anno precedente (art. 13 c. 10); periodi obbligatori; calcolo lato server | ✅ |
| `festivita_soppresse_ata`<br>`festivita_soppresse_doc` | Festività soppresse | art. 14 CCNL 2007; L. 937/1977 | Rimossa la citazione dell'**art. 28 c. 6 del CCNL 21/05/2018, che è del comparto Funzioni Locali**; anno scolastico di riferimento; conteggio in giorni lavorativi; **tetto delle 4 giornate imposto**; calcolo lato server. Nel modulo docenti, dichiarazione sul periodo di fruizione (art. 14 c. 2) | ✅ |
| `incarico_retribuito` | Incarichi extra-istituzionali | **D.Lgs 165/2001 art. 53**; D.Lgs 297/1994 art. 508 | Aggiunto l'art. 53, che governa l'istituto e non era citato; natura del soggetto conferente, da cui dipende l'effetto del silenzio; avvertenza su termini (30 giorni) e obblighi successivi; **aperto al personale ATA**; controlli di formato su CF e P.IVA | ✅ |
| `l104_assistenza_ata`<br>`l104_assistenza_doc` | L. 104, assistenza a familiare | L. 104/1992 art. 33 c. 3; art. 68 CCNL 19/21 (ATA); art. 15 c. 6 CCNL 2007 (docenti) | **Rimosse le dichiarazioni sul referente unico**, abolito dal D.Lgs 105/2022, sostituite dalla dichiarazione di fruizione in alternanza; `L.15/68` → DPR 445/2000; codice fiscale e verbale dell'assistito; **tetti di 3 giorni e (ATA) 18 ore mensili**; programmazione mensile (ATA, art. 68 cc. 2-3); calcolo lato server | ✅ |
| `l104_ata`<br>`l104_doc` | L. 104, permessi per sé | **L. 104/1992 art. 33 c. 6** | Citava l'art. 3 c. 3, che definisce la gravità e non attribuisce permessi. Scelta resa **alternativa** come vuole il comma 6 (eliminata l'opzione «entrambi»); **2 ore al giorno** oppure **3 giorni al mese**, entrambi verificati; verbale di accertamento; `L.15/68` → DPR 445/2000; calcolo lato server | ✅ |
| `libera_professione` | Libera professione dei docenti | D.Lgs 297/1994 art. 508 c. 15 | Citazione portata al **comma 15**, che consente, distinguendolo dal comma 10 che vieta; avvertenza su condizioni e ricorso (c. 16); avvisi condizionali sul part-time non superiore al 50% (art. 53 c. 6 D.Lgs 165/2001) e sull'iscrizione agli albi (L. 662/1996 art. 1 c. 56) | ✅ |
| `malattia_ata`<br>`malattia_doc` | Assenze per malattia | art. 17 CCNL 2007; art. 35 cc. 3-7 CCNL 19/21 | Citazioni puntuali e rimosso il rinvio a un «articolo sopra indicato» inesistente; **avvisi sul comporto** distinti per tempo indeterminato (18 mesi) e determinato (9 mesi nel triennio, o 30 giorni al 50%); fasce di reperibilità **10-12 e 17-19** (art. 17 c. 14); per gli ATA, avviso sul regime autonomo dell'art. 69 | ✅ |
| `permessi_150ore_ata`<br>`permessi_150ore_doc` | Diritto allo studio | **art. 37 CCNL 19/21** | Rimossa la citazione dell'**art. 3 del DPR 395/1988, disapplicato**; contingente del 3% e criteri di precedenza; diritti del comma 4; **anno solare** di riferimento; certificazione di iscrizione con allegato condizionale e impegno per frequenza ed esami, con l'avvertenza del comma 5; **tetto delle 150 ore** | ✅ |
| `permessi_brevi_art16_ata` | Permessi brevi ATA | art. 16 CCNL 2007 | **Tetto delle 36 ore annue imposto**; anno scolastico di riferimento; conseguenza del mancato recupero (c. 4); citazione precisata. Il limite del comma 1 (metà dell'orario giornaliero) è enunciato ma non imposto: quell'orario varia e il formulario non lo conosce | ✅ |
| `permessi_brevi_art16_doc` | Permessi brevi docenti | art. 16 CCNL 2007 | **Massimo di 2 ore per singolo permesso imposto** riga per riga (c. 1); monte annuo pari all'orario settimanale di insegnamento — 25/22/18 ore — enunciato, con blocco oltre il massimo possibile; recupero con supplenze e subordinazione alla sostituzione (cc. 3 e 5) | ✅ |
| `permessi_retribuiti_ata_td`<br>`permessi_retribuiti_doc_td` | Permessi, tempo determinato | **art. 35 CCNL 19/21** | Il titolo citava l'**art. 19, abrogato**. Aggiunte le tre fattispecie mancanti: **3 giorni retribuiti per motivi personali** (c. 12, la novità del rinnovo), **6 giorni non retribuiti** (c. 13), **8 giorni non retribuiti per concorsi ed esami** (c. 14); campo sul tipo di contratto a termine, da cui dipende il regime; validazione delle combinazioni incompatibili e dei tetti; avviso di coerenza col profilo. Nel modulo ATA, corretto il testo sulle cariche elettive che citava l'art. 38 dei docenti anziché l'art. 52 | ✅ |
| `permessi_retribuiti_ata_ti` | Permessi retribuiti, ATA di ruolo | art. 15 cc. 1 e 3 CCNL 2007 | Rimosso un blocco di testo **orfano** sui motivi personali — nessuna opzione lo attivava — e sostituito con il rimando all'**art. 67** (18 ore), che per gli ATA ha preso il posto dell'art. 15 c. 2; art. 38 → art. 52 sulle cariche elettive; tetti per fattispecie; avviso di coerenza | ✅ |
| `permessi_retribuiti_doc_ti` | Permessi retribuiti, docenti di ruolo | art. 15 cc. 1, 2, 3 CCNL 2007 | Completata la citazione monca «i 6 giorni di ferie previsti dall'**art.** del CCNL», che sono quelli dell'art. 13 c. 9, fruibili senza il vincolo di sostituibilità senza oneri; tetti per fattispecie; avviso di coerenza | ✅ |

---

## 3. Che cosa è cambiato, per categoria

**Norme citate che non erano più applicabili.** L'art. 19 nei due moduli a tempo determinato;
l'art. 3 del DPR 395/1988 nei due moduli 150 ore; l'art. 28 c. 6 del CCNL 21/05/2018, che
appartiene al comparto Funzioni Locali, nelle festività soppresse; la L. 15/1968 nei quattro
moduli L. 104; l'art. 3 c. 3 al posto dell'art. 33 c. 6 nei moduli L. 104 per sé; le
dichiarazioni sul referente unico, abolito dal D.Lgs 105/2022.

**Diritti che non erano esigibili.** I tre giorni retribuiti per motivi personali introdotti
dall'art. 35 c. 12 per i supplenti annuali non erano richiedibili da nessun formulario: è la
modifica di maggior impatto pratico del rinnovo sul personale a termine.

**Conteggi.** Ferie e festività soppresse contavano giorni di calendario; ora si contano i
giorni lavorativi, escludendo domeniche, dieci festività nazionali, il lunedì dell'Angelo
calcolato e la ricorrenza del santo patrono configurabile. Nei moduli malattia il conteggio di
calendario è invece **corretto** — il comporto si computa così — ed è ora documentato nel
codice perché non venga «uniformato» per errore.

**Numeri non falsificabili.** I totali sono campi readonly riempiti dal browser: erano quindi
modificabili e restavano vuoti senza JavaScript. Dove il numero finisce su un atto firmato,
viene ora ricalcolato dal server al salvataggio. I tetti verificati sono: 4 giornate di
festività soppresse, 3 giorni al mese e 18 ore mensili per la L. 104, 2 ore al giorno per i
permessi della persona con disabilità, 36 ore annue per i permessi brevi ATA, 2 ore per
singolo permesso breve dei docenti, 150 ore annue per il diritto allo studio, e i tetti per
fattispecie dei permessi retribuiti.

**Vicoli ciechi rimossi.** In diversi moduli i totali readonly erano obbligatori: senza
JavaScript la domanda era bloccata su campi che l'utente non poteva compilare. L'obbligo è
stato spostato sui campi effettivamente compilabili.

---

## 4. Infrastruttura condivisa aggiunta a `entypa`

| Elemento | Dove | A cosa serve |
|---|---|---|
| `entypa_giorni_lavorativi()` | `entypa.module` | Conteggio dei giorni lavorativi, usato da ferie, festività soppresse e L. 104 |
| `_entypa_pasquetta()` | `entypa.module` | Pasqua gregoriana con l'algoritmo di Meeus, senza dipendere dall'estensione `calendar` |
| `entypa_get_patrono()` | `entypa.module` | Ricorrenza del santo patrono, configurabile in `entypa.settings` |
| `entypa_aggiungi_validazione()` | `entypa.module` | Aggancia una validazione a `#element_validate`; da usare **al posto di** `$form['#validate']`, che Webform non esegue in modo affidabile quando il bottone premuto ha propri validatori |
| `entypa/giorni-lavorativi` | `entypa.libraries.yml`, `js/` | Stesso conteggio lato browser, dichiarato come dipendenza delle librerie `webform.javascript.*` |
| Campo «Ricorrenza del santo patrono» | `EntypaSettingsForm` | Configurazione per istituto, nella forma `GG/MM` |

Due regole imparate sul campo, utili a chi interverrà dopo:

- **L'errore di validazione non va mai messo sul contenitore di una tabella.**
  `FormState::getError()` risale i `#parents`, quindi ogni sottocampo di ogni riga
  ristamperebbe lo stesso messaggio. Va su un campo semplice.
- **`drush php:script` esegue come utente anonimo.** Gli elementi con `#access_*_roles`
  risultano allora inaccessibili e Webform ne sopprime la validazione su uno stato di form
  temporaneo, buttando via gli errori. Le prove vanno autenticate.

---

## 5. Questioni aperte

**Da decidere con la scuola o i sindacati**

1. **Fruizione oraria della L. 104 per i docenti nel modulo di assistenza.** L'art. 68, che la
   consente, riguarda il personale ATA; l'art. 15 c. 6 parla di giornate. L'opzione è stata
   lasciata in attesa di riscontro. Nel modulo dei permessi *per sé* il problema non si pone:
   lì l'ora nasce dall'art. 33 c. 2 richiamato dal c. 6, che vale per chiunque.
2. **Part-time non superiore al 50% nella libera professione.** L'art. 53 c. 6 esclude quei
   rapporti dal regime autorizzatorio, ma l'art. 508 è espressamente fatto salvo dall'art. 53
   c. 1. Il modulo espone entrambe le norme e non decide.
3. **Certificazione di frequenza del diritto allo studio.** L'impegno è dichiarato, ma manca un
   canale per depositare le attestazioni dopo la fruizione.

**Da valutare come sviluppo**

4. **Controlli sul cumulo annuale.** Tutti i tetti sono verificati sulla singola domanda.
   Verificare il cumulo richiede di leggere le istanze precedenti dello stesso dipendente, e
   porta con sé decisioni non tecniche: se contare le domande inviate o solo quelle
   autorizzate, come trattare quelle annullate, e come sbloccare una domanda legittima fermata
   da uno storico sporco. La proposta sul tavolo è un avviso al dirigente, non un blocco.
5. **`email_referenti_plesso`.** Presente in nove formulari, nascosto via CSS ed escluso da
   riepilogo e mail; **nessun handler lo usa**, né in copia né in copia nascosta. Si raccoglie
   un indirizzo e non se ne fa nulla.
6. **Ruoli negli `#access_*_roles`.** I moduli corretti usano ora solo `authenticated`. Erano
   diffusi riferimenti a ruoli inesistenti su questa installazione (`administrator`,
   `dirigente`, `direttore`, `dsga`, `vicario_ds`, `uff_personale`), che non concedevano nulla
   a nessuno.

**Istituti senza formulario**

| Istituto | Norma | Misura |
|---|---|---|
| Permessi orari per motivi personali o familiari, ATA | art. 67 CCNL 19/21 | 18 ore per anno scolastico, minimo un'ora |
| Visite, terapie, prestazioni specialistiche ed esami, ATA | art. 69 CCNL 19/21 | 18 ore per anno scolastico, preavviso di 3 giorni |
| Ferie del personale ATA | art. 13 CCNL 2007 | 32 giorni; 15 continuativi fra 1º luglio e 31 agosto |
| 6 giorni di ferie dei docenti in attività didattica | art. 13 c. 9 e art. 15 c. 2 CCNL 2007 | convertibili in permesso per motivi personali |

---

## 6. Messa in esercizio

Le modifiche stanno nei file dei moduli. Per applicarle:

```bash
ddev drush cim -y    # configurazioni dei formulari
ddev drush cr        # hook nuovi (presave, form_alter, library_info_alter)
```

Il `cr` non è opzionale: Drupal tiene in cache l'elenco di quali moduli implementano quali
hook, e presave, validazioni e libreria condivisa sono implementazioni nuove.

Durante la lavorazione sono già stati importati i formulari `permessi_150ore_ata`,
`permessi_150ore_doc`, `permessi_brevi_art16_ata`, `permessi_brevi_art16_doc` e i quattro
`permessi_retribuiti`, per poterne verificare le validazioni contro la configurazione reale.

Va inoltre configurata la **ricorrenza del santo patrono** nelle impostazioni di Entýpa, in
forma `GG/MM`: senza quel valore il conteggio dei giorni di ferie e festività esclude
domeniche e festività nazionali, ma non il patrono.
