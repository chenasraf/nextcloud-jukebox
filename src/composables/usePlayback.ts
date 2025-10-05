import { ref, computed, watch } from 'vue'
import { axios } from '@/axios'
import type { Track, PodcastEpisode, RadioStation } from '@/models/media'

type MediaType = 'track' | 'podcast' | 'radio'

// Base media type
export interface Playable {
  id: number | string
  type: MediaType
  duration?: number | null
  [key: string]: unknown
}

export function trackToPlayable(track: Track): Playable {
  return { type: 'track', ...track }
}

export function podcastEpisodeToPlayable(episode: PodcastEpisode): Playable {
  return { type: 'podcast', ...episode }
}

export function radioStationToPlayable(station: RadioStation): Playable {
  return { type: 'radio', ...station }
}

export function toPlayable<T extends Track | PodcastEpisode | RadioStation | Playable>(
  media: T,
): Playable {
  if ('trackNumber' in media) {
    return trackToPlayable(media as Track)
  } else if ('guid' in media) {
    return podcastEpisodeToPlayable(media as PodcastEpisode)
  } else if ('remoteUuid' in media) {
    return radioStationToPlayable(media as RadioStation)
  }
  throw new Error('Unsupported media type')
}

const audio = new Audio()
const isPlaying = ref(false)
const queue = ref<Playable[]>([])
const currentIndex = ref(-1)
const loading = ref(false)
const currentTime = ref(0)
const duration = ref(0)
const awaitingSeekResume = ref(false)
const resumePosition = ref(0)
let seekInProgress = false
const suppressTimeUpdate = ref(false)

const currentMedia = computed(() =>
  currentIndex.value >= 0 ? queue.value[currentIndex.value] ?? null : null,
)

const streamPaths: Record<string, (_media: Playable) => string> = {
  track: (media) => `/music/tracks/${media.id}/stream`,
  podcast: (media) => `/podcasts/episodes/${media.id}/stream`,
  radio: (media) => `/radio/${media.remoteUuid}/stream`,
}

function getStreamUrl(media: Playable): string {
  const pathResolver = streamPaths[media.type]
  if (!pathResolver) {
    throw new Error(`Unsupported media type: ${media.type}`)
  }
  return axios.defaults.baseURL + pathResolver(media)
}

function trackAction(media: Playable, action: 'play' | 'pause' | 'complete' | 'resume') {
  const endpoints: Record<
    MediaType,
    { path: string; data: (media: Playable) => unknown } | undefined
  > = {
    podcast: {
      path: '/podcasts/track',
      data: (media: Playable) => ({
        id: media.id,
        guid: media.guid,
        action,
        timestamp: Math.floor(Date.now() / 1000),
        position: Math.floor(currentTime.value),
        total: Math.floor(duration.value),
        device: 'web',
      }),
    },
    track: undefined,
    radio: undefined,
  }

  if (!endpoints[media.type]) return 0

  const endpoint = endpoints[media.type]!

  axios
    .post(endpoint.path, endpoint.data(media))
    .catch((err) => console.warn('Tracking failed:', err))
}

async function getStartPosition(media: Playable): Promise<number> {
  const endpoints: Record<MediaType, ((_media: Playable) => string) | undefined> = {
    podcast: (media) => `/podcasts/episodes/${media.id}/position`,
    track: undefined,
    radio: undefined,
  }
  if (!endpoints[media.type]) return 0
  const endpoint = endpoints[media.type]!(media)
  try {
    const response = await axios.get(endpoint)
    const position = response.data.position || 0
    if (position > 0) {
      awaitingSeekResume.value = true
    }
    return position
  } catch (err) {
    console.warn('Failed to get start position:', err)
    return 0
  }
}

async function playMedia(media: Playable) {
  const index = queue.value.findIndex((item) => item.id === media.id && item.type === media.type)

  if (index !== -1) {
    currentIndex.value = index
  } else {
    queue.value.push(media)
    currentIndex.value = queue.value.length - 1
  }

  const src = getStreamUrl(media)

  if (audio.src !== src) {
    audio.pause()
    resumePosition.value = await getStartPosition(media)
    audio.src = src
    audio.load()
  } else {
    resumePosition.value = 0
    awaitingSeekResume.value = false
  }

  duration.value = typeof media.duration === 'number' ? media.duration : 0
}

function playIndex(index: number) {
  if (queue.value[index]) {
    currentIndex.value = index
    playMedia(queue.value[index])
  }
}

function next() {
  if (currentIndex.value + 1 < queue.value.length) {
    playIndex(currentIndex.value + 1)
  }
}

function prev() {
  if (audio.currentTime < 2 && currentIndex.value > 0) {
    playIndex(currentIndex.value - 1)
  } else {
    audio.currentTime = 0
  }
}

function pause() {
  audio.pause()
}

function togglePlay() {
  if (audio.paused) {
    audio.play().catch((err) => console.error('Toggle play failed:', err))
  } else {
    pause()
  }
}

