import './assets/main.css'

import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import axios from 'axios'
import config from '@/config'

// Use environment-based configuration
axios.defaults.baseURL = config.apiBaseUrl
axios.defaults.headers.common['Content-Type'] = 'application/json'
axios.defaults.headers.common['Accept'] = 'application/json'

const app = createApp(App)

app.use(router)
app.use(store)

app.mount('#app')
