Share button
============

Share button component allows displaying social share buttons with several options and styles.

Component implemented as a Twig template for including.

Accepted variables
------------------

- `data`:
    - `label`: Button text.
    - `url`: URL of page for sharing.
    - `title`: Title of page for sharing.
    - `icon`: icon for showing beside the button text: `TRUE` (default), `FALSE` or [icon component](icon.md).
- `settings`:
    - `network`: `'facebook'`, `'twitter'`, `'linkedin'`.
    - `attributes`: (object) element attributes.
    - `classes`: (array) classes for adding to the element.
    - `id`: HTML `id` attribute.
    - `disabled`: (bool) makes button looks and behave as inactive.
    - `icon_trailing`: (bool) allows to display the icon at the right of button text. By default icon displayed at the left.

Component options
-----------------

* No title
* No icon
* Leading icon
* Trailing icon
* Custom icon
* Network brand color for a button background

Component classes:

* `share-button--branded` - set network brand color for a button background

Alternatively, the Share button could be used with MDC button styles with all of its options.

Examples of usage
-----------------

### Default button

In the Twig template file:

~~~
{% include "@material_base/components/02_molecules/share-button.twig" with {
  data: {
    label: 'Share',
    url: 'https://example.com',
    title: 'Example site',
  },
  settings: {
    network: 'facebook',
  }
} %}
~~~

Output: Default button.

### Button with trailing icon

In the Twig template file:

~~~
{% include "@material_base/components/02_molecules/share-button.twig" with {
  data: {
    label: 'Share',
    url: 'https://example.com',
    title: 'Example site',
  },
  settings: {
    network: 'facebook',
    icon_trailing: TRUE,
  }
} %}
~~~

Output: Buttons with trailing icon.

### Button without text

In the Twig template file:

~~~
{% include "@material_base/components/02_molecules/share-button.twig" with {
  data: {
    url: 'https://example.com',
    title: 'Example site',
  },
  settings: {
    network: 'facebook',
  }
} %}
~~~

Output: Buttons without text.

### Button without icon

In the Twig template file:

~~~
{% include "@material_base/components/02_molecules/share-button.twig" with {
  data: {
    label: 'Facebook',
    url: 'https://example.com',
    title: 'Example site',
    icon: FALSE,
  },
  settings: {
    network: 'facebook',
  }
} %}
~~~

Output: Button without icon.

### Button with custom icon

In the Twig template file:

~~~
{% include "@material_base/components/02_molecules/share-button.twig" with {
  data: {
    label: 'Share',
    url: 'https://example.com',
    title: 'Example site',
    icon: {
      data: {
        value: 'thumb_up',
      },
      settings: {
        type: 'font',
        classes: ['material-icons'],
      },
    },
  },
  settings: {
    network: 'facebook',
  }
} %}
~~~

Output: Button with custom icon.

### Button with brand background color

In the Twig template file:

~~~
{% include "@material_base/components/02_molecules/share-button.twig" with {
  data: {
    label: 'Share',
    url: 'https://example.com',
    title: 'Example site',
  },
  settings: {
    network: 'facebook',
    classes: ['share-button--branded'],
  }
} %}
~~~

Output: Button with brand background color.

### Button using MDC Button style

In the Twig template file:

~~~
{% include "@material_base_mdc/components/02_molecules/share-button.twig" with {
  data: {
    label: 'Share',
    url: 'https://example.com',
    title: 'Example site',
  },
  settings: {
    network: 'facebook',
    classes: ['mdc-button--raised'],
  }
} %}
~~~

Output: Button using MDC Button style.
