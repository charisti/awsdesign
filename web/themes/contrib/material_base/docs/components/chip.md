Chip
====

Material Base includes custom implementation of Material Design chips for using with Drupal checkbox form item.

MDC library chip component hasn't been implemented yet.

Component implemented as an alternative template for a checkbox form element which needs to be used instead of the default one, for example, by extending Drupal template suggestions.

Accepted variables
------------------

Template accept all default `form-element` template variables and additionally these:

- `outlined`: (bool) makes chip looks outlined.
- `icon`: ([icon component](icon.md)) icon for showing beside the chip text.

Component options
-----------------

Supported options:

* Outlined
* Chip icon

These options cannot be configured via the Drupal interface.

Applying options could be achieved by passing corresponding values to `form-element--checkbox--chip.html.twig` template, by preprocess function, or overriding/including the template in your custom theme.

Examples of usage
-----------------

### Default chip

In the checkbox form the element Twig template:

~~~
{% include "form-element--checkbox--chip.html.twig" } %}
~~~

Output: Default chip

### Outlined chip with icon

In checkbox form element Twig template:

~~~
{% include "form-element--checkbox--chip.html.twig" with {
  outlined: true,
  icon: {
    data: {
      value: 'filter_list',
    },
    settings: {
      type: 'font',
      classes: ['material-icons'],
    },
  }
} %}
~~~

Output: Outlined chip with icon
