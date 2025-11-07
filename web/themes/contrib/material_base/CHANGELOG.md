Material Base development changes
=================================

05.09.2025 - 3.1.0
------------------

* Updated the webpack config. Replaced image optimization handler for better compatibility.
* Updated NPM packages to the recent versions. Configures ESlink, Stylelint, Prettier. Dropped Yarn support in favor of NPM.
* Addressed Sass warnings: global functions, color functions.
* Updated documentation, added logo for the Project browser, updated screenshot, improved code quality.

02.05.2024 - 3.0.0
------------------

* Material Base 3.0 is compatible with Drupal 11.
* All JS has been rewritten on vanilla JS according to Drupal JS API, no JQuery dependency anymore.
* JQuery `slideUp()`, `slideDown()`, and `slideToggle()` functions are replaced by custom implementation on vanilla JS. Attach `material-base/slide` library to use it in your custom theme JS. Example of usage:

  **Before:**

  ```js
  $(element).slideDown(fast);
  ```

  **After:**

  ```js
  slideDown(element, 200);
  ```
* Updated NPM dependencies to recent versions.
* Improved Webpack configuration.
* Asset collecting script moved to a separate entry (`assets.js`), since it shouldn't be included in theme's bundle.

10.06.2023 - 3.0.0-beta1
------------------------

* Switched to a semantic versioning model.
* Changed the base theme to "Stable 9" from Drupal Core.
* Files of the "Material Stable 9" theme (which was a copy "Stable 9" from Drupal Core) had been removed. Theme definition is temporarily kept for a smooth update process.
* Updated button template (`input--submit.html.twig`) to use `<label>` tag instead of `<button>` as a wrapper for `<input>` an element. It's more semantic and accessible.
* Introduced new button template (`button.html.twig`) which utilize `<button>` element instead of `<input>`.
  It's not active by default until [#1671190](https://www.drupal.org/project/drupal/issues/1671190) will be fixed but if you want to have it now, you can override the "input" template in your custom theme, see `themes/material_base_mdc/templates/form/input--submit--button.html.twig`.
* Improved documentation.

14.11.2022 - 2.0
----------------

* Changed the template for textarea fields because of accessibility reasons.
  In case of any issues, see
  `themes/material_base_mdc/templates/form/form-element--textarea.html.twig`
  for reverting options.
* Moved subthemes to `themes` folder for proper Twig template discovery work.
* [#3316638](https://www.drupal.org/project/material_base/issues/3316638) Fixed password field issues.
* [#3316643](https://www.drupal.org/project/material_base/issues/3316643) Improved support of layout builder.
* [#3316641](https://www.drupal.org/project/material_base/issues/3316641) Changed the base theme from "Stable" to "Stable 9" (using own copy made from Drupal code 9.4.8).

06.08.2022 - 2.0-beta5
----------------------

* Updated MDC library from 7.0.0 to 14.0.0.
  Important change: MDC list implementation is in transition state to the new
  implementation and all usage of `mdc-list` (including `mdc-list-item` and
  `mdc-list-item-*`) should be replaced by `mdc-deprecated-list`.
* Updated Webpack from 4.x to 5.x. Update of `webpack.config.js`,
  `package.json`, `package-json.lock`, and `yarn.lock` is required.
* Fixed uploadable fields, file and image.
  The fix requires changes from [#2752443](https://www.drupal.org/project/drupal/issues/2752443), for more details see [#3170746](https://www.drupal.org/project/material_base/issues/3170746)
* Fixed bug with multiple form submit button
* Compatibility with Drupal 10

28.08.2020 - 2.0-beta1
----------------------

* First beta release.
