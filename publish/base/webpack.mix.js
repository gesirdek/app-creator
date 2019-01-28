let mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
    .sass('resources/assets/sass/app.scss', 'public/css')
    .styles([
        'node_modules/vuetify/dist/vuetify.css'
    ], 'public/css/vuetify.css')
    .copy('node_modules/material-icons/css/material-icons.min.css', 'public/css/material-icons.min.css');
//mix.browserSync('81.4.122.131');

mix.webpackConfig({
    resolve: {
        extensions: ['.js', '.json', '.vue'],
        alias: {
            '~': path.join(__dirname, './resources/assets/js')
        }
    },
    output: {
        chunkFilename: 'js/[name].[chunkhash].js',
        publicPath: mix.config.hmr ? '//localhost:8080' : '/'
    }
});