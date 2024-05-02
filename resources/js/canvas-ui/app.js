import './../bootstrap';
import './../../sass/canvas-ui.scss';
import NProgress from 'nprogress';
// import Router from 'vue-router';
// import Vue from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import { createApp, h } from 'vue/dist/vue.esm-bundler';
import VueMeta from 'vue-meta';
import base from './mixins/base';
import moment from 'moment';
import routes from './routes';

import Popper from 'popper.js';

// Vuetify
import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import { aliases, mdi } from 'vuetify/iconsets/mdi'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'


const myCustomLightTheme = {
  dark: false,
  colors: {
    background: '#040e15',
    surface: '#4f4f51',
    cover: '#d6d6d6',
    primary: '#f56677',
    'primary-darken-1': '#3700B3',
    secondary: '#f2c4ce',
    'secondary-darken-1': '#018786',
    error: '#7d1528',
    info: '#0caaed',
    success: '#067d51',
    warning: '#d4aa02',
    golden: '#ad974f',
  },
}

const vuetify = createVuetify({
    /*icons: {
        defaultSet: 'mdi',
        aliases,
        sets: {
          mdi,
        },
      },*/
    components,
    directives,
    theme: {
        defaultTheme: 'myCustomLightTheme',
        themes: {
          myCustomLightTheme,
        },
      },
})
//-----------Vuetify

NProgress.configure({
    showSpinner: false,
    easing: 'ease',
    speed: 300,
});


const router = createRouter({
  history: createWebHistory('canvas-ui'),
  routes: routes // Using the imported routes array
});

router.beforeEach((to, from, next) => {
    NProgress.start();
    next();
});

const app = createApp({}).use(vuetify);

// Add moment to the Vue app instance
app.config.globalProperties.$moment = moment;

// Set productionTip to false
app.config.productionTip = false;

// Mixin
app.mixin(base);

// Use VueMeta plugin
// app.use(createMetaManager());

// Use Vue Router
// const router = createRouter({
//   history: createWebHistory(),
//   routes,
// });
app.use(router);

// Mount the app to the #ui element in your HTML
app.mount('#ui'); 

// new Vue({
//     el: '#ui',
//     router,
// });


