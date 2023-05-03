const path = require('path');
const webpack = require('webpack');
const pjson = require('./package.json');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const AddCharsetWebpackPlugin = require('add-charset-webpack-plugin');
const FixStyleOnlyEntriesPlugin = require('webpack-fix-style-only-entries');
const ESLintPlugin = require('eslint-webpack-plugin');
const StylelintPlugin = require('stylelint-webpack-plugin');
const autoprefixer = require('autoprefixer');
const sass = require('sass');

let cssOutput = 'compressed';
let cssFilename = '[name].min.css';

const TARGET = process.env.npm_lifecycle_event;

if (TARGET === 'uncompress') {
  cssOutput = 'expanded';
  cssFilename = '[name].css';
}

module.exports = {
  cache: true,
  target: ['web', 'es5'],
  entry: {
    'acms': './src/scss/acms.scss',
    'acms-admin': './src/scss/acms-admin.scss',
    'acms-base': './src/scss/acms-base.scss',
    'acms-system': './src/scss/acms-system.scss',
    'acms-fonts': './src/scss/acms-system.scss',
    'template-vars': './src/scss/template-vars.scss',
    normalize: './src/js/normalize-css.js',
    reset: './src/js/reset-css.js',
  },
  output: {
    path: path.join(__dirname),
    filename: 'dest/[name].js',
    assetModuleFilename: 'dest/assets/[name][ext]',
  },
  module: {
    rules: [
      {
        test: /\.(js|ts|tsx)$/,
        include: /src\/js/,
        use: {
          loader: 'babel-loader',
        },
      },
      {
        test: /\.(scss|css)$/,
        use: [
          {
            loader: MiniCssExtractPlugin.loader,
            options: {
              publicPath: '../',
            },
          },
          {
            loader: path.resolve(__dirname, 'lib/css-replace-loader.js'),
            options: {
              target: /^(acms-admin|acms-system)$/,
              pattern: /(\.|'|"|=)acms-/ig,
              replace: '$1acms-admin-',
            },
          },
          {
            loader: 'css-loader',
            options: {
              url: false,
              // 0 => no loaders (default);
              // 1 => postcss-loader;
              // 2 => postcss-loader, sass-loader
              importLoaders: 3,
              sourceMap: true,
            },
          },
          {
            loader: 'postcss-loader',
            options: {
              postcssOptions: {
                plugins: [
                  autoprefixer({
                    grid: 'autoplace',
                  }),
                ],
              },
              sourceMap: true,
            },
          },
          {
            loader: 'sass-loader',
            options: {
              implementation: sass,
              sourceMap: true,
              sassOptions: {
                outputStyle: cssOutput,
                charset: false,
              },
            },
          },
        ],
      },
      {
        test: /\.(jpg|png|svg|woff|woff2|eot|ttf)(\?.*$|$)/,
        type: 'asset',
        parser: {
          dataUrlCondition: {
            maxSize: 4 * 1024, // <--- 4kb
          },
        },
      },
    ],
  },
  plugins: [
    // ESLint
    new ESLintPlugin({
      context: './src',
      extensions: ['js', 'ts', 'tsx', 'jsx'],
      exclude: ['/node_modules/'],
      emitError: true,
      emitWarning: true,
      failOnError: true,
      fix: false,
    }),
    // StyleLint
    new StylelintPlugin({
      configFile: '.stylelintrc',
      context: './src/scss',
      emitError: true,
      emitWarning: true,
      failOnError: true,
      fix: true,
    }),
    // cssに@charset付与
    new AddCharsetWebpackPlugin({
      charset: 'utf-8',
    }),
    new webpack.BannerPlugin({
      banner: `
/*!
 * ${pjson.name} Ver. ${pjson.version}
 * Copyright ${pjson.author}
 * license: ${pjson.license}
 *
 * カスタマイズする場合は、オリジナルのテーマにコピーしてご利用ください。
 *
 */
`,raw: true,
      test: /(acms|template)-?.*\.css/
    }),
    // cssの書き出し
    new MiniCssExtractPlugin({
      filename: 'css/'+cssFilename,
    }),
    // 余分なJSを書き出さない
    new FixStyleOnlyEntriesPlugin(),
  ],
};
