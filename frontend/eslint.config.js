import js from '@eslint/js'
import globals from 'globals'
import reactHooks from 'eslint-plugin-react-hooks'
import reactRefresh from 'eslint-plugin-react-refresh'
import { defineConfig, globalIgnores } from 'eslint/config'

export default defineConfig([
  globalIgnores(['dist', 'dist-server']),
  {
    files: ['**/*.{js,jsx}'],
    extends: [
      js.configs.recommended,
      reactHooks.configs.flat.recommended,
      reactRefresh.configs.vite,
    ],
    languageOptions: {
      globals: globals.browser,
      parserOptions: { ecmaFeatures: { jsx: true } },
    },
    rules: {
      // The React Compiler lint rules ship in eslint-plugin-react-hooks'
      // recommended flat config, but they're forward-looking guidance for
      // codebases adopting the compiler — which this app doesn't. They flag
      // idiomatic, working patterns (loading data via setState in a mount
      // effect; calling a state setter declared lower in the component). Keep
      // them as non-blocking warnings so `npm run lint` stays a useful gate for
      // genuine errors instead of failing on style.
      'react-hooks/set-state-in-effect': 'warn',
      'react-hooks/immutability': 'warn',
      'react-hooks/refs': 'warn',
    },
  },
])
