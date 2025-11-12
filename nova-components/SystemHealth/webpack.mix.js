const mix = require('laravel-mix')
const webpack = require('webpack')
const path = require('path')

mix
  .setPublicPath('dist')
  .js('resources/js/tool.js', 'js')
  .vue({ version: 3 })
  .css('resources/css/tool.css', 'css')
  .webpackConfig({
    externals: {
      vue: 'Vue',
    },
    resolve: {
      alias: {
        '@': path.join(__dirname, 'resources/js/'),
      },
      symlinks: false,
    },
    plugins: [
      new webpack.DefinePlugin({
        __VUE_OPTIONS_API__: JSON.stringify(true),
        __VUE_PROD_DEVTOOLS__: JSON.stringify(false),
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: JSON.stringify(false),
      }),
    ],
  })
  .version()
