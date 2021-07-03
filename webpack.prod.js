const commonConfig   = require("./webpack.common");
const { prodConfig } = require("./webpack.configs");
const { merge }      = require('webpack-merge');

module.exports = merge( commonConfig, prodConfig );