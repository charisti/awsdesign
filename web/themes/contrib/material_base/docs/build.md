Building a theme
================

Material Base and its subtheme template include a configured Webpack script for performing theme build tasks.
The base theme is already packed with built assets, but for your custom theme you most likely need to handle this process during theme development.

Installing dependencies
-----------------------

For installing build tools and its dependencies, you need to have NPM globally installed.

1. Create your custom theme from a subtheme template (see [Subtheme template](subtheme-template.md)).
2. Optional: adjust values in `webpack.config.js`.
3. From your custom theme folder run:

~~~
npm install
~~~

Building theme
--------------

Building script includes these tasks:

* Compiling Sass.
* Autoprefixing CSS.
* JS aggregation and minification.
* Creating map files for CSS and JS.
* Image assets optimizations for images from `images` folder.
* Generating SVG sprite for icons from `icons` folder.

All generated files will be put to `dist` folder.
Source files will not be changed.

Building theme assets could be performed by running a command from your custom theme folder:

~~~
npm run build
~~~

Watching files and rebuilding theme on changes
----------------------------------------------

Building theme assets continuously by watching files for changes could be performed by running command from your custom theme folder:

~~~
npm run develop
~~~

Checking code quality for SASS and JS files
-------------------------------------------

For checking code quality for JS files, you could run a command from your custom theme folder:

```
npm run eslint
```

To automatically fix JS code quality issues, you could run a command from your custom theme folder:

```
npm run eslint-fix
```

For checking code quality for SASS files, you could run a command from your custom theme folder:

```
npm run stylelint
```

To automatically fix SASS code quality issues, you could run a command from your custom theme folder:

```
npm run stylelint-fix
```

To apply automatic formating fixes for both JS and SASS files, you could run a command from your custom theme folder:

```
npm run format
```

Adding SASS and JS files to the build process
---------------------------------------------

The building process (including watching) happens independently for a different group of sources.
Usually a group corresponds a single library and includes CSS and/or JS assets.

For adding SASS and JS files to the build process, it needs to specify library name and path to index files in `entry` section of `module.exports` array in `webpack.config.js`:

~~~
'LIBRARY-NAME': ['./js/JS-INDEX.js', './scss/SCSS-INDEX.scss']
~~~

Usually for index files names used the same name as for the library.

By default, Webpack is always creating JS assets. Our Webpack config includes `RemoveEmptyScriptsPlugin` which removes extra assets automatically.

Built assets will be available in `dist` folder and could be used in [libraries](/libraries.md).
