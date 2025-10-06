<template>
  <Page :loading="isLoading">
    <template #title>
      {{ video?.title || 'Video' }}
    </template>

    <div v-if="video" class="video-container">
      <NcNoteCard v-if="showError" type="error">
        <h3>Video format not supported in {{ browserName }}</h3>
        <p>
          This {{ videoFileExtension.toUpperCase() }} file contains codecs that {{ browserName }} cannot play.
          <span v-if="isFirefox">Chrome may have better compatibility with this file format.</span>
        </p>
        <p>You can download the video to play it in a media player like VLC or MPV.</p>
        <div class="error-actions">
          <NcButton :href="streamUrl" :download="video.path.split('/').pop()" variant="primary" size="large">
            Download Video
          </NcButton>
          <NcButton v-if="isFirefox" :href="currentUrl" target="_blank" variant="secondary" size="large">
            Try in Chrome
          </NcButton>
        </div>
      </NcNoteCard>

      <div v-else>
        <NcNoteCard v-if="videoFileExtension === 'mkv'" type="warning">
          <p>
            <strong>Note:</strong> MKV files may have audio compatibility issues in web browsers.
            If you don't hear sound, the file likely uses an unsupported audio codec (e.g., AC3, DTS).
            Try downloading the video to play in VLC or MPV for full audio support.
          </p>
        </NcNoteCard>

        <div class="video-wrapper">
          <video ref="videoElement" class="video-js vjs-default-skin vjs-big-play-centered"
            :poster="video.thumbnail ?? undefined">
          </video>
        </div>
      </div>

      <div class="video-info">
        <h2>{{ video.title || 'Untitled' }}</h2>
        <div class="meta">
          <span v-if="video.width && video.height" class="resolution">
            {{ video.width }}×{{ video.height }}
          </span>
          <span v-if="video.year" class="year">{{ video.year }}</span>
          <span v-if="video.genre" class="genre">{{ video.genre }}</span>
          <span v-if="video.duration" class="duration">{{ formatDuration(video.duration) }}</span>
        </div>
      </div>
    </div>
  </Page>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, computed, watch, onUnmounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { axios } from '@/axios'
import Page from '@/components/Page.vue'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcButton from '@nextcloud/vue/components/NcButton'
import playback from '@/composables/usePlayback'
import type { Video } from '@/models/media'
import videojs from 'video.js'
import type Player from 'video.js/dist/types/player'
import 'video.js/dist/video-js.css'

