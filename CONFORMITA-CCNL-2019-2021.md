# Conformità dei formulari Entypa al CCNL Istruzione e Ricerca 2019-2021

Ricognizione dei 22 formulari in `web/modules/custom/entypa/modules/` rispetto al CCNL
comparto «Istruzione e Ricerca» 2019-2021, Sezione Scuola, sottoscritto il 18 gennaio 2024,
e alle fonti di legge che regolano i singoli istituti.

**Data della ricognizione:** 17 settembre 2026
**Testo normativo di riferimento:** testo definitivo della Sezione Scuola aggiornato alla
sottoscrizione del 18/01/2024, che riporta in corsivo gli articoli del CCNL 29/11/2007
ancora vigenti e indica espressamente le abrogazioni.

> **Avvertenza.** Questa è una ricognizione tecnica sui formulari, non un parere giuridico.
> Dice che cosa ciascun modulo raccoglie e dichiara, e dove questo diverge dal testo
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
| Permessi retribuiti, tempo indeterminato | art. 15 CCNL 29/11/2007 | vigente (comma 2 e comma 6: solo docenti) |
| Permessi brevi | art. 16 CCNL 29/11/2007 | vigente |
| Assenze per malattia | art. 17 CCNL 29/11/2007 | vigente |
| Ferie, permessi e assenze del personale a tempo **determinato** | **art. 35 CCNL 19/21** | nuovo — **abroga l'art. 19 CCNL 29/11/2007** (art. 35, c. 16) |
| Diritto allo studio (150 ore) | **art. 37 CCNL 19/21** | nuovo — **disapplica l'art. 3 del DPR 395/1988** (art. 37, c. 7) |
| Permessi orari motivi personali/familiari **ATA** (18 ore) | **art. 67 CCNL 19/21** | nuovo — abroga l'art. 31 CCNL 19/04/2018, che aveva abrogato l'art. 15 c. 2 CCNL 2007 per gli ATA |
| Permessi di legge **ATA**, incluso L. 104 ad ore | **art. 68 CCNL 19/21** | nuovo — L. 104 fruibile ad ore nel limite di 18 ore mensili |
| Visite, terapie, prestazioni specialistiche, esami **ATA** (18 ore) | **art. 69 CCNL 19/21** | nuovo — abroga l'art. 33 CCNL 19/04/2018 |
| Cariche pubbliche elettive | art. 38 (docenti) e art. 52 (ATA) CCNL 29/11/2007 | vigenti |

Fonti di legge richiamate dai formulari: L. 104/1992 art. 33 c. 3 (**come modificato dal
D.Lgs 105/2022, in vigore dal 13 agosto 2022**), L. 937/1977, D.Lgs 297/1994 art. 508,
D.Lgs 165/2001 art. 53, L. 584/1967 e L. 107/1990, L. 52/2001 art. 5, L. 53/2000 art. 4 c. 1.

---

## 2. Tabella di conformità

Legenda della colonna «esito»: **A** = citazione normativa errata o superata · **B** = istituto
o fattispecie mancante · **C** = conforme, con margini di miglioramento.

