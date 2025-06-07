import { axios } from './axios'
import Settings from './Settings.vue'
import './style.scss'
import { createApp } from 'vue'

console.log('[DEBUG] Mounting jukebox Settings')
console.log('[DEBUG] Base URL:', axios.defaults.baseURL)
createApp(Settings).mount('#jukebox-settings')
