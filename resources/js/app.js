import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Toast from "vue-toastification";
import { useToast } from "vue-toastification";
import 'animate.css';
import "vue-toastification/dist/index.css";
import "vue-multiselect/dist/vue-multiselect.css";
import { Capacitor } from '@capacitor/core';
import { Keyboard } from '@capacitor/keyboard';

// Hide the ˄ ˅ ✓ bar iOS shows above the keyboard in web views.
if (Capacitor.isNativePlatform()) Keyboard.setAccessoryBarVisible({ isVisible: false });

// Keep the startup loader on screen at least this long, so it doesn't just flash.
const APP_LOADER_MIN_MS = 1200;

// Fades out the startup loader from app.blade.php once Vue has rendered the first page.
function hideAppLoader() {
    const loader = document.getElementById('app-loader');
    if (!loader) return;
    setTimeout(() => {
        loader.classList.add('is-hidden');
        loader.addEventListener('transitionend', () => loader.remove(), { once: true });
    }, Math.max(0, APP_LOADER_MIN_MS - performance.now()));
}

// Keep html.canvas-dark (set by app.blade.php on first load) in sync when navigating between admin and league pages.
router.on('navigate', (event) => {
    const component = event.detail.page.component;
    document.documentElement.classList.toggle('canvas-dark', component.startsWith('League/') || component === 'Auth/PlayerLogin');
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const inertiaApp =   createApp({render: () => h(App, props)})
            .use(plugin)
            .use(Toast)
            .component('useToast', useToast)
            .use(ZiggyVue);
            inertiaApp.config.globalProperties.$toast = useToast();
            inertiaApp.mount(el);
            hideAppLoader();
    },
    progress: {
        color: '#4B5563',
    },
});
