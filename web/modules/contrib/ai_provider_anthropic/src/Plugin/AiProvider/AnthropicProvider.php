<?php

namespace Drupal\ai_provider_anthropic\Plugin\AiProvider;

use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai\Attribute\AiProvider;
use Drupal\ai\Base\OpenAiBasedProviderClientBase;
use Drupal\ai\Enum\AiModelCapability;
use Drupal\ai\Exception\AiQuotaException;
use Drupal\ai\Exception\AiSetupFailureException;
use Drupal\ai\Traits\OperationType\ChatTrait;

/**
 * Plugin implementation of the 'anthropic' provider.
 */
#[AiProvider(
  id: 'anthropic',
  label: new TranslatableMarkup('Anthropic'),
)]
class AnthropicProvider extends OpenAiBasedProviderClientBase {

  use ChatTrait;

  /**
   * {@inheritdoc}
   */
  protected string $endpoint = 'https://api.anthropic.com/v1';

  /**
   * Run moderation call, before a normal call.
   *
   * @var bool
   */
  protected bool $moderation = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getConfiguredModels(?string $operation_type = NULL, array $capabilities = []): array {
    // Check if dynamic fetching is enabled (default: true for seamless
    // upgrade).
    $dynamic_enabled = $this->getConfig()->get('dynamic_models_enabled') ?? TRUE;
    if ($dynamic_enabled) {
      // Try to get dynamic models first.
      $dynamic_models = $this->fetchAvailableModels();

      $models = [];
      if (!empty($dynamic_models)) {
        // If we got models from the API, use them exclusively.
        // This prevents duplicates from hardcoded models.
        $models = $dynamic_models;
      }

      // Also use hardcoded models for backward compatibility.
      $models = array_merge($this->getHardcodedModels($operation_type, $capabilities), $models);
    }
    else {
      // Use only hardcoded models if dynamic fetching is disabled.
      $models = $this->getHardcodedModels($operation_type, $capabilities);
    }

    // Apply capability filtering.
    if (in_array(AiModelCapability::ChatJsonOutput, $capabilities)) {
      return array_filter($models, function ($id) {
        // Keep models that support JSON output.
        // Updated to handle various model ID formats.
        return preg_match('/claude-3\.[57]|claude-3-[57]|claude-4|claude-(opus|sonnet)-4/i', $id);
      }, ARRAY_FILTER_USE_KEY);
    }

    if ($operation_type == 'chat') {
      return $models;
    }

    return $models;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedOperationTypes(): array {
    return [
      'chat',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSetupData(): array {
    $models = $this->getConfiguredModels();
    // Get the 4.1 models for complex tasks from the list.
    $default_complex_model = 'claude-opus-4-1-latest';
    foreach ($models as $model_id => $model_name) {
      if (str_starts_with($model_id, 'claude-opus-4-1')) {
        // We found a 4.1 model, we can use it.
        $default_complex_model = $model_id;
        break;
      }
    }
    // Get the 4.0 sonnet model for general tasks from the list.
    $default_chat_model = 'claude-sonnet-4-latest';
    foreach ($models as $model_id => $model_name) {
      if (str_starts_with($model_id, 'claude-sonnet-4')) {
        // We found a 4.0 sonnet model, we can use it.
        $default_chat_model = $model_id;
        break;
      }
    }

    return [
      'key_config_name' => 'api_key',
      'default_models' => [
        'chat' => $default_chat_model,
        'chat_with_image_vision' => $default_chat_model,
        'chat_with_complex_json' => $default_complex_model,
        'chat_with_tools' => $default_complex_model,
        'chat_with_structured_response' => $default_complex_model,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getModelSettings(string $model_id, array $generalConfig = []): array {
    return $generalConfig;
  }

  /**
   * Enables moderation response, for all next coming responses.
   */
  public function enableModeration(): void {
    $this->moderation = TRUE;
  }

  /**
   * Disables moderation response, for all next coming responses.
   */
  public function disableModeration(): void {
    $this->moderation = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function loadClient(): void {
    // Set custom endpoint from host config if available.
    if (!empty($this->getConfig()->get('host'))) {
      $this->setEndpoint($this->getConfig()->get('host'));
    }

    try {
      parent::loadClient();
    }
    catch (AiSetupFailureException $e) {
      throw new AiSetupFailureException('Failed to initialize Anthropic client: ' . $e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Returns hardcoded models for backward compatibility.
   *
   * @param string|null $operation_type
   *   The operation type.
   * @param array $capabilities
   *   Required capabilities.
   *
   * @return array
   *   Array of hardcoded models.
   */
  protected function getHardcodedModels(?string $operation_type = NULL, array $capabilities = []): array {
    // These are the existing hardcoded models.
    $models = [
      'claude-opus-4-1-latest' => 'Claude Opus 4.1 (Latest)',
      'claude-sonnet-4-latest' => 'Claude Sonnet 4 (Latest)',
      'claude-opus-4-latest' => 'Claude Opus 4 (Latest)',
      'claude-3-7-sonnet-latest' => 'Claude 3.7 Sonnet (Latest)',
      'claude-3-5-sonnet-latest' => 'Claude 3.5 Sonnet (Latest)',
      'claude-3-5-haiku-latest' => 'Claude 3.5 Haiku (Latest)',
      'claude-3-opus-latest' => 'Claude 3 Opus (Latest)',
      'claude-3-sonnet-latest' => 'Claude 3 Sonnet (Latest)',
      'claude-3-haiku-latest' => 'Claude 3 Haiku (Latest)',
    ];

    // Apply the same filtering logic as before.
    if (in_array(AiModelCapability::ChatJsonOutput, $capabilities)) {
      unset($models['claude-opus-4-latest']);
      unset($models['claude-3-opus-latest']);
      unset($models['claude-3-haiku-latest']);
    }

    return $models;
  }

  /**
   * Fetches available models from Anthropic API.
   *
   * @return array
   *   Array of models keyed by model ID with display names as values.
   */
  protected function fetchAvailableModels(): array {
    // Check cache first.
    $cache_key = 'ai_provider_anthropic:models';
    $cached = $this->cacheBackend->get($cache_key);
    if ($cached && !empty($cached->data)) {
      return $cached->data;
    }

    try {
      // Ensure we have an API key.
      $api_key = $this->apiKey ?: $this->loadApiKey();

      // Make direct HTTP request to models endpoint.
      // Note: The models endpoint requires version 2023-06-01 specifically.
      $response = $this->httpClient->request('GET', 'https://api.anthropic.com/v1/models', [
        'headers' => [
          'x-api-key' => $api_key,
          // Models endpoint requires this specific version.
          'anthropic-version' => '2023-06-01',
          'Content-Type' => 'application/json',
        ],
        'timeout' => 30,
      ]);

      $body = $response->getBody()->getContents();
      $data = Json::decode($body);

      $models = [];
      if (!empty($data['data']) && is_array($data['data'])) {
        foreach ($data['data'] as $model) {
          if (!empty($model['id']) && !empty($model['display_name'])) {
            $models[$model['id']] = $model['display_name'];
          }
        }

        // Handle pagination if needed.
        if (!empty($data['has_more']) && !empty($data['last_id'])) {
          // For now, we'll limit to first page to avoid too many requests.
          // This could be expanded in the future.
          $this->loggerFactory->get('ai_provider_anthropic')
            ->notice('Additional models available via pagination, showing first page only.');
        }
      }

      // Cache for 24 hours (configurable via settings).
      $cache_ttl = $this->getConfig()->get('models_cache_ttl') ?? 86400;
      $this->cacheBackend->set($cache_key, $models, time() + $cache_ttl);

      // Log successful fetch.
      $this->loggerFactory->get('ai_provider_anthropic')
        ->info('Successfully fetched @count models from Anthropic API', ['@count' => count($models)]);

      // Log the model IDs for debugging.
      if (count($models) > 0) {
        $this->loggerFactory->get('ai_provider_anthropic')
          ->debug('Fetched models: @models', ['@models' => implode(', ', array_keys($models))]);
      }

      return $models;
    }
    catch (\Exception $e) {
      // Log error but don't throw - gracefully fall back.
      $this->loggerFactory->get('ai_provider_anthropic')
        ->warning('Failed to fetch Anthropic models dynamically: @error', ['@error' => $e->getMessage()]);

      // Return empty array - hardcoded models will still be available.
      return [];
    }
  }

  /**
   * Clears the cached models list.
   *
   * This can be called from an admin form or drush command.
   */
  public function clearModelsCache(): void {
    $this->cacheBackend->delete('ai_provider_anthropic:models');
    $this->loggerFactory->get('ai_provider_anthropic')
      ->info('Anthropic models cache cleared.');
  }

  /**
   * Handle API exceptions consistently.
   *
   * @param \Exception $e
   *   The exception to handle.
   *
   * @throws \Drupal\ai\Exception\AiRateLimitException
   * @throws \Drupal\ai\Exception\AiQuotaException
   * @throws \Exception
   */
  protected function handleApiException(\Exception $e): void {
    if (strpos($e->getMessage(), 'Your credit balance is too low to access the Anthropic API') !== FALSE) {
      throw new AiQuotaException($e->getMessage());
    }
    throw $e;
  }

}
