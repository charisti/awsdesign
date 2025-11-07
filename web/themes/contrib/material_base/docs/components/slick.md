Slick
=====

Material Base provides basic styles and adjusted template for the Slick Carousel library and Drupal module.

Component implemented as CSS styles which are automatically applied to markup provided by module.

Component options
-----------------

Additional MB classes:

* `slick--equal-heght` - makes slides the same height

Extended classes should be added to the root element with `slick` class by overriding template in your custom theme or extending optionset styles with Sass extends function.

Examples of usage
-----------------

In the Sass file:

~~~
.slick--optionset--SETTINGSNAME {
  @extends .slick--equal-heght;
}
~~~
