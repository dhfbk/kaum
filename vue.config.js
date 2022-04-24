const path = require('path');
const {defineConfig} = require('@vue/cli-service')

module.exports = defineConfig({
    transpileDependencies: true,
    css: {
        loaderOptions: {
            sass: {
                sassOptions: {
                    includePaths: [
                        path.resolve(__dirname, './node_modules'),
                    ],
                },
            },
        },
    },
    pluginOptions: {
        i18n: {
            locale: 'en',
            fallbackLocale: 'en',
            localeDir: 'locales',
            enableLegacy: false,
            runtimeOnly: false,
            compositionOnly: false,
            fullInstall: true
        }
    },
    devServer: {
        allowedHosts: [
            'dh-hetzner.fbk.eu',
        ],
    },
})

