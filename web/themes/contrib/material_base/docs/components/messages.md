Messages
========

Material Base includes styling for Drupal status messages.

Component applied automatically.

Component options
-----------------

Layout options:

* Messages in overlay - display messages in overlay in the bottom left corner and auto hide them in several seconds.

"Messages in overlay" option requires the Status messages block to be placed in the "Messages" region.

"Messages in overlay" option implements the Snackbar component from the MDC library and supports all its features.

Alternatively, it is also possible to have "Messages in overlay" without MDC Snackbar styles and behavior.

Layout options could be set on the Appearance settings page (`/admin/appearance/settings`).

Examples of usage
-----------------

### "Messages in overlay" without MDC Snackbar styles.

For creating custom overlay messages, it could be useful not to have MDC Snackbar styles loaded.

Copy `status-messages.html.twig` from `templates/misc` folder and paste as `status-messages--fixed.html.twig` to `templates/misc` folder of your custom theme.

Output: Default "Messages in overlay" implementation will be used in your theme.