export default defineComponent({
  name: 'VideoView',
  components: {
    Page,
    NcNoteCard,
    NcButton,
  },
  setup() {
    const route = useRoute()
    const video = ref<Video | null>(null)
    const isLoading = ref(true)
    const videoElement = ref<HTMLVideoElement | null>(null)
    const player = ref<Player | null>(null)
    const showError = ref(false)
    const { overwriteQueue, isPlaying, currentTime, setSeek, currentMedia } = playback

    const streamUrl = computed(() => {
      if (!video.value) return ''
      return `${axios.defaults.baseURL}/video/${video.value.id}/stream`
    })

    const videoMimeType = computed(() => {
      if (!video.value?.path) return 'video/mp4'
      const ext = video.value.path.split('.').pop()?.toLowerCase()
      const mimeTypes: Record<string, string> = {
        mp4: 'video/mp4',
        webm: 'video/webm',
        ogg: 'video/ogg',
        ogv: 'video/ogg',
        mkv: 'video/webm', // Try webm MIME type as webm is a subset of MKV
        avi: 'video/x-msvideo',
        mov: 'video/quicktime',
      }
      return mimeTypes[ext || ''] || 'video/mp4'
    })

    const videoFileExtension = computed(() => {
      if (!video.value?.path) return ''
      return video.value.path.split('.').pop()?.toLowerCase() || ''
    })

    const isFirefox = /Firefox/i.test(navigator.userAgent)
    const browserName = computed(() => {
      const ua = navigator.userAgent
      if (/Firefox/i.test(ua)) return 'Firefox'
      if (/Chrome/i.test(ua)) return 'Chrome'
      if (/Safari/i.test(ua)) return 'Safari'
      if (/Edge/i.test(ua)) return 'Edge'
      return 'your browser'
    })

    const currentUrl = computed(() => window.location.href)

    let isSyncing = false

    onMounted(async () => {
      const id = decodeURIComponent(route.params.id as string)

      try {
        const res = await axios.get(`/video/${id}`)
        video.value = res.data
        isLoading.value = false

        // Add video to queue
        if (video.value) {
          overwriteQueue([{ type: 'video', ...video.value }], 0)

          // Wait for DOM to update before initializing video.js
          await nextTick()

          // Initialize video.js player
          if (videoElement.value) {
            console.log('Initializing video.js player')

            player.value = videojs(videoElement.value, {
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

            console.log('Video.js player initialized:', player.value)

            // Set the source
            player.value.src({
              src: streamUrl.value,
              type: videoMimeType.value,
            })

            console.log('Video source set:', streamUrl.value, videoMimeType.value)

            // Event listeners
            player.value.on('play', () => {
              console.log('Video play event')
              if (!isPlaying.value && !isSyncing) {
                isSyncing = true
                playback.togglePlay()
                setTimeout(() => { isSyncing = false }, 100)
              }
            })

            player.value.on('pause', () => {
              console.log('Video pause event')
              if (isPlaying.value && !isSyncing) {
                isSyncing = true
                playback.togglePlay()
                setTimeout(() => { isSyncing = false }, 100)
              }
            })

            player.value.on('ended', () => {
              console.log('Video ended event')
              playback.next()
            })

            player.value.on('timeupdate', () => {
              if (!isSyncing && player.value) {
                const time = player.value.currentTime()
                if (time !== undefined && Math.abs(currentTime.value - time) > 0.5) {
                  setSeek(time)
                }
              }
            })

            player.value.on('error', () => {
              const error = player.value?.error()
              console.error('Video.js error:', error)

              // Check for unsupported media errors
              if (error && (error.code === 4 || error.code === 3)) {
                // MEDIA_ERR_SRC_NOT_SUPPORTED (4) or MEDIA_ERR_DECODE (3)
                console.log('Media format not supported, showing error message')
                showError.value = true
              }
            })

            player.value.on('loadedmetadata', () => {
              console.log('Video metadata loaded')
            })

            // Auto-play
            const p = player.value
            p.ready(() => {
              console.log('Video.js ready, attempting auto-play')
              p.play()?.catch((err) => console.error('Auto-play failed:', err))
            })
          } else {
            console.error('Video element not found')
          }
        }
      } catch (err) {
        console.error('Failed to load video:', err)
        isLoading.value = false
      }
    })

    // Sync video.js player with playback state
    watch(isPlaying, (playing) => {
      if (!player.value || isSyncing) return
      const p = player.value
      const isPaused = p.paused()
      if (playing && isPaused) {
        p.play()?.catch((err) => console.error('Video play failed:', err))
      } else if (!playing && !isPaused) {
        p.pause()
      }
    }, { flush: 'post' })

    // Sync video currentTime when seeking from external controls
    let lastExternalSeek = 0
    watch(currentTime, (time) => {
      if (!player.value || isSyncing) return
      const currentPlayerTime = player.value.currentTime()
      if (currentPlayerTime === undefined) return
      const diff = Math.abs(currentPlayerTime - time)
      // Only sync if difference is significant (more than 1 second) to avoid feedback loops
      if (diff > 1 && Date.now() - lastExternalSeek > 500) {
        player.value.currentTime(time)
        lastExternalSeek = Date.now()
      }
    }, { flush: 'post' })

    // When current media changes away from this video, pause it
    watch(currentMedia, (media) => {
      if (!player.value) return
      if (!media || media.type !== 'video' || media.id !== video.value?.id) {
        player.value.pause()
      }
    }, { flush: 'post' })

    const formatDuration = (seconds: number): string => {
      const hours = Math.floor(seconds / 3600)
      const minutes = Math.floor((seconds % 3600) / 60)
      const secs = seconds % 60

      if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
      }
      return `${minutes}:${secs.toString().padStart(2, '0')}`
    }

    onUnmounted(() => {
      // Dispose video.js player
      if (player.value) {
        player.value.dispose()
        player.value = null
      }
    })

    return {
      video,
      isLoading,
      streamUrl,
      videoMimeType,
      videoFileExtension,
      videoElement,
      showError,
      isFirefox,
      browserName,
      currentUrl,
      formatDuration,
    }
  },
})
</script>

<style scoped lang="scss">
.video-container {
  max-width: 1200px;
  margin: 0 auto;

  .video-wrapper {
    width: 100%;
    max-width: 100%;
    margin-bottom: 1.5rem;
    background-color: #000;
    border-radius: 8px;
    overflow: hidden;
  }

  .error-actions {
    display: flex;
    gap: 1rem;
    /* justify-content: center; */
    margin-top: 1rem;
    margin-bottom: 1.5rem;
  }

  .video-info {
    margin-top: 1.5rem;

    h2 {
      margin: 0 0 0.5rem 0;
      font-size: 1.5rem;
    }

    .meta {
      display: flex;
      gap: 1rem;
      font-size: 0.9rem;
      color: var(--color-text-maxcontrast);

      span:not(:last-child)::after {
        content: '•';
        margin-left: 1rem;
      }
    }
  }
}
</style>

<style lang="scss">
// Global video.js styles (not scoped)
.video-js {
  width: 100% !important;
  height: auto !important;
}
</style>
