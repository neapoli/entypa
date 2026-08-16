# config/update

Configurazioni di Entýpa che devono restare **sempre** quelle nostre e uguali su
tutte le scuole, anche dopo modifiche fatte a mano sul singolo sito.

## Differenza con le altre cartelle

| Cartella | Chi la legge | Quando |
| --- | --- | --- |
| `config/install` | core | all'installazione del modulo; errore se la configurazione esiste già |
| `config/optional` | core | all'installazione e quando vengono abilitati i moduli da cui dipende; salta le configurazioni già presenti |
| `config/update` | questo modulo | **a ogni** `drush updb`, tramite `src/Drush/Commands/EntypaCommands.php` |

Una configurazione va messa **o** in `config/optional` **o** qui, non in
entrambe: `config/optional` viene ignorata se la configurazione esiste già,
quindi per ciò che va riallineato a ogni aggiornamento fa fede solo questa
cartella.

Alla prima installazione del modulo i file di questa cartella vengono comunque
creati da `_entypa_install_config_update()` in `entypa.install`, altrimenti su
un sito nuovo le viste e i display della Modulistica online non esisterebbero
fino al primo `drush updb`.

## Come si scrivono i file

Si esportano da un sito allineato, per esempio:

```bash
ddev drush config:get views.view.modulistica_online --format=yaml > \
  web/modules/custom/entypa/config/update/views.view.modulistica_online.yml
```

Poi si **rimuovono le chiavi `uuid:` e `_core:`**. Servono a rendere il file
applicabile a scuole diverse, dove la stessa configurazione ha uuid diversi:
per le entità già presenti core conserva l'uuid del sito.

## Come si applicano

Automaticamente **a ogni** `ddev drush updb`, anche quando non ci sono
aggiornamenti in sospeso: se ne occupa l'hook post-comando in
`src/Drush/Commands/EntypaCommands.php`. Oppure a mano:

```bash
ddev drush entypa:config-update
```

Le configurazioni presenti vengono sovrascritte, quelle mancanti create.
Nessuna configurazione viene mai cancellata.

## Contenuto attuale

- `views.view.modulistica_online.yml` — elenco della modulistica (`/admin/modulistica-online`)
- `views.view.modulistica_online_istanze_inviate.yml` — istanze inviate dal personale
- `core.entity_form_display.node.modulistica_online.default.yml`
- `core.entity_view_display.node.modulistica_online.default.yml`
- `core.entity_view_display.node.modulistica_online.full.yml`
- `pathauto.pattern.contenuto_modulistica_online.yml`