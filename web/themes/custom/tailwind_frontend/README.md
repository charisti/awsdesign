# Tailwind Frontend Theme

This custom Drupal theme bundles Tailwind CSS for rapid UI prototyping.

## Getting Started

From `web/themes/custom/tailwind_frontend` run:

```sh
npm install
npm run build   # or: npm run watch
```

The compiled CSS is saved to `css/dist/style.css` and is loaded through the `global-styling` library.

## Drupal Setup

1. Place the compiled CSS by running the build step above.
2. In Drupal go to **Appearance** and install `Tailwind Frontend`.
3. Set it as the default theme.
4. Clear caches (`drush cr`) after enabling the theme.

Blocks can be assigned to the following regions: `header`, `primary_menu`, `secondary_menu`, `hero`, `content`, `sidebar`, and `footer`.
