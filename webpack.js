const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')

const buildMode = process.env.NODE_ENV
const isDev = buildMode === 'development'
webpackConfig.devtool = isDev ? 'cheap-source-map' : 'source-map'
// webpackConfig.bail = false

webpackConfig.stats = {
	colors: true,
	modules: false,
}

const appId = 'integration_replicate'
webpackConfig.entry = {
	adminSettings: { import: path.join(__dirname, 'src', 'adminSettings.js'), filename: appId + '-adminSettings.js' },
}

module.exports = webpackConfig
