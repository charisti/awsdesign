Navbar
======

Material Base includes a versatile navigation bar with several options.

Component implemented around Drupal "Navbar" region and intended to show its content.

Component options
-----------------

Navbar component implements the Top App Bar component from the MDC library and supports all its features.

Layout options:

* Standard
* Dense
* Prominent

Also navbar could be "sticky" or scrollable with the page.

Layout options could be set on the Appearance settings page (`/admin/appearance/settings`).

Additional MB styling classes:

* `navbar--light` - light navbar background
* `navbar--dark` - dark navbar background
* `navbar--secondary` - secondary color navbar background

Default navbar background is primary.

Classes could be added on template level by overriding/including `region--navbar.html.twig` template in your custom theme.

Alternatively, it is also possible to have navbar without MDC Top App Bar styles.

Navbar items
------------

Supported navbar items:

* Branding (Drupal site branding block)
* Menu, including 2 level submenus (Drupal menu block)
* Icon button (as [Icon button](icon-button.md) or [Button](button.md) components)
* Button (as [Button](button.md) component)

The main idea behind navbar items accessibility is to make inner spacing for clickable items without background or border (Logo, Icon button, Menu item), and outer spacing for items with their own inner spacing or non-clickable (Button with a background, Menu, Slogan).

Default navbar items already follow this rule. For achieving outer spacing for custom items (for example, buttons), `navbar-item` class could be used.

Classes for handling navbar items visibility and alignment:

* `navbar-item--center` - makes item be center aligned
* `navbar-item--right` - makes item be right aligned. The Class should be added only to the first item of the right-aligned group. If the first item is different per breakpoint, the class should be added to all such items.
* `visible-BREAKPOINT` - show item starting from `BREAKPOINT` (`sm`, `md`, `lg`)
* `visible-BREAKPOINT-inline` - show item as inline
* `visible-BREAKPOINT-flex` - show item as flex
* `hidden-BREAKPOINT` - hide item starting from `BREAKPOINT` (`sm`, `md`, `lg`)

Default navbar item alignment is left and visibility is visible.

For items presented by Drupal blocks, adding classes possible with [Block class](https://www.drupal.org/project/block_class) module or similar.

Custom items and their classes could be added on template level by overriding/including `region--navbar.html.twig` template in your custom theme.

Examples of usage
-----------------

### Navbar without MDC Top App Bar styles.

For creating custom navbars could be useful not to have MDC Top App Bar styles loaded.

Copy `region--navbar.html.twig` and `page.html.twig` from `templates/layout` folder (not from `material_base_mdc/templates/layout` folder) and paste to `templates/layout` folder of your custom theme.

Output: Default navbar implementation will be used in your theme.

### Branding (Logo, Site name, Slogan)

Place Site branding block instance into the Navbar region. Configure the block for displaying the necessary elements.

### Default menu (MDC Menu)

Default menu support second menu level displayed as dropdown menu by click (MDC Menu component).

Place a menu block instance into the Navbar region. Configure the block for displaying necessary menu deepness.

### Menu (MB Dropdown)

This implementation supports the second menu level displayed as a dropdown menu by click (MB Dropdown component).

Copy `menu--navbar.html.twig` from `templates/navigation` folder (not from `material_base_mdc/templates/navigation` folder) and paste to `templates/navigation` folder of your custom theme.

Place a menu block instance into the Navbar region. Configure the block for displaying necessary menu deepness.

### Menu (hoverable)

This implementation supports the second menu level displayed as a dropdown menu by hover.

Copy `menu--navbar--hoverable.html.twig` from `templates/navigation` folder and paste as `menu--navbar.html.twig` to `templates/navigation` folder of your custom theme.

Place a menu block instance into the Navbar region. Configure the block for displaying necessary menu deepness.

### Overlay menu open button (Icon button)

In the Twig template file:

~~~
{% include "@material_base_mdc/components/02_molecules/icon-button.twig" with {
  data: {
    icon: {
      data: {
        value: 'menu',
      },
      settings: {
        type: 'svg-sprite',
      },
    },
  },
  settings: {
    classes: ['mdc-top-app-bar__navigation-icon', 'overlay-open__button', 'hidden-lg'],
  },
} %}
~~~

Output: Icon button which opens the overlay (hidden for mobiles).

### Icon button

In the Twig template file:

~~~
{% include "@material_base_mdc/components/02_molecules/icon-button.twig" with {
  data: {
    url: '/user',
    icon: {
      data: {
        value: 'account_circle',
      },
      settings: {
        type: 'svg-sprite',
      },
    },
  },
  settings: {
    classes: ['mdc-top-app-bar__action-item', 'navbar-item--right'],
    tag: 'a',
  },
} %}
~~~

Output: Icon button which works as a link to the user page, aligned to right.

### Button

In the Twig template file:

~~~
{% include "@material_base_mdc/components/02_molecules/button.twig" with {
  data: {
    label: 'contact',
    url: '/contact'
  },
  settings: {
    classes: ['mdc-button--unelevated', 'button--light', 'navbar-item'],
    tag: 'a',
  },
} %}
~~~

Output: Just a button.

### Overlay menu open button without MDC styles

In the Twig template file:

~~~
{% include "@material_base/components/02_molecules/button.twig" with {
  data: {
    icon: {
      data: {
        value: 'menu',
      },
      settings: {
        type: 'svg-sprite',
      },
    },
  },
  settings: {
    classes: ['navbar-icon-button', 'overlay-open__button', 'hidden-lg']
  },
} %}
~~~

Output: Icon button without MDC styles which open overlay (hidden for mobiles).

### Icon button without MDC styles

In the Twig template file:

~~~
{% include "@material_base/components/02_molecules/button.twig" with {
  data: {
    url: '/user',
    icon: {
      data: {
        value: 'account_circle',
      },
      settings: {
        type: 'svg-sprite',
      },
    },
  },
  settings: {
    classes: ['navbar-icon-button', 'navbar-item--right'],
    tag: 'a',
  },
} %}
~~~

Output: Icon button without MDC styles which works as a link to the user page, aligned to right.

### Button without MDC styles

In the Twig template file:

~~~
{% include "@material_base/components/02_molecules/button.twig" with {
  data: {
    label: 'contact',
    url: '/contact'
  },
  settings: {
    classes: ['navbar-button'],
    tag: 'a',
  },
} %}
~~~

Output: Button without MDC styles.
