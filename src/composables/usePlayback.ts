import { ref, computed } from 'vue'
import type { Media } from '@/models/media'
import { axios } from '@/axios'

const audio = new Audio()
const isPlaying = ref(false)
const queue = ref<Media[]>([])
const currentIndex = ref<number>(-1)
const currentTime = ref(0)
const duration = ref(0)
const seek = computed(() => (duration.value > 0 ? (currentTime.value / duration.value) * 100 : 0))

const currentMedia = computed(() => {
  return currentIndex.value >= 0 ? queue.value[currentIndex.value] ?? null : null
})

function play(media: Media) {
  const index = queue.value.findIndex(item => item.id === media.id)

  if (index !== -1) {
    currentIndex.value = index
  } else {
    queue.value.push(media)
    currentIndex.value = queue.value.length - 1
  }

  const newSrc = axios.defaults.baseURL + `/tracks/${media.id}/stream`

  if (audio.src !== newSrc) {
    audio.pause()
    audio.src = newSrc
    audio.load()
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

function playIndex(index: number) {
  if (queue.value[index]) {
    currentIndex.value = index
    play(queue.value[index])
  }
}

function next() {
  if (currentIndex.value + 1 < queue.value.length) {
    playIndex(currentIndex.value + 1)
  } else {
    console.warn('No next track in queue')
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
    audio
      .play()
      .then(() => (isPlaying.value = true))
      .catch(err => {
        console.error('Toggle play failed:', err)
      })
  } else {
    pause()
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
  if (currentIndex.value >= 0) {
    queue.value.splice(currentIndex.value + 1, 0, media)
  } else {
    queue.value.push(media)
  }
}

function clearQueue() {
  queue.value = []
  currentIndex.value = -1
}

function overwriteQueue(newQueue: Media[], startIndex = 0) {
  queue.value.splice(0, queue.value.length, ...newQueue)
  currentIndex.value = startIndex

  if (queue.value[startIndex]) {
    play(queue.value[startIndex])
  } else {
    console.warn('No valid track at startIndex', startIndex)
  }
}

function setSeek(percent: number) {
  const newTime = (Number(percent) / 100) * duration.value
  audio.currentTime = newTime
}

audio.addEventListener('play', () => {
  isPlaying.value = true
})

audio.addEventListener('pause', () => {
  isPlaying.value = false
})

audio.addEventListener('ended', () => {
  isPlaying.value = false
  next()
})

audio.addEventListener('timeupdate', () => {
  currentTime.value = audio.currentTime
  duration.value = audio.duration || 0
})

audio.addEventListener('loadedmetadata', () => {
  duration.value = audio.duration
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
    currentIndex,
    addToQueue,
    addAsNext,
    clearQueue,
    overwriteQueue,
    currentTime,
    duration,
    seek,
    setSeek,
  }
}

const playback = usePlayback()
export default playback
