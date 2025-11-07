const path = require('path');
const autoprefixer = require('autoprefixer');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
const SpriteLoaderPlugin = require('svg-sprite-loader/plugin');
const ImageMinimizerPlugin = require('image-minimizer-webpack-plugin');

function materialImporter() {
  return {
    findFileUrl(url) {
      if (url.startsWith('@material')) {
        try {
          const resolved = require.resolve(url);
          return new URL(`file://${resolved}`);
        } catch (e) {
          return null;
        }
      }
      return null;
    },
  };
}

// Determine webpack build mode.
const mode = process.env.NODE_ENV || 'development';

module.exports = [
  {
    mode,
    entry: {
      theme: ['./js/theme.js', './scss/theme.scss'],
      mdc: ['./js/mdc.js', './scss/mdc.scss'],
      // Imports all images and icons for handling by Webpack.
      assets: './js/assets.js',
      // CSS-only entries (without JS bundle).
      fonts: './scss/fonts.scss',
    },
    output: {
      path: path.resolve(__dirname, 'dist'),
      filename: 'js/[name].js',
      sourceMapFilename: '[file].map',
      clean: true,
    },
    module: {
      rules: [
        {
          test: /\.scss$/,
          use: [
            {
              loader: MiniCssExtractPlugin.loader,
            },
            {
              loader: 'css-loader',
              options: {
                sourceMap: true,
              },
            },
            {
              loader: 'postcss-loader',
              options: {
                postcssOptions: {
                  plugins: () => [autoprefixer()],
                  sourceMap: true,
                },
              },
            },
            {
              loader: 'sass-loader',
              options: {
                // Prefer Dart Sass.
                implementation: require('sass'),
                sassOptions: {
                  includePaths: ['./node_modules'],
                  importers: [materialImporter()],
                  // Disable warnings from dependencies.
                  quietDeps: true,
                  // Disabling import warnings, since we can't use
                  // Sass modules until the base theme fully migrated to it.
                  silenceDeprecations: ['import'],
                },
                sourceMap: true,
              },
            },
          ],
        },
        {
          test: /\.js$/,
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env'],
          },
        },
        {
          test: /\.(png|jpg|jpeg|webp|avif|svg)$/,
          type: 'asset/resource',
          exclude: /icons\/.*\.svg$/,
          generator: {
            filename: './[path][name][ext]',
          },
        },
        {
          test: /icons\/.*\.svg$/,
          loader: 'svg-sprite-loader',
          options: {
            extract: true,
            spriteFilename: './images/icons.svg',
            runtimeCompat: true,
          },
        },
      ],
    },
    devtool: mode === 'development' ? 'source-map' : undefined,
    optimization: {
      minimizer: [
        '...', // Extend existing minimizers (TerserPlugin, etc.)
        new ImageMinimizerPlugin({
          test: /\.(jpe?g|png|webp|avif)$/i,
          minimizer: {
            implementation: ImageMinimizerPlugin.sharpMinify,
            options: {
              encodeOptions: {
                jpeg: {
                  quality: 90,
                  progressive: true,
                },
                png: {
                  quality: 85,
                  progressive: true,
                },
                webp: {
                  quality: 90,
                },
                avif: {
                  quality: 85,
                },
              },
            },
          },
        }),
        new ImageMinimizerPlugin({
          test: /\.svg$/i,
          // Excluding both source files and generated sprite.
          exclude: [/icons\/.*\.svg$/, /icons\.svg$/],
          minimizer: {
            implementation: ImageMinimizerPlugin.svgoMinify,
            options: {
              encodeOptions: {
                plugins: [
                  {
                    name: 'preset-default',
                  },
                ],
              },
            },
          },
        }),
      ],
    },
    plugins: [
      new MiniCssExtractPlugin({
        filename: 'css/[name].css',
        chunkFilename: 'css/[id].css',
      }),
      // Removes JS bundle for CSS-only entries.
      new RemoveEmptyScriptsPlugin(),
      new SpriteLoaderPlugin({
        plainSprite: true,
      }),
      // Custom plugin to remove assets.js bundle.
      {
        apply(compiler) {
          compiler.hooks.compilation.tap('RemoveAssetsBundle', compilation => {
            compilation.hooks.processAssets.tap(
              {
                name: 'RemoveAssetsBundle',
                stage: compilation.PROCESS_ASSETS_STAGE_OPTIMIZE_INLINE,
              },
              assets => {
                delete assets['js/assets.js'];
                delete assets['js/assets.js.map'];
              },
            );
          });
        },
      },
    ],
    stats: {
      children: false,
    },
  },
];
