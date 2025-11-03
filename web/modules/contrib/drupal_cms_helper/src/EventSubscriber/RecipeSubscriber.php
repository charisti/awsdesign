<?php

declare(strict_types=1);

namespace Drupal\drupal_cms_helper\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Recipe\RecipeAppliedEvent;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class RecipeSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly AliasManagerInterface $aliasManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      RecipeAppliedEvent::class => 'onApply',
    ];
  }

  public function onApply(RecipeAppliedEvent $event): void {
    $recipe = $event->recipe;
    $extra = $recipe->getExtra('drupal_cms');

    if ($recipe->type === 'Site' && isset($extra['front_page'])) {
      $path = $this->aliasManager->getPathByAlias($extra['front_page']);

      $this->configFactory->getEditable('system.site')
        ->set('page.front', $path)
        ->save();
    }
  }

}
