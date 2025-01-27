// Load vue js
import { createApp } from 'vue/dist/vue.esm-bundler';

const el = document.getElementById("app");

import media from "./vue_components/editor.vue";

const app = createApp({});
app.component("media", media);

app.mount(el);
