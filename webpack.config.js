const Encore = require('@symfony/webpack-encore');
const path = require('path');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/')
    .setPublicPath('/bundles/kiwicmx')
    .setManifestKeyPrefix('')
    .cleanupOutputBeforeBuild()
    .disableSingleRuntimeChunk()

    .addEntry('iconedSelect', './assets/scripts/iconedSelect.js')
    .addStyleEntry('backend', './assets/styles/backend.scss')
    .addStyleEntry('ui', './assets/styles/ui.scss')

    //.configureBabel((config) => {
        //config.plugins.push('@babel/plugin-transform-class-properties');
        //config.plugins.push('@babel/plugin-transform-private-methods');
    //})

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
    })

    // enables Sass/SCSS support
    .enableSassLoader()
    // enable CSS source maps
    .enableSourceMaps()
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())
;

module.exports = Encore.getWebpackConfig();
