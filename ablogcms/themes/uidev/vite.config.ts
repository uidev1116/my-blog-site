import { resolve, basename } from 'path'
import {
  defineConfig,
  mergeConfig,
  splitVendorChunkPlugin,
  loadEnv,
} from 'vite'
import { visualizer } from 'rollup-plugin-visualizer'
import react from '@vitejs/plugin-react'
import eslint from 'vite-plugin-eslint'
import stylelint from 'vite-plugin-stylelint'

// https://vitejs.dev/config/
export default defineConfig(({ command, mode }) => {
  return mergeConfig(
    {
      base: command === 'serve' ? `/themes/${basename(__dirname)}` : './',
      plugins: [
        splitVendorChunkPlugin(),
        eslint({
          extensions: ['js', 'ts', 'tsx'],
          exclude: ['**/node_modules/**'],
          emitError: true,
          emitWarning: true,
          failOnError: false,
          fix: true,
        }),
        stylelint({
          include: ['src/scss/*.scss'],
          exclude: ['**/node_modules/**'],
          emitError: true,
          emitWarning: true,
          fix: true,
        }),
        react(),
        ...(mode === 'analyze'
          ? [
              visualizer({
                open: true,
                filename: 'dest/stats.html',
                gzipSize: true,
                brotliSize: true,
              }),
            ]
          : []),
      ],
      build: {
        manifest: true,
        outDir: 'dest',
        assetsInlineLimit: 4096, // 4kbより小さいアセットをインライン化
        rollupOptions: {
          input: {
            index: resolve(__dirname, 'src/js/index.js'),
            admin: resolve(__dirname, 'src/js/admin.js'),
          },
        },
      },
    },
    mode === 'development'
      ? {
          build: {
            sourcemap: 'inline',
            minify: false,
            watch: {},
          },
          server: {
            host: true,
          },
        }
      : {},
  )
})
