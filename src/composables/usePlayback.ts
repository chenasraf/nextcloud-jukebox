import { ref } from 'vue'
import type { Media } from '@/models/media'
import { axios } from '@/axios'

const audio = new Audio()
const isPlaying = ref(false)
const currentMedia = ref<Media | null>(null)
const queue = ref<Media[]>([])

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

function next() {
  if (queue.value.length > 0) {
    const nextTrack = queue.value.shift()!
    play(nextTrack)
  } else {
    console.warn('No more tracks in the queue')
  }
}

function prev() {
  // rewind to start if song progress < 2 seconds, otherwise go back 1 song
  if (audio.currentTime < 2) {
    if (currentMedia.value && queue.value.length > 0) {
      const previousTrack = queue.value.shift()!
      play(previousTrack)
    } else {
      console.warn('No previous track available')
    }
  } else {
    audio.currentTime = 0
  }
}

function addToQueue(media: Media | Media[]) {
  if (Array.isArray(media)) {
    queue.value.push(...media)
  } else {
    queue.value.push(media)
  }
}

function addAsNext(media: Media) {
  queue.value.splice(0, 0, media)
}

function clearQueue() {
  queue.value = []
}

function overwriteQueue(newQueue: Media[], startImmediately = true) {
  queue.value = [...newQueue]
  if (startImmediately && queue.value.length > 0) {
    play(queue.value.shift()!)
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
  if (queue.value.length > 0) {
    const nextTrack = queue.value.shift()!
    play(nextTrack)
  }
})

function usePlayback() {
  return {
    play,
    pause,
    togglePlay,
    next,
    prev,
    isPlaying,
    currentMedia,
    queue,
    addToQueue,
    addAsNext,
    clearQueue,
    overwriteQueue,
  }
}

const playback = usePlayback()

export default playback
