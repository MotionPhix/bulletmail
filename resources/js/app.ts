import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { configureEcho } from '@laravel/echo-vue';
import { renderApp } from '@inertiaui/modal-vue'
import { createApp } from 'vue';
import { initializeTheme } from './composables/useAppearance';

configureEcho({
  broadcaster: 'pusher',
});

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
  interface ImportMetaEnv {
    readonly VITE_APP_NAME: string;
    [key: string]: string | boolean | undefined;
  }

  interface ImportMeta {
    readonly env: ImportMetaEnv;
    readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
  }
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    const app = createApp({ render: renderApp(App, props) });

    app.use(plugin)
      .use(ZiggyVue);

    initializeTheme()

    app.mount(el);
  },
  progress: {
    color: '#4B5563',
  },
});
