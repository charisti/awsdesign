Libraries
=========

Material Base uses Drupal libraries features for organizing CSS and JS assets.

It allows excluding some parts of the base theme you don't need on your project.
It also allows overriding existing parts or adding additional parts to your subtheme when needed.

For more information about managing libraries, see this [page](https://www.drupal.org/docs/theming-drupal/adding-stylesheets-css-and-javascript-js-to-a-drupal-theme#override-extend).

Material Base libraries
-----------------------

All libraries from the base theme include it by default to their subthemes until you exclude them.

**`material_base/base`**

Contains basic CSS, JS, variables, mixins, extends, helpers and includes all components for building with default values.

This library isn't intended to be excluded because most other libraries depend on it.

CSS part of this library intended to be overridden by implementation in your custom subtheme created with your project values and adjustments.

More information about using [mixins](mixins.md), [variables](variables.md) and [components](components.md).

**`material_base/grid`**

Contains grid system definitions for [Flexbox Grid](http://flexboxgrid.com/) external library.

This library could be freely excluded or overridden by any other grid system.

More information about using Flexbox Grid in Material Base [here](grid.md).

**`material_base/fonts`**

Contains the default font definition. The default Material Design font family is Roboto, and it is included in MB.

This library is intended to be excluded or overridden according to fonts used on your project.

In case of this library was overridden, fonts preloading definitions in `THEMENAME.theme` should be also updated.

**`material_base/icons-font`**

Contains "Material Icons" iconic fonts definition including themes like Outlined, Rounded, Sharp, Two-Tone and Filed (default).

This library could be excluded if you are not going to use iconic fonts. It also could be overridden or extended by other iconic fonts.

In case of this library was overridden, fonts preloading definitions in `THEMENAME.theme` should be also updated.

More information about using Material Icons fonts in Material Base [here](icon-fonts.md).

Using an iconic font is probably the easiest way of using predefined icon sets like Material Icons or Font Awesome but not so convenient for custom icons. For easy using of custom icons (but not excluding icons from icon libraries) there is also SVG icon sprite generation function included in the [building](build.md) process.

More information about using SVG icons sprite in Material Base [here](svg-icons.md).

Both icon usage options could be effectively used together.

**`material_base_mdc/mdc`** (from "Material Base MDC" subtheme)

Contains [Material Components for web](https://m2.material.io/develop/web) library definition.

This library was picked out to separate subtheme because it should be used together with Twig templates specific to the MDC library.

CSS part of this library intended to be overridden by implementation in your custom subtheme created with only MDC components directly used on your project and its variables values.

More information about MDC in Material Base [here](mdc.md).

**`THEMENAME/theme`** (library template from `themes/material_base_subtheme` folder)

Contains scaffolding for the main library of your custom theme.

This library (or its replacement) intended to be used as the scope of your custom CSS, JS and templates specific to your project.

