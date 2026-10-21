<?php

namespace Drupal\search_api_autocomplete\Hook;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api_autocomplete\SearchApiAutocompleteException;
use Drupal\search_api_solr\SolrBackendInterface;

/**
 * Contains general hook implementations for the Search API Autocomplete module.
 */
class SearchApiAutocompleteHooks {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ModuleHandlerInterface $moduleHandler,
    TranslationInterface $translation,
  ) {
    $this->stringTranslation = $translation;
  }

  /**
   * Implements hook_requirements().
   *
   * This checks whether there is a Solr server with an attached autocomplete
   * search set up on this site, and in this case returns a warning if the
   * "search_api_solr_autocomplete" module is not also enabled.
   */
  public function runtimeRequirements():array {
    // Only execute this during runtime. Also, nothing to do if either the Solr
    // module is not installed, or the "search_api_solr_autocomplete" module is
    // also installed.
    if (
      !interface_exists(SolrBackendInterface::class)
      || $this->moduleHandler->moduleExists('search_api_solr_autocomplete')
    ) {
      return [];
    }

    // We also have to make sure that the "search_api_solr_autocomplete" module
    // would even be available. Easiest way to do this is by checking the
    // current schema version number. However, since that constant was removed
    // in later versions of the "search_api_solr" module, we also check for the
    // existence of a method that was added in a later version. Both the
    // existence of the SolrBackendInterface::getPreferredSchemaVersion() or a
    // schema version of at least 4.2.5 would mean that the
    // "search_api_solr_autocomplete" module is available for installation.
    if (
      !method_exists(SolrBackendInterface::class, 'getPreferredSchemaVersion')
      && version_compare(SolrBackendInterface::SEARCH_API_SOLR_SCHEMA_VERSION, '4.2.5', '<')
    ) {
      return [];
    }

    // Check that there really is at least one autocomplete search set up with a
    // Solr server.
    $searches = $this->entityTypeManager->getStorage('search_api_autocomplete_search')
      ->loadMultiple();
    foreach ($searches as $search) {
      try {
        $index = $search->getIndex();
        if (
          $index->status()
          && $index->hasValidServer()
          && $index->getServerInstance()->getBackend() instanceof SolrBackendInterface
        ) {
          return [
            'search_api_solr_autocomplete' => [
              'title' => 'Search API Solr Autocomplete',
              'value' => $this->t('When using a Solr server as the search backend, it is recommended to enable the "Search API Solr Autocomplete" module for improved autocomplete functionality.'),
              'severity' => DeprecationHelper::backwardsCompatibleCall(
                \Drupal::VERSION,
                '11.2',
                fn () => RequirementSeverity::Warning,
                fn () => REQUIREMENT_WARNING,
              ),
            ],
          ];
        }
      }
      catch (SearchApiException | SearchApiAutocompleteException) {
        // Ignore.
      }
    }

    return [];
  }

}
