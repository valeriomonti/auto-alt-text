let mix = require('laravel-mix');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');

mix.webpackConfig({
    plugins: [
        new CleanWebpackPlugin({
            cleanOnceBeforeBuildPatterns: ['**/*'],
            verbose: true,
        }),
    ],
});

mix
    .postCss('resources/css/admin.css', 'css', [
        require('postcss-nested')()
    ])
    .js('resources/js/admin.js', 'dist/js')
    .setPublicPath('dist');

if (mix.inProduction()) {
    mix.version();
    mix.then(async () => {
        const convertToFileHash = require("laravel-mix-make-file-hash");
        const fileHashedManifest = await convertToFileHash({
            publicPath: "dist",
            manifestFilePath: "dist/mix-manifest.json"
        });
    });
}