function addToQueue(media: Playable | Playable[]) {
  const items = Array.isArray(media) ? media : [media]
  queue.value.push(...items)
}

function addAsNext(media: Playable) {
  if (currentIndex.value >= 0) {
    queue.value.splice(currentIndex.value + 1, 0, media)
  } else {
    queue.value.push(media)
  }
}

function removeFromQueue(media: Playable) {
  const index = queue.value.findIndex((item) => item.id === media.id && item.type === media.type)
  if (index !== -1) {
    queue.value.splice(index, 1)
    if (currentIndex.value >= index) {
      currentIndex.value = Math.max(currentIndex.value - 1, -1)
    }
  }
}

function clearQueue() {
  queue.value = []
  currentIndex.value = -1
  audio.pause()
  audio.src = ''
}

function overwriteQueue(newQueue: Playable[], startIndex = 0) {
  queue.value.splice(0, queue.value.length, ...newQueue)
  currentIndex.value = startIndex
  if (queue.value[startIndex]) {
    playMedia(queue.value[startIndex])
  }
}

function playFromQueue(media: Playable) {
  const index = queue.value.findIndex((item) => item.id === media.id && item.type === media.type)
  if (index !== -1) {
    playIndex(index)
  }
}

function setSeek(newTime: number) {
  // console.log('[setSeek] Seeking to:', newTime, { currentTime: audio.currentTime, duration: audio.duration });
  seekInProgress = true
  audio.currentTime = newTime
  setTimeout(() => {
    suppressTimeUpdate.value = false
  }, 200) // 2–3 frames at 60fps
}

audio.addEventListener('play', () => {
  // console.log('[audio event] play', { resumePosition: resumePosition.value, currentTime: currentTime.value, awaitingSeekResume: awaitingSeekResume.value, currentMedia: currentMedia.value });
  isPlaying.value = true
  if (awaitingSeekResume.value) return

  trackAction(currentMedia.value!, currentTime.value > 0 ? 'resume' : 'play')
})
audio.addEventListener('pause', () => {
  // console.log('[audio event] pause', { currentMedia: currentMedia.value });
  isPlaying.value = false
  trackAction(currentMedia.value!, 'pause')
})
audio.addEventListener('ended', () => {
  // console.log('[audio event] ended', { currentMedia: currentMedia.value });
  isPlaying.value = false
  trackAction(currentMedia.value!, 'complete')
  next()
})
audio.addEventListener('waiting', () => {
  // console.log('[audio event] waiting');
  loading.value = true
})
audio.addEventListener('seeking', () => {
  // console.log('[audio event] seeking');
  seekInProgress = true
  loading.value = true
})
audio.addEventListener('seeked', () => {
  // console.log('[audio event] seeked');
  seekInProgress = false
  loading.value = false
  if (!suppressTimeUpdate.value) {
    currentTime.value = audio.currentTime
  }
})
audio.addEventListener('timeupdate', () => {
  if (seekInProgress) return
  // console.log('[audio event] timeupdate', { currentTime: audio.currentTime, duration: audio.duration });
  if (!suppressTimeUpdate.value) {
    currentTime.value = audio.currentTime
  }
  if (!duration.value && audio.duration) {
    duration.value = audio.duration || 0
  }
})
audio.addEventListener('loadedmetadata', () => {
  // console.log('[audio event] loadedmetadata', { duration: audio.duration });
  if (!duration.value && audio.duration) {
    duration.value = audio.duration
  }
})
audio.addEventListener('loadstart', () => {
  // console.log('[audio event] loadstart');
  loading.value = true
})

audio.addEventListener('canplay', () => {
  // console.log('[audio event] canplay', { resumePosition: resumePosition.value, currentTime: currentTime.value, awaitingSeekResume: awaitingSeekResume.value, currentMedia: currentMedia.value });
  loading.value = false

  if (awaitingSeekResume.value && resumePosition.value > 0) {
    audio.currentTime = resumePosition.value
    currentTime.value = resumePosition.value
    awaitingSeekResume.value = false
    resumePosition.value = 0
  }

  audio.play().catch((err) => {
    console.warn('Resume playback after seek failed:', err)
  })
})

audio.addEventListener('error', () => {
  // console.log('[audio event] error');
  loading.value = false
})

watch(currentMedia, (_newMedia, oldMedia) => {
  if (oldMedia && oldMedia.type === 'podcast') {
    trackAction(oldMedia, 'pause')
  }
})

function usePlayback() {
  return {
    audio,
    isPlaying,
    currentMedia,
    queue,
    loading,
    currentIndex,
    currentTime,
    duration,
    playMedia,
    addToQueue,
    addAsNext,
    removeFromQueue,
    playFromQueue,
    overwriteQueue,
    clearQueue,
    playIndex,
    next,
    prev,
    togglePlay,
    pause,
    setSeek,
    trackAction,
  }
}

export const playback = usePlayback()
export default playback
