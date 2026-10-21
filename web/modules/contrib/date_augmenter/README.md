# Date Augmenter API

The Date Augmenter API provides a plugin system that allows other modules to
extend or modify the output of date field formatters. Rather than choosing
between individual formatters that each only partially meet your needs, site
builders can assemble the exact date formatting capabilities they require by
enabling and combining multiple augmenter plugins.

The module integrates with supported date formatters (such as Smart Date)
by adding an augmenter configuration UI to their field formatter settings. Each
augmenter can be independently enabled, ordered by weight, and configured per
field formatter instance.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/date_augmenter).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/date_augmenter).

## Table of contents

- Requirements
- Recommended modules
- Installation
- Configuration
- Developing a custom augmenter plugin
- Maintainers

## Requirements

- Drupal 9, 10, or 11
- At least one date field formatter that supports the Date Augmenter API (i.e.
  implements a `supportsDateAugmenter()` method). The
  [Smart Date](https://www.drupal.org/project/smart_date) module is the
  primary supported formatter.

## Recommended modules

The following separate contrib modules provide augmenter plugins that work with
this API:

- [Add to Calendar Date Augmenter](https://www.drupal.org/project/date_augmenter_cal)
  — Adds "Add to Calendar" links to date output.
- [Date Content Augmenter](https://www.drupal.org/project/date_content_augmenter)
  — Appends related content to date output.
- [Link Augmenter](https://www.drupal.org/project/date_augmenter_link)
  — Wraps date output in configurable links.
- [AP Stylebook Date Augmenter](https://www.drupal.org/project/ap_style_date_augmenter)
  — Formats dates according to AP Stylebook guidelines.

## Installation

Install as you would normally install a contributed Drupal module. See
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules)
for further information.

```
composer require drupal/date_augmenter
```

Then enable the module:

```
drush en date_augmenter
```

## Configuration

This module does not provide a standalone configuration page. Augmenter
settings are configured per field formatter instance:

1. Navigate to the **Manage display** page for a content type or other entity
   that has a date field (e.g. _Structure > Content types > [Type] > Manage
   display_).
2. Locate a date field using a formatter that supports the Date Augmenter API
   (such as a Smart Date formatter) and click its settings gear icon.
3. In the formatter settings form, locate the **Date Augmenters** section.
4. Enable the desired augmenters using the provided checkboxes.
5. Adjust the order of augmenters using the drag-and-drop weight table.
6. Expand each enabled augmenter's settings tab to configure it.
7. Save the formatter settings and then save the display.

For formatters that distinguish between individual date instances and recurring
rule dates (such as Smart Date), separate augmenter configuration is available
for each case.

## Developing a custom augmenter plugin

To create a new augmenter plugin in your own module, implement
`\Drupal\date_augmenter\Plugin\DateAugmenterInterface` (or extend the provided
`DateAugmenterBase` or `DateAugmenterPluginBase` base classes) and place the
class in your module's `src/Plugin/DateAugmenter/` directory.

More detailed information is available in the [documentation](https://www.drupal.org/docs/contributed-modules/date-augmenter-api/adding-api-support-to-your-formatter).

**Using a PHP attribute (PHP 8.1+, recommended):**

```php
namespace Drupal\my_module\Plugin\DateAugmenter;

use Drupal\date_augmenter\Attribute\DateAugmenter;
use Drupal\date_augmenter\Plugin\DateAugmenterBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\StringTranslation\TranslatableMarkup;

#[DateAugmenter(
  id: 'my_augmenter',
  label: new TranslatableMarkup('My Augmenter'),
  description: new TranslatableMarkup('Does something useful to date output.'),
  weight: 0,
)]
class MyAugmenter extends DateAugmenterBase {

  public function augmentOutput(array &$output, DrupalDateTime $start, ?DrupalDateTime $end = NULL, array $options = []): void {
    // Modify $output (a render array) as needed.
  }

}
```

**Using an annotation (legacy, for Drupal 9 compatibility):**

```php
/**
 * @DateAugmenter(
 *   id = "my_augmenter",
 *   label = @Translation("My Augmenter"),
 *   description = @Translation("Does something useful to date output."),
 *   weight = 0,
 * )
 */
```

To make your augmenter configurable (providing a settings form), also implement
`\Drupal\date_augmenter\Plugin\ConfigurablePluginInterface` or extend
`\Drupal\date_augmenter\Plugin\DateAugmenter\DateAugmenterPluginBase`, and
implement `PluginFormInterface` from Drupal core.

For the augmenter to appear in a formatter's settings form, the formatter must
implement a `supportsDateAugmenter()` method. If it returns an array of set
names (e.g. `['instances', 'rule']`), separate augmenter configuration is
presented for each set.

## Maintainers

- Martin Anderson-Clutz - [mandclu](https://www.drupal.org/u/mandclu)
