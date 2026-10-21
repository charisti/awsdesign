<?php

declare(strict_types=1);

namespace Drupal\drupal_cms_helper;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageCopyTrait;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DefaultContent\Exporter as ContentExporter;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Recipe\Recipe;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @internal
 *   This is an internal part of Drupal CMS and may be changed or removed at any
 *   time without warning. External code should not interact with this class.
 */
final class SiteExporter implements LoggerAwareInterface {

  use LoggerAwareTrait;
  use StorageCopyTrait;
  use StringTranslationTrait;

  public function __construct(
    private readonly ModuleExtensionList $moduleList,
    private readonly ThemeExtensionList $themeList,
    private readonly FileSystemInterface $fileSystem,
    private readonly StorageInterface $storage,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly ConfigManagerInterface $configManager,
    private readonly ContentExporter $contentExporter,
    #[Autowire(param: 'app.root')] private readonly string $appRoot,
  ) {}

  /**
   * Exports the current site's configuration and content into a recipe.
   *
   * @param string $destination
   *   The path where the recipe should be created.
   */
  public function export(string $destination): void {
    $this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    $extensions = $this->getInstalledExtensions();
    $this->updateComposerJson($destination, $extensions);

    // Initially, just export all config as files. Then we'll convert certain
    // items to config actions.
    $storage = new SiteExportFileStorage(
      $this->configManager,
      $this->entityTypeManager,
      $destination . '/config',
    );
    self::replaceStorageContents($this->storage, $storage);
    // From here on out, we're only going to modify the default collection. Any
    // other collections probably just contain translations, and config actions
    // are not translatable (yet).
    $storage = $storage->createCollection(StorageInterface::DEFAULT_COLLECTION);
    // The core.extension config should never be included in a recipe.
    $storage->delete('core.extension');

    $actions = [];
    foreach ($storage->listAll() as $name) {
      if ($this->isAction($name)) {
        $actions[$name] = $this->toAction($name, $storage->read($name));
        $storage->delete($name);
      }
    }
    $this->updateRecipe($destination, array_keys($extensions), $actions ?? []);

    // Export all content, with its dependencies, as files.
    foreach ($this->loadAllContent() as $entity) {
      $this->contentExporter->exportWithDependencies($entity, $destination . '/content');
    }
  }

  /**
   * Loads all exportable content entities.
   *
   * @return iterable<\Drupal\Core\Entity\ContentEntityInterface>
   *   An iterable that yields content entities.
   */
  private function loadAllContent(): iterable {
    foreach ($this->entityTypeManager->getDefinitions() as $id => $entity_type) {
      // Path aliases are created when the content is, and therefore should not
      // be exported. Internal entities are more of a grey area, but we can
      // safely assume they shouldn't be exported (content moderation states are
      // the main example in core).
      if ($entity_type->isInternal() || $id === 'path_alias') {
        continue;
      }
      if ($entity_type->entityClassImplements(ContentEntityInterface::class)) {
        $storage = $this->entityTypeManager->getStorage($id);
        $query = $storage->getQuery()->accessCheck(FALSE);
        // Ignore users 0 or 1, since they always exist with those IDs.
        if ($id === 'user') {
          $query->condition('uid', 1, '>');
        }
        foreach ($query->execute() as $entity_id) {
          yield $storage->load($entity_id);
        }
      }
    }
  }

  /**
   * Finds all installed modules and themes.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   All installed extensions, keyed by machine name.
   */
  private function getInstalledExtensions(): array {
    $modules = array_intersect_key(
      $this->moduleList->getList(),
      $this->moduleList->getAllInstalledInfo(),
    );
    $themes = array_intersect_key(
      $this->themeList->getList(),
      $this->themeList->getAllInstalledInfo(),
    );
    return array_filter(
      [...$modules, ...$themes],
      // Install profiles should always be excluded from recipes.
      fn (Extension $e): bool => $e->getType() !== 'profile',
    );
  }

  /**
   * Generates Composer version constraints for a set of extensions.
   *
   * @param \Drupal\Core\Extension\Extension[] $extensions
   *   A set of extensions.
   *
   * @return array<string, string>
   *   An array of Composer version constraints, keyed by package name.
   */
  private function getExtensionRequirements(array $extensions): array {
    $requirements = [];

    foreach ($extensions as $name => $extension) {
      $package_name = str_starts_with($extension->getPath(), 'core/')
        ? 'drupal/core'
        : 'drupal/' . ($extension->info['project'] ?? $name);

      try {
        $version = InstalledVersions::getPrettyVersion($package_name);
      }
      catch (\OutOfBoundsException) {
        $message = $this->t('Cannot determine a version constraint for @type @names because the package @package does not appear to be installed.', [
          '@type' => $extension->getType(),
          '@name' => $name,
          '@package' => $package_name,
        ]);
        $this->logger?->warning((string) $message);
        continue;
      }

      $stability = VersionParser::parseStability($version);
      $stability = VersionParser::normalizeStability($stability);
      $requirements[$package_name] = $stability === 'dev' ? $version : "^$version";

      if ($stability !== 'stable') {
        $message = $this->t('Package @package has a @stability version constraint, which may prevent the recipe from being installed into projects that require stable dependencies.', [
          '@stability' => $stability,
          '@package' => $package_name,
        ]);
        $this->logger?->warning((string) $message);
      }
    }
    return $requirements;
  }