| Webform | Disciplina | Articolo CCNL / legge | Cosa hai oggi | Cosa manca | Correzione proposta | Esito |
|---|---|---|---|---|---|---|
| `permessi_retribuiti_ata_td`<br>`permessi_retribuiti_doc_td` | Permessi del personale a tempo determinato | **art. 35 CCNL 19/21** (abroga art. 19 CCNL 2007) | Titolo «Permessi retribuiti **art. 19**». Opzioni: lutto, matrimonio, donazione sangue, donazione midollo, cariche elettive, testimonianza. Solo richiesta a giorni (`data_inizio`/`data_fine`/`numero_giorni`) | L'articolo citato è **abrogato**. Mancano: i **3 giorni retribuiti per motivi personali o familiari** per i contratti annuali o al 30 giugno (art. 35 c. 12); i **6 giorni non retribuiti** per gli altri contratti a termine (c. 13); gli **8 giorni non retribuiti per concorsi ed esami** (c. 14). Manca la distinzione fra tipo di contratto, da cui dipende tutto il resto | Cambiare il titolo in «Permessi — personale a tempo determinato (art. 35 CCNL 2019-2021)». Aggiungere un radio sul tipo di contratto (annuale 31/08 o 30/06 · altro contratto a termine) e far dipendere da quello le tipologie ammesse, con l'indicazione se il permesso è retribuito o no. Per gli ATA, l'art. 35 c. 12 consente di fruire i 3 giorni **anche ad ore** con le modalità dell'art. 67 | **A + B** |
| `permessi_retribuiti_ata_ti` | Permessi retribuiti, ATA a tempo indeterminato | art. 15 cc. 1 e 3 CCNL 2007 | Concorsi/esami 8 gg, lutto 3 gg, matrimonio 15 gg, donazioni, cariche elettive, testimonianza. Testi dei singoli istituti corretti e dettagliati | Nulla di errato: l'assenza dell'opzione «motivi personali» è corretta, perché per gli ATA quell'istituto non è più l'art. 15 c. 2 ma l'**art. 67** (18 ore). Resta però scoperto: non esiste alcun formulario per l'art. 67 | Lasciare le opzioni come sono. Creare il formulario dedicato all'art. 67 (vedi §4). Nel titolo, precisare «art. 15 CCNL 2007» invece del generico «art. 15» | **C** |
| `permessi_retribuiti_doc_ti` | Permessi retribuiti, docenti a tempo indeterminato | art. 15 cc. 1, 2, 3 CCNL 2007 | Come sopra, più l'opzione «Motivi familiari/personali» (3 giorni), corretta per i docenti | Nel testo dell'opzione manca il numero dell'articolo: «i 6 giorni di ferie previsti dall'**art.** del CCNL». Sono i 6 giorni dell'**art. 13 c. 9**, convertibili in permesso ai sensi dell'art. 15 c. 2 | Completare la citazione: «i 6 giorni di ferie previsti dall'art. 13, comma 9, del CCNL 29/11/2007». Aggiungere che, se fruiti per i motivi dell'art. 15 c. 2, non opera la condizione di non creare oneri | **A** |
| `permessi_brevi_art16_ata`<br>`permessi_brevi_art16_doc` | Permessi brevi | art. 16 CCNL 2007 (vigente) | Tabella oraria con giorno, ore, plesso (e classe per i docenti); radio sul recupero con la dicitura corretta «entro i due mesi lavorativi successivi»; dichiarazione che il coordinatore è informato e provvederà alla copertura (art. 16 c. 5) | Nessun tetto è imposto: **36 ore annue** per gli ATA e **orario settimanale di insegnamento** per i docenti (c. 2); durata non superiore **alla metà dell'orario giornaliero** e, per i docenti, **massimo 2 ore** (c. 1). Non è detto che per i docenti l'unità minima è l'ora di lezione | I tetti annui dipendono dal pregresso e non sono calcolabili dal modulo: esplicitarli nel testo introduttivo e far dichiarare al richiedente le ore già fruite nell'anno scolastico. Il limite per singola richiesta (metà orario giornaliero, 2 ore per i docenti) è invece verificabile e va imposto sul totale | **C** |
| `l104_ata`<br>`l104_doc` | Permessi L. 104 per sé | L. 104/1992 art. 33 c. 3; art. 68 CCNL 19/21 (ATA); art. 15 c. 6 CCNL 2007 (docenti) | Scelta fra permesso orario, giornaliero o entrambi; tabelle con totali calcolati; dichiarazioni sulla propria condizione di disabilità e sull'impegno a comunicare variazioni | Manca il **limite di 3 giorni mensili**. Per gli ATA manca il riferimento all'art. 68 e il limite di **18 ore mensili** per la fruizione oraria, e manca la **programmazione mensile** da comunicare a inizio mese (art. 68 c. 2) con la deroga d'urgenza delle 24 ore (c. 3). Per i **docenti** la fruizione oraria non è prevista: l'art. 68 riguarda gli ATA, e l'art. 15 c. 6 dispone che i docenti fruiscano i permessi «possibilmente in giornate non ricorrenti» | Citare l'articolo giusto nei due moduli, distinti per profilo. Nel modulo ATA: aggiungere il limite delle 18 ore mensili e un campo sul mese di programmazione, con opzione «richiesta urgente». Nel modulo docenti: rivedere l'opzione «permesso orario» con la segreteria, perché non ha base contrattuale | **A + B** |
| `l104_assistenza_ata`<br>`l104_assistenza_doc` | Permessi L. 104 per assistenza a familiare | L. 104/1992 art. 33 c. 3, **come modificato dal D.Lgs 105/2022**; art. 68 CCNL 19/21 (ATA) | Come sopra, più i dati del familiare (cognome, nome, grado di parentela) e quattro dichiarazioni | **La dichiarazione 3 è superata**: «Assenza di altri lavoratori nel nucleo familiare dell'assistito che fruiscono del permesso» presuppone il **referente unico**, principio **abolito dal D.Lgs 105/2022 dal 13 agosto 2022**: più lavoratori possono ora fruire dei permessi per la stessa persona, in alternanza fra loro. Stesso problema per la dichiarazione 4 sull'«assistenza esclusiva». Mancano inoltre il **codice fiscale dell'assistito** e gli estremi del **verbale di accertamento della gravità** | Sostituire le dichiarazioni 3 e 4 con la dichiarazione di fruizione **in alternanza** con gli altri eventuali aventi diritto, indicandoli. Aggiungere codice fiscale dell'assistito ed estremi del verbale. Per il resto vale quanto detto sopra sul limite mensile e sulla fruizione oraria dei docenti | **A + B** |
| `permessi_150ore_ata`<br>`permessi_150ore_doc` | Diritto allo studio | **art. 37 CCNL 19/21** | Testo: «ai sensi dell'**art. 3 del D.P.R. 395/1988** e dell'**art. 15, comma 7**, del C.C.N.L.». Tabella oraria, residenza, istituto di iscrizione, allegato facoltativo | Entrambe le citazioni sono superate: l'art. 37 c. 7 **disapplica l'art. 3 del DPR 395/1988** e abroga l'art. 146 c. 1 lett. g) p. 1 del CCNL 2007. Mancano: l'**anno solare** di riferimento (le 150 ore sono per anno solare, non scolastico); il richiamo al contingente del **3%**; l'obbligo di produrre la **certificazione di iscrizione, frequenza ed esami**, la cui mancanza converte i permessi in aspettativa senza assegni con recupero delle somme (art. 37 c. 5) | Riscrivere il testo citando l'art. 37 del CCNL 2019-2021. Aggiungere il campo «anno solare di riferimento». Rendere obbligatorio l'allegato con la certificazione di iscrizione, e aggiungere la dichiarazione di impegno a produrre le attestazioni di frequenza ed esami, con l'avvertenza sulle conseguenze | **A + B** |
| `festivita_soppresse_ata`<br>`festivita_soppresse_doc` | Festività soppresse | art. 14 CCNL 2007; L. 937/1977 | Testo: «Con riferimento all'**art. 18 del CCNL del 6.7.1995**, all'**art. 28, comma 6, del CCNL 21/05/2018**, agli effetti della legge 937/1977» | **L'art. 28 del CCNL 21/05/2018 appartiene al comparto Funzioni Locali**, non alla Scuola: è la norma su ferie e festività soppresse degli enti locali. L'art. 18 del CCNL 1995 è storico e superato. La norma vigente è l'**art. 14 del CCNL 29/11/2007**: 4 giornate di riposo, più il Santo Patrono se cade in giorno lavorativo. Per i **docenti** manca il vincolo dell'art. 14 c. 2: fruibili solo fra il termine di lezioni ed esami e l'inizio delle lezioni successive, o durante la sospensione delle lezioni | Sostituire il testo: «ai sensi dell'art. 14 del CCNL 29/11/2007 e della legge 23 dicembre 1977, n. 937». Nel modulo docenti aggiungere l'avvertenza sul periodo di fruizione. Utile chiarire che le 4 giornate sono cosa diversa dalle 2 giornate della L. 937/77 già comprese nei 32 giorni di ferie (art. 13 c. 2) | **A + B** |
| `ferie_estive_doc` | Ferie | art. 13 CCNL 2007, commi 14-15 sostituiti dall'art. 38 CCNL 19/21 | Testo: «ai sensi del C.C.N.L. comparto scuola del 29.11.2007». Anno di riferimento (corrente o precedente), periodi, totale giorni | La citazione è generica. Mancano i riferimenti puntuali e il dato sul **monte ferie** (32 giorni, 30 per i neoassunti nei primi 3 anni, art. 13 cc. 2-4). Il modulo copre solo le ferie estive: restano scoperti i **6 giorni durante l'attività didattica** (art. 13 c. 9) e non esiste alcun modulo ferie per gli **ATA** | Citare «art. 13 del CCNL 29/11/2007, come modificato dall'art. 38 del CCNL 2019-2021». Per l'anno precedente, richiamare il c. 10: i docenti fruiscono entro l'anno scolastico successivo nei periodi di sospensione. Creare i moduli mancanti (vedi §4) | **A + B** |
| `malattia_ata`<br>`malattia_doc` | Assenze per malattia | art. 17 CCNL 2007; art. 35 cc. 3-7 CCNL 19/21 per il tempo determinato | Tipologia (malattia, esami/terapie, prolungamento, gravi patologie, ricovero, day hospital, convalescenza), periodo, protocollo del certificato, reperibilità | Nessuna distinzione fra tempo indeterminato (comporto di **18 mesi**, art. 17 c. 1) e tempo determinato, dove il comporto è **9 mesi nel triennio** per i contratti annuali o al 30 giugno e **30 giorni annui retribuiti al 50%** per le supplenze brevi (art. 35 cc. 3-6). Nel modulo docenti il testo dice «di cui all'articolo sopra indicato» **senza che alcun articolo sia indicato sopra**. Per gli ATA, «esami diagnostici o terapie» si sovrappone al regime autonomo dell'**art. 69** (18 ore annue), che ha computo e trattamento economico diversi | Correggere il rinvio pendente nel modulo docenti. Aggiungere il tipo di rapporto e mostrare l'avvertenza sul comporto corrispondente. Per gli ATA, separare le assenze per visite ed esami dell'art. 69 in un modulo proprio (vedi §4) e lasciare qui la sola malattia | **A + B** |
| `incarico_retribuito` | Incarichi retribuiti extra-istituzionali | **D.Lgs 165/2001 art. 53**; D.Lgs 297/1994 art. 508 | Testo: «ai sensi dell'**art. 508 del D.Lgs 297/94**». Raccolta completa: committente, codice fiscale e partita IVA, sede, importo presunto, ore, cinque dichiarazioni e impegni | La norma che governa l'autorizzazione agli incarichi retribuiti è l'**art. 53 del D.Lgs 165/2001**, che qui non è citato: l'art. 508 riguarda l'incompatibilità e la libera professione dei docenti. Mancano il richiamo al termine di **30 giorni** per la pronuncia (45 se il dipendente presta servizio presso altra amministrazione) e all'effetto del silenzio, che vale come assenso solo se l'incarico è conferito da un'amministrazione pubblica e come diniego in ogni altro caso (art. 53, comma 10). Manca anche il richiamo alla comunicazione dei compensi entro 15 giorni, che il comma 11 pone in capo al soggetto conferente, e alla comunicazione dell'amministrazione al Dipartimento della funzione pubblica (comma 12) | Aggiungere l'art. 53 del D.Lgs 165/2001 come riferimento principale, mantenendo l'art. 508 per il personale docente. Esplicitare il termine di riscontro, l'effetto del silenzio distinto per committente pubblico o privato, e la destinazione dei dati comunicati | **A** |
| `libera_professione` | Libera professione | D.Lgs 297/1994 art. 508 | Testo e struttura corretti per l'istituto: tipologia di contratto, albo professionale, dichiarazioni di conferibilità e compatibilità | Il regime differisce a seconda del **regime orario** (il modulo raccoglie già full time e part-time sopra o sotto il 50%) ma il modulo non ne trae conseguenze, mentre il part-time non superiore al 50% ha una disciplina propria | Legare le dichiarazioni richieste al regime orario dichiarato, oppure indicarne le conseguenze nel testo introduttivo | **C** |
| `cambio_turno_ata` | Cambio turno | art. 66 CCNL 19/21 (turnazioni) e contrattazione integrativa d'istituto | Turno previsto e modificato, plesso, collega, consenso del collega, motivo, riorganizzazione del servizio | Nessun riferimento normativo nel testo. Lo scambio di turno è materia della contrattazione integrativa d'istituto | Richiamare l'art. 66 del CCNL e il contratto integrativo d'istituto, con l'anno di riferimento | **C** |
| `cambio_turno_doc` | Sostituzione o scambio di ore | — | Struttura identica alla versione ATA, con la terminologia della turnazione | Il personale docente non è soggetto a turnazione: per i docenti si tratta di uno scambio di ore di lezione, istituto diverso | Rivedere la denominazione e il testo, o valutare se il modulo debba esistere in questa forma | **C** |
| `adesione_commissioni` | Disponibilità per commissioni e gruppi di lavoro | — (organizzazione interna) | Elenco delle commissioni, note, firma | Nessun rilievo di conformità. Nota tecnica: `codice_fiscale` è obbligatorio mentre `cognome` e `nome` no, pur essendo tutti precompilati dal profilo | Uniformare l'obbligatorietà dei dati anagrafici | **C** |

