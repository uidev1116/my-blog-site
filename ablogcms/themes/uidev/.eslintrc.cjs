module.exports = {
  env: {
    browser: true,
    es2021: true,
  },
  extends: [
    'plugin:react/recommended',
    'plugin:jsx-a11y/recommended',
    'airbnb',
    'airbnb/hooks',
    'prettier',
  ],
  parser: '@typescript-eslint/parser',
  parserOptions: {
    ecmaVersion: 'latest',
    sourceType: 'module',
    project: true,
    tsconfigRootDir: __dirname,
  },
  plugins: ['react-refresh'],
  globals: {
    $: true,
    jQuery: true,
    ACMS: true
  },
  rules: {
    // eslint-plugin-import
    'import/no-extraneous-dependencies': [
      'error',
      {
        peerDependencies: true,
      },
    ],
    'import/extensions': [
      'error',
      {
        js: 'never',
        jsx: 'never',
        ts: 'never',
        tsx: 'never',
      },
    ],
    'import/prefer-default-export': 'off',
    // eslint-plugin-react
    'react/jsx-filename-extension': [
      'error',
      {
        extensions: ['.jsx', '.tsx'],
      },
    ],
    'react/react-in-jsx-scope': 'off',
    'react/prop-types': 'off',
    'react/function-component-definition': [
      'error',
      {
        namedComponents: 'arrow-function',
        unnamedComponents: 'arrow-function',
      },
    ],
    'react/require-default-props': 'off',
    'react/jsx-props-no-spreading': 'off',
    // eslint-config-react-refresh
    'react-refresh/only-export-components': [
      'warn',
      { allowConstantExport: true },
    ],
    // eslint-config-airbnb
    'consistent-return': 'off',
    /* 型定義にno-unused-varsのルールが適用される問題への対策*/
    'no-unused-vars': 'off',
  },
  overrides: [
    // プロジェクトにJavaScriptとTypeScriptが共存しているため、
    // ts, tsxの拡張子のファイルは別途TypeScript用のルールを適用
    {
      files: ['**/*.ts', '**/*.tsx'],
      extends: ['plugin:@typescript-eslint/recommended'],
      plugins: ['@typescript-eslint'],
      rules: {
        // 未使用変数を禁止
        '@typescript-eslint/no-unused-vars': 'error',
        // 定義前の変数の使用に警告
        '@typescript-eslint/no-use-before-define': 'warn',
      },
    },
  ],
  settings: {
    'import/resolver': {
      vite: {
        configPath: 'vite.config.ts'
      },
      typescript: {},
    },
    'import/internal-regex': /^@types\//,
  },
}
