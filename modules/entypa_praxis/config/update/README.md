# config/update

La vista dell'istruttoria delle istanze, che deve restare sempre quella del
modulo e uguale su tutte le scuole, anche dopo modifiche fatte a mano sul
singolo sito.

Vale per intero quanto spiegato in `entypa/config/update/README.md`: stesso
meccanismo, stesso modo di scrivere i file (si esportano con `drush config:get`
e si tolgono le chiavi `uuid:` e `_core:`), stessa regola per cui una
configurazione va messa **o** in `config/optional` **o** qui, mai in entrambe.

Si riapplicano da sole a ogni `drush updb`, oppure a mano:

```bash
ddev drush entypa:config-update
```

È il comando di Entýpa a raccogliere anche questa cartella, come fa con quelle
dei sottomoduli dei formulari: il meccanismo è uno solo per tutto il modulo.

Alla prima installazione ci pensa `entypa_importa_config_update('entypa_praxis')`
in `entypa_praxis.install`, perché core questa cartella non la legge.

## Contenuto attuale

- `views.view.admin_entypa_praxis.yml` — istruttoria delle istanze
  (`/admin/entypa-praxis`, con le schede Inviate, Evase e Annullate)
