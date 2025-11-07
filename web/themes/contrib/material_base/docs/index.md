Material Base
=============

Introduction
------------

Material Base — is a base theme for Drupal.
It implements the [Material Design](https://material.io/) concept from Google.

The theme was made as basic styles and a set of components, which can be easily combined for making beautiful Drupal themes instead of building them from scratch. It utilizes [Material Components for web](https://m2.material.io/develop/web) library by Google.

Material Base 2 has been completely rewritten since version 1.x, and there is no upgrade path from version 1 to version 2.

Material Base 3.0 is a semantic version release of Material Base 2, no breaking compatibility changes have been made.

Material Base — is the theme for themers. It requires knowledge of Drupal theming system and CSS/SASS.

Happy theming!

Quick install
-------------

**Install theme**

The recommended way for getting the theme is installing it with Composer.

~~~
composer require 'drupal/material_base:^3.0'
~~~

For more information see this [page](https://www.drupal.org/docs/develop/using-composer/manage-dependencies).

**Enable theme**

It is not recommended to use Material Base theme as a site theme, the best practice is to create a subtheme and specify Material Base as a base theme. However, the base theme could be used for demonstration purposes.

Just enable it and make it default via Appearance settings or Drush, as usual.

Upgrade from 2.x to 3.x version
-------------------------------

In Material Base 2.0.0 we introduced a "base" theme for Material Base. In fact, it was just a copy of the "Stable 9" theme pasted inside the Material Base folder. It had been done for the sake of independency on Drupal Core updates. However, user experience showed it was the wrong decision.
The "Stable 9" theme still sits in the Core of new Drupal versions, our own copy of "Stable 9" requires maintenance and produces unwanted bugs related to template discovery. So the code of "Material Stable 9" has been removed from version 3. The update script will disable it and enable "Stable 9" instead. If it doesn't happen automatically, please do it manually.

JQuery `slideUp()`, `slideDown()`, and `slideToggle()` functions are replaced by custom implementation on vanilla JS. Attach `material-base/slide` library to use it in your custom theme JS. Example of usage:

**Before:**

```js
$(element).slideDown(fast);
```

**After:**

```js
slideDown(element, 200);
```

Upgrade from 2.x-beta to 2.0 version
------------------------------------

In 2.x beta versions, Material Base used "Stable" as a base theme, but in 2.0 it was changed to "Stable 9". The copy of "Stable 9" from Drupal Core 9.4.8 was added to Material Base as "Material Stable 9". Following the new approach of subtheming, Material Base is using its own copy of the base theme instead of referring to it.
Switching from "Stable" to "Stable 9" shouldn't make a big difference, but in case of regression issues, it will be the first place to check. Don't forget to run the update script after the update.

In 2.0 child themes were moved to the `themes` folder, to make the template discovery work correctly. If you created a subtheme based on the `material_base_subtheme` template, most likely you will need to update the path for `material_base_mdc/mdc` library files in `YOURTHEME.info.yml`, from `../dist/` to `../../dist/`.

**Before:**

```yaml
libraries-override:
  material_base_mdc/mdc:
    css:
      theme:
        ../dist/css/mdc.css: false
```

**After:**

```yaml
libraries-override:
  material_base_mdc/mdc:
    css:
      theme:
        ../../dist/css/mdc.css: false
```

In 2.0 the default template for the Textarea field was changed. Alternative version with no floating label used by default due to compatibility with CKEditor. Original MDC implementation still available, see `themes/material_base_mdc/templates/form/form-element--textarea.html.twig` for reverting options.


Full documentation
------------------

* **Getting started**
    * [Installing](install.md)
    * [Setup](setup.md)
    * [Building](build.md)
* **Theme architecture**
    * [Folder structure](folder-structure.md)
    * [MDC subtheme](mdc.md)
    * [Custom subtheme template](subtheme-template.md)
    * [Variables](variables.md)
* **Theme features**
    * [Using libraries](libraries.md)
    * [Grid](grid.md)
    * [Breakpoints](breakpoints.md)
    * [Mixins](mixins.md)
    * [Helpers and extends](helpers-extends.md)
    * [Icon fonts](icon-fonts.md)
    * [SVG icons sprite](svg-icons.md)
    * [Style guide](style-guide.md)
* **[Components](components.md)**
    * [Accordion](components/accordion.md)
    * [Button](components/button.md)
    * [Card](components/card.md)
    * [Chip](components/chip.md)
    * [Copy target](components/copy-target.md)
    * [Drawer](components/drawer.md)
    * [Dropdown](components/dropdown.md)
    * [Footer](components/footer.md)
    * [Form](components/form.md)
    * [Icon button](components/icon-button.md)
    * [Icon](components/icon.md)
    * [Menu dropdown](components/menu-dropdown.md)
    * [Messages](components/messages.md)
    * [Navbar](components/navbar.md)
    * [Overlay](components/overlay.md)
    * [Search](components/search.md)
    * [Share button](components/share-button.md)
    * [Slick](components/slick.md)
    * [Text list](components/text-list.md)
    * [Tooltip](components/tooltip.md)
