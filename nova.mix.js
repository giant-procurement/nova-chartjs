const mix = require('laravel-mix')
const webpack = require('webpack')
const path = require('path')

class NovaExtension {
  name() {
    return 'nova-extension'
  }

  register(name) {
    this.name = name
  }

  webpackConfig(webpackConfig) {
    webpackConfig.externals = {
      vue: 'Vue',
    }

    webpackConfig.resolve.alias = {
      ...(webpackConfig.resolve.alias || {}),
      'laravel-nova': path.join(__dirname, '../../vendor/laravel/nova/resources/js/mixins/packages.js'),
      'laravel-nova-ui': path.join(__dirname, '../../vendor/laravel/nova/node_modules/laravel-nova-ui'),
      '@': path.join(__dirname, '../../vendor/laravel/nova/resources/js/'),
      '@ui': path.join(__dirname, '../../vendor/laravel/nova/resources/ui/'),
    }

    webpackConfig.resolve.extensions = [
      ...(webpackConfig.resolve.extensions || []),
      '.ts',
      '.tsx',
    ]

    webpackConfig.module.rules.push({
      test: /\.tsx?$/,
      loader: 'ts-loader',
      exclude: /node_modules/,
      options: {
        appendTsSuffixTo: [/\.vue$/],
        transpileOnly: true,
      },
    })

    webpackConfig.output = {
      uniqueName: this.name,
    }
  }
}

mix.extend('nova', new NovaExtension())