---

## 3. Riepilogo per priorità

**Da correggere per primi — citano norme abrogate, disapplicate o di un altro comparto**

1. `permessi_retribuiti_ata_td` e `permessi_retribuiti_doc_td`: l'art. 19 è abrogato dall'art. 35
   c. 16, e con esso manca il diritto nuovo introdotto dal rinnovo — i 3 giorni retribuiti ai
   supplenti annuali. È la modifica di maggior impatto pratico del CCNL 2019-2021 sul personale
   a termine, e oggi i formulari non la rendono esigibile.
2. `permessi_150ore_ata` e `permessi_150ore_doc`: l'art. 3 del DPR 395/1988 è espressamente
   disapplicato dall'art. 37 c. 7.
3. `festivita_soppresse_ata` e `festivita_soppresse_doc`: il CCNL citato è quello delle
   Funzioni Locali.
4. `l104_assistenza_ata` e `l104_assistenza_doc`: la dichiarazione sul referente unico chiede
   al dipendente di attestare una condizione che la legge non richiede più dal 13 agosto 2022,
   e la cui mancanza potrebbe portare a negare un permesso dovuto.

**Da valutare con la segreteria**

5. Fruizione oraria della L. 104 per i **docenti**: nessuna base contrattuale.
6. Assenza di ogni controllo sui tetti (3 giorni mensili, 36 ore annue, 150 ore, 18 ore).

