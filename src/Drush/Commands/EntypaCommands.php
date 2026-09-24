<?php

namespace Drupal\entypa\Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Drush\Commands\config\ConfigImportCommands;
use Drush\Commands\core\UpdateDBCommands;
use Drush\Drush;

/**
 * Comandi Drush del modulo Entýpa.
 */
class EntypaCommands extends DrushCommands {

  /**
   * Evita di ripetere l'allineamento se il comando viene annidato.
   *
   * @var bool
   */
  protected $applied = FALSE;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Costruttore.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list.
   */
  public function __construct(ModuleExtensionList $module_extension_list) {
    parent::__construct();
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('extension.list.module')
    );
  }

  /**
   * Riapplica config/update dopo ogni "drush updb".
   *
   * Esegue lo stesso comando che si lancerebbe a mano:
   * @code
   * drush -y config:import --partial --source=.../entypa/config/update
   * @endcode
   * A differenza di hook_post_update_NAME(), che core esegue una sola volta per
   * sito, questo hook parte a ogni esecuzione di updatedb, anche quando non ci
   * sono aggiornamenti in sospeso.
   *
   * @param mixed $result
   *   Il risultato del comando updatedb.
   * @param \Consolidation\AnnotatedCommand\CommandData $command_data
   *   I dati del comando in esecuzione.
   */
  #[CLI\Hook(type: HookManager::POST_COMMAND_HOOK, target: UpdateDBCommands::UPDATEDB)]
  public function reapplyEntypaConfig($result, CommandData $command_data): void {
    if ($this->applied) {
      return;
    }
    $this->applied = TRUE;

    $this->importConfigUpdate();
  }

  /**
   * Riallinea le configurazioni di config/update di Entýpa e dei sottomoduli.
   *
   * Core legge config/install e config/optional una sola volta, quando il
   * modulo viene installato: le modifiche che facciamo dopo non arriverebbero
   * mai ai siti già in opera. Per questo i formulari e le viste stanno in
   * config/update, che riversiamo qui a ogni aggiornamento.
   *
   * Si raccolgono solo i sottomoduli davvero installati su questo sito: una
   * scuola sceglie quali formulari attivare, e importare anche gli altri ne
   * creerebbe di non voluti.
   *
   * Utile anche a mano, senza attendere il prossimo "drush updb".
   */
  #[CLI\Command(name: 'entypa:config-update', aliases: ['entypa-cu'])]
  #[CLI\Usage(name: 'drush entypa:config-update', description: 'Riapplica le configurazioni di Entýpa e dei sottomoduli installati.')]
  public function importConfigUpdate(): void {
    $cartelle = $this->cartelleDaRiapplicare();

    // Senza file da importare config:import uscirebbe in errore.
    if (!$cartelle) {
      $this->logger()->notice(dt('Entýpa: nessuna configurazione da riapplicare (config/update vuota).'));
      return;
    }

    $this->logger()->notice(dt('Entýpa: riapplico le configurazioni di @moduli.', [
      '@moduli' => implode(', ', array_keys($cartelle)),
    ]));

    // config:import legge una sola cartella: le si raduna tutte in una
    // temporanea, così l'importazione è una e risolve da sé l'ordine delle
    // dipendenze fra le configurazioni dei vari moduli.
    $source = $this->radunaConfigurazioni($cartelle);

    try {
      $process = $this->processManager()->drush(
        Drush::aliasManager()->getSelf(),
        ConfigImportCommands::IMPORT,
        [],
        ['partial' => TRUE, 'source' => $source, 'yes' => TRUE]
      );

      // Volutamente run() e non mustRun(): config:import valida le dipendenze e
      // fallisce, per esempio, se una configurazione cita un modulo non
      // installato su questa scuola. Con mustRun() l'errore interromperebbe
      // l'intero "drush updb"; così l'aggiornamento arriva in fondo e il
      // problema resta ben visibile nel log.
      $process->run();
      $this->output()->writeln($process->getOutput());

      if (!$process->isSuccessful()) {
        $this->logger()->error(dt("Entýpa: l'allineamento di config/update è fallito. @error", [
          '@error' => $process->getErrorOutput(),
        ]));
        return;
      }
    }
    finally {
      $this->rimuoviCartella($source);
    }

    $this->logger()->success(dt('Entýpa: configurazioni di config/update riapplicate.'));
  }

  /**
   * Elenca le cartelle config/update da riversare nel sito.
   *
   * @return array
   *   Il percorso della cartella, per nome di modulo.
   */
  protected function cartelleDaRiapplicare(): array {
    $cartelle = [];

    foreach (array_merge(['entypa'], $this->sottomoduliInstallati()) as $modulo) {
      $cartella = DRUPAL_ROOT . '/' . $this->moduleExtensionList->getPath($modulo) . '/config/update';

      if (is_dir($cartella) && glob($cartella . '/*.yml')) {
        $cartelle[$modulo] = $cartella;
      }
    }

    return $cartelle;
  }

  /**
   * Elenca i sottomoduli di Entýpa installati su questo sito.
   *
   * Il riconoscimento è sul percorso e non sul nome: i sottomoduli non hanno
   * un prefisso comune — l104_ata, malattia_doc, libera_professione — ma
   * stanno tutti dentro entypa/modules.
   *
   * @return array
   *   I nomi dei moduli, in ordine alfabetico.
   */
  protected function sottomoduliInstallati(): array {
    $dentro = $this->moduleExtensionList->getPath('entypa') . '/modules/';
    $nomi = [];

    foreach (array_keys($this->moduleExtensionList->getAllInstalledInfo()) as $modulo) {
      if ($modulo !== 'entypa' && str_starts_with($this->moduleExtensionList->getPath($modulo), $dentro)) {
        $nomi[] = $modulo;
      }
    }

    sort($nomi);

    return $nomi;
  }

  /**
   * Copia le configurazioni dei moduli in un'unica cartella temporanea.
   *
   * @param array $cartelle
   *   Il percorso della cartella config/update, per nome di modulo.
   *
   * @return string
   *   Il percorso della cartella temporanea, da rimuovere dopo l'uso.
   */
  protected function radunaConfigurazioni(array $cartelle): string {
    $temporanea = sys_get_temp_dir() . '/entypa-config-update-' . getmypid() . '-' . uniqid();
    mkdir($temporanea, 0700, TRUE);

    $provenienza = [];

    foreach ($cartelle as $modulo => $cartella) {
      foreach (glob($cartella . '/*.yml') as $file) {
        $nome = basename($file);

        // Due moduli che spedissero la stessa configurazione si sovrascrivono
        // a vicenda, e quale vinca dipenderebbe dall'ordine: meglio dirlo.
        if (isset($provenienza[$nome])) {
          $this->logger()->warning(dt('Entýpa: @file è fornita sia da @primo sia da @secondo; vince @secondo.', [
            '@file' => $nome,
            '@primo' => $provenienza[$nome],
            '@secondo' => $modulo,
          ]));
        }

        copy($file, $temporanea . '/' . $nome);
        $provenienza[$nome] = $modulo;
      }
    }

    return $temporanea;
  }

  /**
   * Rimuove la cartella temporanea e il suo contenuto.
   *
   * @param string $cartella
   *   Il percorso della cartella da rimuovere.
   */
  protected function rimuoviCartella(string $cartella): void {
    foreach (glob($cartella . '/*') ?: [] as $file) {
      unlink($file);
    }

    rmdir($cartella);
  }

}