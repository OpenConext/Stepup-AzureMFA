var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .copyFiles({
        from: './assets/images',
        to: './images/[path][name].[ext]',
    })
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .addStyleEntry('global', './assets/scss/application.scss')
    .addLoader({test: /\.scss$/, loader: 'webpack-import-glob-loader'})
    // Convert sass files.
    .enableSassLoader(function (options) {
        options.sassOptions = {
            outputStyle: 'expanded',
            includePaths: ['public'],
        };
    })
    .autoProvidejQuery()
    .cleanupOutputBeforeBuild()
    .enableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())
;

module.exports = Encore.getWebpackConfig();