**Correzioni di testo**

7. Rinvio pendente in `malattia_doc` («di cui all'articolo sopra indicato»).
8. Citazione monca in `permessi_retribuiti_doc_ti` («l'art. del CCNL»).
9. Citazione generica del CCNL 2007 in `ferie_estive_doc` e nei due moduli malattia.

---

## 4. Istituti privi di formulario

Il rinnovo del 2024 ha introdotto per il personale ATA tre istituti orari autonomi. Due non
hanno un modulo corrispondente, e il terzo è coperto solo in parte.

| Istituto | Norma | Misura | Stato |
|---|---|---|---|
| Permessi orari retribuiti per motivi personali o familiari, ATA | **art. 67 CCNL 19/21** | 18 ore per anno scolastico, minimo un'ora, giornata intera convenzionalmente pari a 6 ore | **Nessun formulario.** Per gli ATA questo ha sostituito i 3 giorni dell'art. 15 c. 2 |
| Assenze per visite, terapie, prestazioni specialistiche ed esami, ATA | **art. 69 CCNL 19/21** | 18 ore per anno scolastico, comprensive dei tempi di percorrenza; preavviso di 3 giorni; attestazione del medico o della struttura | **Nessun formulario.** Oggi passa impropriamente dal modulo malattia |
| Ferie del personale ATA | art. 13 CCNL 2007 | 32 giorni; frazionabili; almeno 15 giorni continuativi fra il 1º luglio e il 31 agosto | **Nessun formulario** (esiste solo `ferie_estive_doc`) |
| 6 giorni di ferie dei docenti durante l'attività didattica | art. 13 c. 9 e art. 15 c. 2 CCNL 2007 | 6 giorni, convertibili in permesso per motivi personali | Non coperto da `ferie_estive_doc` |

Da verificare inoltre se l'autorizzazione agli incarichi retribuiti ex art. 53 D.Lgs 165/2001
debba essere disponibile anche al personale ATA: `incarico_retribuito` è oggi classificato
nella sola categoria «Docenti».

---

## 5. Metodo

- I contenuti dei formulari sono stati estratti dalle configurazioni
  `modules/*/config/optional/webform.webform.*.yml` con il parser YAML di Symfony, leggendo
  per ciascun elemento tipo, obbligatorietà, opzioni, condizioni e testi normativi.
- Il testo del CCNL è stato letto integralmente nelle parti su ferie, permessi e assenze,
  comprese le clausole di abrogazione e disapplicazione, che sono l'elemento decisivo di
  questa ricognizione.
- Le due modifiche legislative esterne al CCNL richiamate nella tabella — l'abolizione del
  referente unico e l'appartenenza al comparto Funzioni Locali del CCNL 21/05/2018 — sono
  state verificate separatamente.
- Non è stata modificata alcuna configurazione: la colonna «correzione proposta» descrive
  l'intervento, non lo applica.
