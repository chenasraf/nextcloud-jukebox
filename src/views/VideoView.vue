<template>
  <Page :loading="isLoading">
    <template #title>
      {{ video?.title || 'Video' }}
    </template>

    <div v-if="video" class="video-container">
      <video
        ref="videoElement"
        class="video-player"
        controls
        :poster="video.thumbnail ?? undefined"
        @loadedmetadata="handleLoadedMetadata"
        @timeupdate="handleTimeUpdate"
        @play="handlePlay"
        @pause="handlePause"
        @ended="handleEnded">
        <source :src="streamUrl" :type="videoMimeType" />
        Your browser does not support the video tag.
      </video>

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
  import { defineComponent, ref, onMounted, computed, watch, onUnmounted } from 'vue'
  import { useRoute } from 'vue-router'
  import { axios } from '@/axios'
  import Page from '@/components/Page.vue'
  import playback from '@/composables/usePlayback'
  import type { Video } from '@/models/media'

  export default defineComponent({
    name: 'VideoView',
    components: {
      Page,
    },
    setup() {
      const route = useRoute()
      const video = ref<Video | null>(null)
      const isLoading = ref(true)
      const videoElement = ref<HTMLVideoElement | null>(null)
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
          mkv: 'video/x-matroska',
          avi: 'video/x-msvideo',
          mov: 'video/quicktime',
        }
        return mimeTypes[ext || ''] || 'video/mp4'
      })

      onMounted(async () => {
        const id = decodeURIComponent(route.params.id as string)

        try {
          const res = await axios.get(`/video/${id}`)
          video.value = res.data

          // Add video to queue and start playing
          if (video.value) {
            overwriteQueue([{ type: 'video', ...video.value }], 0)
            // Auto-play the video after a short delay to ensure element is ready
            setTimeout(() => {
              if (videoElement.value) {
                videoElement.value.play().catch((err) => console.error('Auto-play failed:', err))
              }
            }, 100)
          }
        } catch (err) {
          console.error('Failed to load video:', err)
        } finally {
          isLoading.value = false
        }
      })

      let isSyncing = false

      // Sync video element with playback state
      watch(isPlaying, (playing) => {
        if (!videoElement.value || isSyncing) return
        if (playing && videoElement.value.paused) {
          videoElement.value.play().catch((err) => console.error('Video play failed:', err))
        } else if (!playing && !videoElement.value.paused) {
          videoElement.value.pause()
        }
      }, { flush: 'post' })

      // Sync video currentTime when seeking from external controls
      let lastExternalSeek = 0
      watch(currentTime, (time) => {
        if (!videoElement.value || isSyncing) return
        const diff = Math.abs(videoElement.value.currentTime - time)
        // Only sync if difference is significant (more than 1 second) to avoid feedback loops
        if (diff > 1 && Date.now() - lastExternalSeek > 500) {
          videoElement.value.currentTime = time
          lastExternalSeek = Date.now()
        }
      }, { flush: 'post' })

      // When current media changes away from this video, pause it
      watch(currentMedia, (media) => {
        if (!videoElement.value) return
        if (!media || media.type !== 'video' || media.id !== video.value?.id) {
          videoElement.value.pause()
        }
      }, { flush: 'post' })

      const handleLoadedMetadata = () => {
        if (!videoElement.value) return
        // Video is ready to play
      }

      const handleTimeUpdate = () => {
        // Don't update during sync to avoid feedback loops
        if (isSyncing) return
        // Passively update the current time for display only
      }

      const handlePlay = () => {
        // Video started playing - sync with playback state
        if (!isPlaying.value) {
          isSyncing = true
          playback.togglePlay()
          setTimeout(() => { isSyncing = false }, 100)
        }
      }

      const handlePause = () => {
        // Video paused - sync with playback state
        if (isPlaying.value) {
          isSyncing = true
          playback.togglePlay()
          setTimeout(() => { isSyncing = false }, 100)
        }
      }

      const handleEnded = () => {
        // Video ended
        playback.next()
      }

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
        // Pause video when leaving the page
        if (videoElement.value) {
          videoElement.value.pause()
        }
      })

      return {
        video,
        isLoading,
        streamUrl,
        videoMimeType,
        videoElement,
        handleLoadedMetadata,
        handleTimeUpdate,
        handlePlay,
        handlePause,
        handleEnded,
        formatDuration,
      }
    },
  })
</script>

<style scoped lang="scss">
  .video-container {
  max-width: 1200px;
  margin: 0 auto;

  .video-player {
    width: 100%;
    max-height: 70vh;
    background-color: #000;
    border-radius: 8px;
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
