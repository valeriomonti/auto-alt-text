let mix = require('laravel-mix');

mix
    .sass('resources/css/admin-style.scss', 'css')
    .js('resources/js/admin.js', 'dist/js')
    .setPublicPath('dist')


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
