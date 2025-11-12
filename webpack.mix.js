const mix = require('laravel-mix')

require('./nova.mix')

mix
    .setPublicPath('dist')
    .js('resources/js/chart-js-integration.js', 'js')
    .vue({ version: 3 })
    .nova('coroowicaksono/chart-js-integration')
