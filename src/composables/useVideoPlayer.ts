import { ref, computed } from 'vue'
import type Player from 'video.js/dist/types/player'
import type { Video } from '@/models/media'
import videojs from 'video.js'

const player = ref<Player | null>(null)
const currentVideo = ref<Video | null>(null)
const videoElement = ref<HTMLVideoElement | null>(null)
const isInitialized = ref(false)

export function useVideoPlayer() {
  const initializePlayer = (element: HTMLVideoElement, video: Video, streamUrl: string, mimeType: string) => {
    // Dispose existing player if any
    if (player.value) {
      player.value.dispose()
      player.value = null
    }

    videoElement.value = element
    currentVideo.value = video
    isInitialized.value = true

    player.value = videojs(element, {
      controls: true,
      responsive: true,
      aspectRatio: '16:9',
      preload: 'auto',
      html5: {
        vhs: {
          overrideNative: true,
        },
        nativeAudioTracks: false,
        nativeVideoTracks: false,
      },
    })

    player.value.src({
      src: streamUrl,
      type: mimeType,
    })

    return player.value
  }

  const disposePlayer = () => {
    if (player.value) {
      player.value.dispose()
      player.value = null
    }
    videoElement.value = null
    currentVideo.value = null
    isInitialized.value = false
  }

  const movePlayerToElement = (element: HTMLVideoElement) => {
    if (!player.value) return

    // Get the underlying native video element from video.js
    const nativeVideoElement = player.value.el().querySelector('video')
    if (nativeVideoElement) {
      // Detach from current parent and attach to new parent
      element.parentNode?.replaceChild(nativeVideoElement, element)
      videoElement.value = element
    }
  }

  return {
    player: computed(() => player.value),
    currentVideo: computed(() => currentVideo.value),
    videoElement: computed(() => videoElement.value),
    isInitialized: computed(() => isInitialized.value),
    initializePlayer,
    disposePlayer,
    movePlayerToElement,
  }
}

export default useVideoPlayer
