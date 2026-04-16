
import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp, Link, Head } from '@inertiajs/vue3';
import BootstrapVueNext from 'bootstrap-vue-next';
import vClickOutside from "click-outside-vue3";
import VueApexCharts from "vue3-apexcharts";

import Layout from '@/Shared/Layouts/Main.vue';
import store from "@/Shared/State/store";
import AOS from 'aos';
import 'aos/dist/aos.css';

AOS.init({
    easing: 'ease-out-back',
    duration: 1000
});

const appName = import.meta.env.VITE_APP_NAME || 'DOST-IX';
createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        const pagePath = `./Pages/${name}.vue`;
        const matchedPage = pages[pagePath]
            ?? pages[Object.keys(pages).find((path) => path.toLowerCase() === pagePath.toLowerCase())];

        if (!matchedPage) {
            throw new Error(`Inertia page not found: ${name}`);
        }

        let page = matchedPage.default;
        if (page.layout === undefined) {
            page.layout = Layout;
        }
        return page;
    },
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(store)
            .use(BootstrapVueNext)
            .component('Link', Link)
            .component('Head', Head)
            .use(VueApexCharts)
            .use(vClickOutside)
            .mount(el);
    },
    title: title => title ? `${appName} | ${title}` : appName,
    progress: {
        color: '#4B5563',
    },
});
