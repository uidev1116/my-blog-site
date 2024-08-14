// const path = require('path');
// const plugin = require('tailwindcss/plugin');

/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    // node_modules を除外するため個別指定
    './src/**/*.{js,ts,jsx,tsx,vue}',
  ],
  theme: {},
  future: {
    hoverOnlyWhenSupported: true,
  },
  corePlugins: {
    preflight: false,
  },
}
