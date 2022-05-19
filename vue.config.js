const path = require('path');
const {defineConfig} = require('@vue/cli-service')
// const webpack = require("webpack");
// const dotenv = require('dotenv').config({ path: __dirname + '/.env' })

module.exports = defineConfig({
    publicPath: "/kaum/",
    transpileDependencies: true,
    // configureWebpack: {
    //     resolve: {
    //         fallback: {
    //             fs: false,
    //             os: false,
    //             util: false,
    //             path: false
    //         }
    //     }
    // },
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

