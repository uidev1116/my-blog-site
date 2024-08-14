import { resolve } from 'path'
import { defineConfig } from 'vite'
import { visualizer } from 'rollup-plugin-visualizer'
import react from '@vitejs/plugin-react'
import eslint from 'vite-plugin-eslint'
import stylelint from 'vite-plugin-stylelint'

// https://vitejs.dev/config/
export default defineConfig(({ command, mode }) => ({
  base: './',
  plugins: [
    // eslint({
    //   extensions: ['js', 'ts', 'tsx'],
    //   exclude: ['**/node_modules/**'],
    //   emitError: true,
    //   emitWarning: true,
    //   failOnError: false,
    //   fix: true,
    // }),
    stylelint({
      include: ['src/scss/*.scss'],
      exclude: ['**/node_modules/**'],
      emitError: true,
      emitWarning: true,
      fix: true,
    }),
    react(),
    mode === 'analyze' &&
      visualizer({
        open: true,
        filename: 'dist/stats.html',
        gzipSize: true,
        brotliSize: true,
      }),
  ],
  build: {
    manifest: true,
    assetsInlineLimit: 4096, // 4kbより小さいアセットをインライン化
    rollupOptions: {
      input: {
        index: resolve(__dirname, 'src/js/index.ts'),
        admin: resolve(__dirname, 'src/js/admin.ts'),
      },
    },
    output: {
      manualChunks(id) {
        if (id.includes('node_modules')) {
          return 'vendor'
        }
      },
    },
  },
}))
