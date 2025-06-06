import { generateOcsUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

const baseURL = generateOcsUrl('/apps/jukebox/api')
export const settingsAxios = axios.create({
  baseURL,
})
