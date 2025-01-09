import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import Vue3Toastify from 'vue3-toastify';
import AgentFlowEditor from './components/AgentFlowEditor.vue';
// Create Vue app
const app = createApp({});
const pinia = createPinia();

// Register components
app.component('agent-flow-editor', AgentFlowEditor);

// Use plugins
app.use(pinia);
app.use(Vue3Toastify, {
    autoClose: 3000,
  });
// Mount app
app.mount('#app');
