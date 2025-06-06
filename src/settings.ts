import Settings from './Settings.vue'
import './style.scss'
import { createApp } from 'vue'
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

const baseURL = generateOcsUrl('/apps/jukebox/api')
axios.defaults.baseURL = baseURL

console.log('[DEBUG] Mounting jukebox Settings')
console.log('[DEBUG] Base URL:', baseURL)
createApp(Settings).mount('#jukebox-settings')
