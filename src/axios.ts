import { generateOcsUrl } from '@nextcloud/router'
import _axios from '@nextcloud/axios'

const baseURL = generateOcsUrl('/apps/jukebox/api')
export const axios = _axios.create({ baseURL })
