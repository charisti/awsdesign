Drawer
======

Material Base includes a drawer for holding the mobile menu and other stuff.

Component implemented around Drupal "Drawer" region and intended to show its content.

Overlay items
-------------

Supported drawer items:

* Drawer open button (as navbar item)
* Branding (Drupal site branding block)
* Menu, including 2nd level submenus (Drupal menu block)

Custom items could be added on template level by overriding/including `region--drawer.html.twig` template in your custom theme.

Examples of usage
-----------------

### Branding

Place Site branding block instance into a drawer region. Configure a block for displaying the necessary elements.

### Default menu (MDC list)

The default menu supports the second menu level displayed as accordion by click. Menu items styled with the MDC List component.

Place a menu block instance into the Drawer region. Configure the block for displaying the necessary menu deepness.

### Menu without MDC List styles

This implementation supports the second menu level displayed as accordion by click. Menu items styled without the MDC List component.

Copy `menu--drawer.html.twig` from `templates/navigation` folder (not from `material_base_mdc/templates/navigation` folder) and paste to `templates/navigation` folder of your custom theme.

Place a menu block instance into the Drawer region. Configure the block for displaying necessary menu deepness.
