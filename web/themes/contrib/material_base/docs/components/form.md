Form
====

Material Base provides default styles and additional options for Drupal form elements.

For displaying and handling forms, Drupal modules or Drupal Form API could be used.
Form elements are not intended to be used directly (via template including).

Component options
-----------------

Some of the form elements implement components from the MDC library and support most of their features.

Implemented MDC components:

* Text field (including textarea, helper text, and floating label)
* Checkbox
* Radio button
* Submit button

The rest of form elements uses default appearance provided by Drupal.

Some options provided by the MDC library cannot be configured via Drupal interface.

Outlined MDC option for text field could be achieved by passing corresponding value to `form-element--textfield.html.twig` template, by preprocess function, or overriding/including the template in your custom theme.

Character counter, prefix and suffix text, leading and trailing icons weren't implemented, however, they could be easily added on template level.

Full width option for text fields and textarea was removed from MDC. But it is still supported by custom implementation.

### Floating label for textarea

MDC doesn't provide a "static label" option for the textarea, only floating label. But it isn't working well with Drupal in some cases, for example, when CKEditor is applied.
Material Base provides an alternative implementation which is enabled by default due to accessibility reasons. For pure MDC experience, replace alternative template by original one in `form-element--textarea.html.twig`.

### Button elemennt instead of input

Historically, Drupal uses `<input type="submit">` element for buttons. The modern way is to use the `<button>` element because it allows putting nested markup and using advanced styling. For compatibility with contrib modules, Material Base comes with `<input>` element by default.
But if you want to have `<button>` element for buttons, it can be achieved by copying `material_base/themes/material_base_mdc/templates/form/input--submit--button.html.twig` and pasting it as `input--submit.html.twig` to your custom theme's `templates/form` folder.

### Clear input button

Clear input button could be added to text fields, such as the search field.

In form element Twig template file:

~~~
{# Place this code right after {{ children }} or 'input' tag #}
<span class="input-clear">
  <svg class="icon input-clear__icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
    <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/>
  </svg>
</span>
~~~

Output: Clear input button will be added to the text field.
