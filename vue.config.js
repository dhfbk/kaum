const {defineConfig} = require('@vue/cli-service')
// const webpack = require("webpack");
// const dotenv = require('dotenv').config({ path: __dirname + '/.env' })

module.exports = defineConfig({
    publicPath: process.env.VUE_PUBLIC_PATH,
    transpileDependencies: true,
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
})

