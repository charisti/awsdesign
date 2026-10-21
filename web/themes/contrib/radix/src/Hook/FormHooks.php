<?php

namespace Drupal\radix\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for radix.
 */
class FormHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_theme_suggestions_HOOK_alter().
   *
   * @phpstan-param list<string> $suggestions
   * @phpstan-param array<string, mixed> $variables
   */
  #[Hook('theme_suggestions_form_alter')]
  public static function themeSuggestionsFormAlter(array &$suggestions, array &$variables): void {
    $element = $variables['element'];
    if (isset($element['#form_id'])) {
      $suggestion = strtr($element['#form_id'], [
        '.' => '_',
        '-' => '_',
      ]);
      $suggestions[] = $variables['theme_hook_original'] . '__' . $suggestion;
    }
    if (isset($element['#id'])) {
      $suggestion = strtr($element['#id'], [
        '.' => '_',
        '-' => '_',
      ]);
      $suggestions[] = $variables['theme_hook_original'] . '__' . $suggestion;
    }
    // Check to see if the form is layout builder form.
    if (isset($element['#layout_builder_element_keys'])) {
      $suggestions[] = $variables['theme_hook_original'] . '__layout__builder';
    }
  }

  /**
   * Implements hook_preprocess_input().
   *
   * @phpstan-param array<string, mixed> $variables
   */
  #[Hook('preprocess_input')]
  public static function preprocessInput(array &$variables): void {
    $element = $variables['element'];
    // Add the element type to the theme.
    $variables['type'] = $element['#type'];
    // Ensure there is no collision with Bootstrap 5 default class names by
    // replacing ".form-text" with ".form-textfield".
    $attributes =& $variables['attributes'];
    if ($element['#type'] === 'textfield' && !empty($attributes['class']) || $element['#type'] === 'password' && !empty($attributes['class'])) {
      $classIndex = array_search('form-text', $attributes['class']);
      $attributes['class'][$classIndex] = 'form-textfield';
    }
  }

  /**
   * Implements hook_preprocess_form_element().
   *
   * @phpstan-param array<string, mixed> $variables
   */
  #[Hook('preprocess_form_element')]
  public static function preprocessFormElement(array &$variables): void {
    $element = $variables['element'];
    // Add required class for checkbox and radio labels.
    if (in_array($element['#type'], [
      'checkbox',
      'radio',
    ])) {
      $variables['label']['#attributes']['class'][] = 'form-check-label';
    }
    else {
      $variables['label']['#attributes']['class'][] = 'form-label';
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * @phpstan-param array<string, mixed> $form
   */
  #[Hook('form_search_block_form_alter')]
  public function formSearchBlockFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    // Update the placeholder and size of field.
    $form['keys']['#title'] = '';
    $form['keys']['#size'] = 20;
    $form['keys']['#placeholder'] = $this->t('Search');
  }

  /**
   * Implements hook_form_alter().
   *
   * @phpstan-param array<string, mixed> $form
   */
  #[Hook('form_alter')]
  public static function formAlter(array &$form, FormStateInterface $form_state): void {
    if (isset($form['actions']['submit']) && count($form['actions']) <= 2) {
      $form['actions']['submit']['#attributes']['class'][] = 'btn';
    }
    if (isset($form['#attributes']['class'])) {
      $form['#attributes']['class'][] = 'needs-validation';
    }
  }

}
