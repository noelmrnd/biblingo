import { defineConfig } from 'astro/config';

export default defineConfig({
  site: 'https://libringo.com',
  trailingSlash: 'never',
  build: {
    format: 'file',
  },
});
