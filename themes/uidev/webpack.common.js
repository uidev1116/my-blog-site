const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const FixStyleOnlyEntries = require("webpack-fix-style-only-entries");
const HtmlWebpackPlugin = require('html-webpack-plugin');
const HtmlCriticalWebpackPlugin = require('html-critical-webpack-plugin');
const VueLoaderPlugin = require('vue-loader/lib/plugin');
const ESLintPlugin = require('eslint-webpack-plugin');
const StylelintPlugin = require('stylelint-webpack-plugin');
const autoprefixer = require('autoprefixer');
const sass = require('sass');
const pkg = require('./package.json');

module.exports = {
  cache: true,
  performance: {
    maxAssetSize: 1500000,
    maxEntrypointSize: 1500000,
  },
  target: ["web", "es5"],
  entry: {
    bundle: `${__dirname}/src/js/index.js`,
    'admin': `${__dirname}/src/scss/site-admin.scss`,
    // 'amp': `${__dirname}/src/scss/site-amp.scss`
  },
  output: {
    path: `${__dirname}/dest/`,
    publicPath: `/themes/${pkg.config.theme}/dest/`,
    filename: '[name].js',
    chunkFilename: `[name].chunk.js?date=${new Date().getTime()}`,
    assetModuleFilename: 'assets/[name][ext]',
  },
  optimization: {
    splitChunks: {
      name: 'vendor',
      chunks: 'initial',
    },
  },
  resolve: {
    extensions: ['.vue', '.js', '.ts', '.tsx'],
    alias: {
      vue$: 'vue/dist/vue.esm.js',
    },
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
        test: /\.vue$/,
        include: /src\/js/,
        loader: 'vue-loader',
        options: {
          loaders: {
            scss: 'vue-style-loader!css-loader!sass-loader', // <style lang="scss">
            sass: 'vue-style-loader!css-loader!sass-loader?indentedSyntax', // <style lang="sass">
          },
        },
      },
      {
        test: /\.(scss|css)$/,
        use: [
          {
            loader: MiniCssExtractPlugin.loader,
          },
          {
            loader: 'css-loader',
            options: {
              url: true,
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
      extensions: ['js', 'ts', 'tsx', 'jsx', 'vue'],
      exclude: ['/node_modules/'],
      emitError: true,
      emitWarning: true,
      failOnError: true,
      fix: true,
    }),
    // StyleLint
    new StylelintPlugin({
      configFile: '.stylelintrc',
      context: './src/scss',
      emitError: true,
      emitWarning: true,
      failOnError: false,
      fix: true,
    }),
    // Vue Loader
    new VueLoaderPlugin(),
    // HTMLファイルを出力
    new HtmlWebpackPlugin({
      template: `${__dirname}/src/index.html`,
      filename: 'index.html',
      inject: true,
    }),
    // sassのビルド結果ファイルを分割
    new FixStyleOnlyEntries(),
    // ファーストビューのCSSをインライン化
    new HtmlCriticalWebpackPlugin({
      base: `${__dirname}/dest/`,
      src: 'index.html',
      dest: 'index.html',
      inline: true,
      width: 1200,
      height: 700,
      penthouse: {
        blockJSRequests: false,
      },
    }),
    // cssの書き出し
    new MiniCssExtractPlugin({
      filename: '[name].css',
      chunkFilename: `[name].chunk.css?date=${new Date().getTime()}`,
    }),
  ],
};
