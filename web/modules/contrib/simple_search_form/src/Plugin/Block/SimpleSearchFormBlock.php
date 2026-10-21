<?php

namespace Drupal\simple_search_form\Plugin\Block;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\views\Plugin\views\display\DisplayRouterInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'SimpleSearchFormBlock' block.
 *
 * @Block(
 *   id = "simple_search_form_block",
 *   admin_label = @Translation("Simple search form"),
 *   category = @Translation("Search")
 * )
 */
class SimpleSearchFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ModuleHandlerInterface $moduleHandler,
    protected ?EntityTypeManagerInterface $entityTypeManager = NULL,
    protected ?AliasManagerInterface $pathAliasManager = NULL,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if ($this->entityTypeManager === NULL) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $entityTypeManager parameter is deprecated in simple_search_form:8.x-1.9 and will break in simple_search_form:2.0.0. See https://www.drupal.org/node/3565748', E_USER_DEPRECATED);
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }
    if ($this->pathAliasManager === NULL) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $pathAliasManager parameter is deprecated in simple_search_form:8.x-1.9 and will break in simple_search_form:2.0.0. See https://www.drupal.org/node/3565748', E_USER_DEPRECATED);
      $this->pathAliasManager = \Drupal::service(AliasManagerInterface::class);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get(ModuleHandlerInterface::class),
      $container->get(EntityTypeManagerInterface::class),
      $container->get(AliasManagerInterface::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      'form' => [
        '#lazy_builder' => ['simple_search_form.lazy_builder:getForm', [Json::encode($this->getConfiguration())]],
        '#create_placeholder' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();

    $config['action_path'] = '';
    $config['get_parameter'] = '';
    $config['input_type'] = 'search';
    $config['search_api_autocomplete'] = [
      'search_id' => '',
      'display' => '',
      'arguments' => '',
      'filter' => '',
    ];
    $config['input_label_display'] = 'before';
    $config['input_label'] = 'Search';
    $config['input_placeholder'] = '';
    $config['input_css_classes'] = '';
    $config['submit_display'] = TRUE;
    $config['submit_label'] = 'Find';
    $config['input_keep_value'] = FALSE;
    $config['preserve_url_query_parameters'] = [];

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form['action_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#description' => $this->t('The path to redirect to.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['action_path'] ?: $this->guessActionPath(),
    ];
    $form['get_parameter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GET parameter'),
      '#description' => $this->t('The $_GET parameter name.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['get_parameter'] ?: $this->guessGetParameter(),
    ];
    $form['input_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Input element type'),
      '#options' => [
        'search' => $this->t('Search'),
        'textfield' => $this->t('Text field'),
      ],
      '#default_value' => $this->configuration['input_type'],
    ];

    if ($this->moduleHandler->moduleExists('search_api_autocomplete')) {
      $form['input_type']['#options']['search_api_autocomplete'] = $this->t('Search API Autocomplete');

      $form['search_api_autocomplete'] = [
        '#type' => 'details',
        '#title' => $this->t('Search API views view to be used:'),
        '#tree' => TRUE,
        '#open' => TRUE,
        '#states' => [
          'visible' => [
            'select[name="settings[input_type]"]' => ['value' => 'search_api_autocomplete'],
          ],
        ],
      ];
      $form['search_api_autocomplete']['search_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('View ID'),
        '#default_value' => $this->configuration['search_api_autocomplete']['search_id'],
        '#states' => [
          'required' => [
            'select[name="settings[input_type]"]' => ['value' => 'search_api_autocomplete'],
          ],
        ],
      ];
      $form['search_api_autocomplete']['display'] = [
        '#type' => 'textfield',
        '#title' => $this->t('View display ID'),
        '#default_value' => $this->configuration['search_api_autocomplete']['display'],
        '#states' => [
          'required' => [
            'select[name="settings[input_type]"]' => ['value' => 'search_api_autocomplete'],
          ],
        ],
      ];
      $form['search_api_autocomplete']['filter'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Fulltext search filter machine name'),
        '#default_value' => $this->configuration['search_api_autocomplete']['filter'],
        '#states' => [
          'required' => [
            'select[name="settings[input_type]"]' => ['value' => 'search_api_autocomplete'],
          ],
        ],
      ];
      $form['search_api_autocomplete']['arguments'] = [
        '#type' => 'textfield',
        '#title' => $this->t('View arguments'),
        '#description' => $this->t('Comma separated values.'),
        '#default_value' => $this->configuration['search_api_autocomplete']['arguments'],
      ];
    }

    $form['input_label_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Label display mode'),
      '#options' => [
        'before' => $this->t('Before'),
        'after' => $this->t('After'),
        'invisible' => $this->t('Invisible'),
      ],
      '#default_value' => $this->configuration['input_label_display'],
    ];
    $form['input_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search label'),
      '#description' => $this->t('The label of a search input.'),
      '#default_value' => $this->configuration['input_label'],
    ];
    $form['input_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search placeholder'),
      '#description' => $this->t('The placeholder for a search input.'),
      '#default_value' => $this->configuration['input_placeholder'],
    ];
    $form['input_css_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search CSS classes'),
      '#description' => $this->t('Space separated list of CSS classes to add to a search input.'),
      '#default_value' => $this->configuration['input_css_classes'],
    ];
    $form['submit_display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display submit button'),
      '#default_value' => $this->configuration['submit_display'],
    ];
    $form['submit_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Submit label'),
      '#description' => $this->t('The label of a submit button.'),
      '#default_value' => $this->configuration['submit_label'],
    ];
    $form['input_keep_value'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Keep value in search input after form submit'),
      '#default_value' => $this->configuration['input_keep_value'],
    ];
    $form['preserve_url_query_parameters'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Preserve query parameters from URL'),
      '#description' => $this->t('A comma separated list of URL query parameters which should be preserved in the URL during the form submit'),
      '#default_value' => implode(', ', $this->configuration['preserve_url_query_parameters']),
    ];

    return $form;
  }

  /**
   * Tries to guess the action path of the search URL.
   *
   * @return string
   *   The search URL, or an empty string if it cannot be determined.
   */
  protected function guessActionPath(): string {
    $view = $this->findSearchView();

    if ($view?->display_handler instanceof DisplayRouterInterface) {
      return (string) $view->display_handler->getUrlInfo()->toString();
    }
    // Fall back to finding a static path (i.e., a node or Canvas page) called
    // `/search`, in the current language. This looks a little exotic, but using
    // the word 'Search', which is a word that appears in core already and
    // is therefore translated, should allow this to work more seamlessly.
    $path = '/' . mb_strtolower($this->t('Search'));
    if ($this->pathAliasManager->getPathByAlias($path) !== $path) {
      return $path;
    }
    // We couldn't find anything sensible.
    return '';
  }

  /**
   * Tries to guess the query string parameter for the search URL.
   *
   * @return string
   *   The name of the query string parameter to append to the search URL, if
   *   one can be determined. If not, empty string.
   */
  protected function guessGetParameter(): string {
    // @todo Use array_find() when PHP 8.4 is required.
    $filters = array_filter(
      $this->findSearchView()?->display_handler->getHandlers('filter') ?? [],
      fn (FilterPluginBase $filter): bool => $filter->getPluginId() === 'search_api_fulltext' && $filter->isExposed(),
    );
    $filter = reset($filters) ?: NULL;
    return $filter?->options['expose']['identifier'] ?? '';
  }

  /**
   * Tries to find a view that can be used for search.
   *
   * This will look for a view tagged with `simple_search_form`, and if found,
   * and will initialize it with its first available routable display (i.e., a
   * page) if possible.
   *
   * @return \Drupal\views\ViewExecutable|null
   *   The initialized view, or NULL if no appropriate view was found.
   */
  protected function findSearchView(): ?ViewExecutable {
    if ($this->moduleHandler->moduleExists('views')) {
      $views = $this->entityTypeManager->getStorage('view')->loadByProperties([
        'tag' => 'simple_search_form',
      ]);
      // @todo Use array_first() when PHP 8.5 is required.
      $view = reset($views) ?: NULL;
      $view = $view?->getExecutable();
      $view?->initDisplay();

      // If the view has a routable display (i.e., a page), point to it.
      foreach ($view?->displayHandlers ?? [] as $display_id => $handler) {
        if ($handler instanceof DisplayRouterInterface) {
          $view->setDisplay($display_id);
        }
      }
      return $view;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $first_char = mb_substr($form_state->getValue('action_path'), 0, 1);
    $allowed_first_chars = ['/', '?', '#'];

    if (!in_array($first_char, $allowed_first_chars)) {
      $form_state->setErrorByName(
        'action_path',
        $this->t('Path should start from "@chars".', ['@chars' => implode('", "', $allowed_first_chars)])
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['action_path'] = $form_state->getValue('action_path');
    $this->configuration['get_parameter'] = $form_state->getValue('get_parameter');
    $this->configuration['input_type'] = $form_state->getValue('input_type');
    $this->configuration['input_label'] = $form_state->getValue('input_label');
    $this->configuration['input_label_display'] = $form_state->getValue('input_label_display');
    $this->configuration['input_placeholder'] = $form_state->getValue('input_placeholder');
    $this->configuration['input_css_classes'] = $form_state->getValue('input_css_classes');
    $this->configuration['submit_display'] = $form_state->getValue('submit_display');
    $this->configuration['submit_label'] = $form_state->getValue('submit_label');
    $this->configuration['input_keep_value'] = $form_state->getValue('input_keep_value');
    $this->configuration['search_api_autocomplete'] = $this->moduleHandler->moduleExists('search_api_autocomplete')
      ? $form_state->getValue('search_api_autocomplete')
      : [];
    $query_parameters = $form_state->getValue('preserve_url_query_parameters');
    $query_parameters = array_filter(array_map('trim', explode(',', $query_parameters)), function ($value) {
      return $value !== '';
    });
    $this->configuration['preserve_url_query_parameters'] = $query_parameters;
  }

}
