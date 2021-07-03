const commonConfig  = require("./webpack.common");
const { devConfig } = require("./webpack.configs");
const { merge }     = require('webpack-merge');

module.exports = merge( commonConfig, devConfig );