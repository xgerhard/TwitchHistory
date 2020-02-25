import Vue from 'vue';
import VueRouter from 'vue-router';

import Home from '@/js/components/Home';
import About from '@/js/components/About';

Vue.use(VueRouter);

const router = new VueRouter({
    mode: 'history',
    routes: [
        {
            path: '/',
            name: 'home',
            component: Home,
            meta: {
                title: 'Home'
            }
        },
        {
            path: '/about',
            name: 'about',
            component: About,
            meta: {
                title: 'About'
            }
        }
    ]
});

// Set page title
router.beforeEach((to, from, next) => {
    const title = to.matched.slice().reverse().find(r => r.meta && r.meta.title);
    if(title)
        document.title = title.meta.title;

    next();
});

export default router;