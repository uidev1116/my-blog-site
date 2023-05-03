const webpackMerge = require('webpack-merge');
const common = require('./webpack.common.js');

process.env.BABEL_ENV = 'development';

module.exports = webpackMerge.merge(common, {
  mode: 'development',
  devtool: 'inline-source-map',
  resolve: {
    modules: ['node_modules'],
    extensions: ['.js', '.ts', '.tsx'],
    // alias: {
    //   "styled-components": path.resolve(__dirname, "node_modules", "styled-components"),
    // }
  },
  devServer: {
    open: true,
    openPage: '',
    inline: true,
    hot: false,
    // contentBase: `${__dirname}/dest`,
    publicPath: '/dest/',
    watchContentBase: true,
    port: 3000,
    proxy: {
      '**': {
        target: {
          host: 'acms.org',
          protocol: 'http:',
          port: 80
        },
        secure: false,
        changeOrigin: true
      }
    }
  }
});
