import { ref } from 'vue'
import type { Media } from '@/models/media'
import { axios } from '@/axios'

const audio = new Audio()
const isPlaying = ref(false)
const currentMedia = ref<Media | null>(null)

function play(media: Media) {
  if (currentMedia.value?.id !== media.id) {
    audio.src = axios.defaults.baseURL + `/tracks/${media.id}/stream`
    audio.load()
    currentMedia.value = media
  }

  audio
    .play()
    .then(() => {
      isPlaying.value = true
    })
    .catch(err => {
      console.error('Playback failed:', err)
      isPlaying.value = false
    })
}

function pause() {
  audio.pause()
}

function togglePlay() {
  if (audio.paused) {
    audio
      .play()
      .then(() => {
        isPlaying.value = true
      })
      .catch(err => {
        console.error('Toggle play failed:', err)
      })
  } else {
    audio.pause()
  }
}

audio.addEventListener('play', () => {
  isPlaying.value = true
})

audio.addEventListener('pause', () => {
  isPlaying.value = false
})

audio.addEventListener('ended', () => {
  isPlaying.value = false
})

export function usePlayback() {
  return {
    play,
    pause,
    togglePlay,
    isPlaying,
    currentMedia,
  }
}

export default usePlayback
