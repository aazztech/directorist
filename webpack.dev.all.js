const commonConfig   = require("./webpack.common");
let   { devConfig }  = require("./webpack.configs");
const { merge }      = require('webpack-merge');
const { vueEntries } = require('./webpack.entry-list');

devConfig.entry = { ...vueEntries };

module.exports = merge( commonConfig, devConfig );