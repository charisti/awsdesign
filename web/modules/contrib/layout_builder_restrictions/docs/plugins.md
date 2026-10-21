---
hide:
  - toc
---

# Adding new restrictions via plugins

Developers can add their own plugins (via `@LayoutBuilderRestriction` annotations) for use cases not covered by the module.

A separate plugin could, for example:

*   Restrict block placement based on the selected layout section or region
*   Supplement the UI for restricting blocks based on, say, machine name regular expression matching

Developers looking to implement their own restriction should be able to start from the default `EntityViewModeRestriction` plugin and modify as needed.

New plugins are expected to implement the following methods:

*   `alterBlockDefinitions(array $definitions, array $context)`: given a list of available block definitions, and a context for where the block is being placed, return an array of allowed block definitions.
*   `alterSectionDefinitions(array $definitions, array $context)`: given a list of available layouts, and a context for where the layout is being placed, return an array of allowed layouts.
*   `blockAllowedinContext()`: given a variety of contexts (including where in the layout the block is coming from and where the block is moving to), return validation in the form of `TRUE`, or restriction message array.

Plugins are responsible for storing configuration associated with the plugin. The default plugin stores its configuration as a third-party-setting on Drupal's entity view mode configuration. Plugins whose restrictions aren't specific to entity view modes, for example, would use a different storage location.

Plugins are also expected to provide their own UI for settings restrictions. The default plugin uses a form alter to modify each entity view mode's settings form. New plugins could use a similar alter, or provide their own standalone configuration form.

Helper methods for plugins are provided by `PluginHelperTrait`:

*   `getBlockDefinitions(LayoutEntityDisplayInterface $display)`: Returns a list of all registered blocks by provider, as well as a list of custom block types, where $display specifies which entity type is requesting the available blocks
*   `getLayoutDefinitions()`: Returns a list of all registered layouts
