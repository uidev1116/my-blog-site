import globals from 'globals'
import pluginJs from '@eslint/js'
import tseslint from 'typescript-eslint'
import react from 'eslint-plugin-react'
import jsxA11y from 'eslint-plugin-jsx-a11y';
import reactHooks from 'eslint-plugin-react-hooks';
import reactRefresh from 'eslint-plugin-react-refresh'
import tailwind from "eslint-plugin-tailwindcss";
import prettier from "eslint-config-prettier";
import { fixupPluginRules } from "@eslint/compat";

export default [
  { files: ['**/*.{js,mjs,cjs,jsx}'] },
  {
    languageOptions: {
      ecmaVersion: 2020,
      globals: {
        ...globals.browser,
        ...globals.jquery,
        ACMS: 'writable',
      },
    },
  },
  pluginJs.configs.recommended,
  react.configs.flat.recommended,
  jsxA11y.flatConfigs.recommended,
  ...tailwind.configs["flat/recommended"],
  {
    rules: {
      // eslint-plugin-react
      'react/jsx-filename-extension': [
        'error',
        {
          extensions: ['.jsx', '.tsx'],
        },
      ],
      'react/react-in-jsx-scope': 'off',
      'react/prop-types': 'off',
      // 関数コンポーネントは関数宣言を強制
      'react/function-component-definition': [
        'error',
        {
          namedComponents: 'function-declaration',
          unnamedComponents: 'function-expression',
        },
      ],
      'react/require-default-props': 'off',
      'react/jsx-props-no-spreading': 'off',
      'consistent-return': 'off',
      /* 型定義にno-unused-varsのルールが適用される問題への対策*/
      'no-unused-vars': 'off',
    },
  },
  {
    plugins: {
      'react-hooks': fixupPluginRules(reactHooks),
      'react-refresh': reactRefresh,
    },
    rules: {
      ...reactHooks.configs.recommended.rules,
      'react-refresh/only-export-components': [
        'warn',
        { allowConstantExport: true },
      ],
    },
  },
  {
    files: ['**/*.{ts, tsx}'],
    languageOptions: {
      parser: tseslint.parser,
    },
    plugins: {
      ts: tseslint.plugin,
    },
    rules: {
      ...tseslint.configs.recommended.rules,
    }
  },
  {
    settings: {
      react: {
        version: 'detect',
      },
    },
  },
  prettier,
]
