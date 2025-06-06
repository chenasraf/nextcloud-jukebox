import App from './App.vue'
import './style.scss'
import { createApp } from 'vue'
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

const baseURL = generateOcsUrl('/apps/jukebox/api')
axios.defaults.baseURL = baseURL

console.log('[DEBUG] Mounting jukebox app')
console.log('[DEBUG] Base URL:', baseURL)
createApp(App).mount('#jukebox-app')
