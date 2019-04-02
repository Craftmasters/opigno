const path = require('path');
const merge = require('webpack-merge');
const webpackConfig = require('./webpack.config');

module.exports = merge(webpackConfig, {
  devtool: 'source-map',
  output: {
    pathinfo: true,
    publicPath: '/',
    filename: '[name].js',
  },
  devServer: {
    host: 'opigno-d8',
    contentBase: path.join(__dirname, '../..'),
    port: 8080,
  },
});