  /**
   * Alters `composer.json` to match the site being exported.
   *
   * - The `type` key is always set to `drupal-recipe`.
   * - Version constraints are generated for all installed extensions and added
   *   to the `require` section; existing constraints are preserved.
   *
   * @param string $destination
   *   The directory where the site is being exported.
   * @param \Drupal\Core\Extension\Extension[] $extensions
   *   All installed extensions.
   */
  private function updateComposerJson(string $destination, array $extensions): void {
    $data = [];

    $destination .= '/composer.json';
    if (file_exists($destination)) {
      $data = file_get_contents($destination);
      $data = Json::decode($data);
    }
    $data['require'] = array_merge(
      $this->getExtensionRequirements($extensions),
      $data['require'] ?? [],
    );
    $data['type'] = Recipe::COMPOSER_PROJECT_TYPE;

    $data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    file_put_contents($destination, $data);
  }

  /**
   * Alters `recipe.yml` to match the site being exported.
   *
   * - The `name` key, if defined in recipe.yml, is preserved. Otherwise, it
   *   defaults to the site name.
   * - The `type` key is always set to `Site`.
   * - Any extensions which are not already in the `install` list will be
   *   appended to it.
   * - The given config actions will be deep-merged into the config actions
   *   already in `recipe.yml`, with the given config actions "winning" any
   *   conflicts.
   *
   * @param string $destination
   *   The directory where the site is being exported.
   * @param string[] $extensions
   *   Machine names names of the installed extensions.
   * @param array $actions
   *   Config actions to be merged into the recipe.
   */
  private function updateRecipe(string $destination, array $extensions, array $actions): void {
    $recipe = [];

    $destination .= '/recipe.yml';
    if (file_exists($destination)) {
      $recipe = file_get_contents($destination);
      $recipe = Yaml::decode($recipe);
    }
    $recipe['name'] ??= $this->configFactory->get('system.site')->get('name');
    $recipe['type'] = 'Site';

    // Add any new extensions to the recipe's install list, preserving the order
    // of the extant list (if there is one).
    $recipe['install'] ??= [];
    array_push($recipe['install'], ...array_diff($extensions, $recipe['install']));

    // The passed-in config actions overwrite the ones in the recipe.
    $recipe['config']['actions'] = NestedArray::mergeDeep(
      $recipe['config']['actions'] ?? [],
      $actions,
    );
    // Do a lenient comparison against extant config. In the early installer,
    // the active config storage will be an InstallStorage object that has
    // loaded and enumerated all available simple config as shipped by the
    // providing modules. This is very likely to differ from the simple config
    // shipped with the recipe, so strict mode will very likely fail. If this
    // recipe is a site template being applied at install time (which is the
    // main to export a site as a recipe), lenient mode doesn't make much
    // difference; during the actual install process, only the config shipped
    // with required modules (e.g., System and User) will be present when the
    // recipe is applied, and that stuff is exported as config actions. We use
    // the null-coalescing assignment here in case we're updating a recipe that
    // has, for whatever reason, explicitly opted in to strict mode.
    $recipe['config']['strict'] ??= FALSE;

    file_put_contents($destination, Yaml::encode($recipe));
  }

  /**
   * Exports a config item as a config action.
   *
   * @param string $name
   *   The name of the config item.
   * @param array $data
   *   The config item's data.
   *
   * @return array
   *   The config item, represented as a config action.
   */
  private function toAction(string $name, array $data): array {
    $entity_keys = $this->configManager->loadConfigEntityByName($name)
      ?->getEntityType()
      ->getKeys();

    // If we have an array of entity keys, then this is a config entity.
    if (is_array($entity_keys)) {
      // The `id` and `uuid` keys cannot be changed by the `setProperties`
      // config action, so delete them. In fact, we don't need to touch ANY
      // entity keys.
      // @see \Drupal\Core\Config\Action\Plugin\ConfigAction\SetProperties
      foreach ($entity_keys as $key) {
        unset($data[$key]);
      }
      // Let dependencies be recalculated on save.
      unset($data['dependencies']);

      return ['setProperties' => $data];
    }
    return ['simpleConfigUpdate' => $data];
  }

  /**
   * Determines if a config object needs to be exported in config actions.
   *
   * This is true for all default config shipped by core itself, as well as the
   * System and User modules, because those are guaranteed to be installed
   * before anything else is.
   *
   * @param string $name
   *   The name of a config object.
   *
   * @return bool
   *   Whether the config object can be exported as a file, or needs to be
   *   represented as a config action.
   */
  private function isAction(string $name): bool {
    static $list;
    if ($list === NULL) {
      $list = [];

      $directory = InstallStorage::CONFIG_INSTALL_DIRECTORY;
      $storages = [
        new FileStorage($this->appRoot . "/core/$directory"),
        new FileStorage($this->moduleList->getPath('system') . "/$directory"),
        new FileStorage($this->moduleList->getPath('user') . "/$directory"),
      ];
      foreach ($storages as $storage) {
        $list = array_merge($list, $storage->listAll());
      }
    }
    return in_array($name, $list, TRUE);
  }

}
