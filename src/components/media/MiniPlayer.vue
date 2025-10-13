<template>
  <div v-if="shouldShow" class="mini-player" @click="navigateToVideo">
    <div class="mini-player-video" @click.stop>
      <video ref="miniVideoElement" class="video-js vjs-default-skin"></video>
    </div>
    <div class="mini-player-info">
      <span class="mini-player-title">{{ videoTitle }}</span>
      <button class="mini-player-close" @click.stop="closePlayer" aria-label="Close mini player">
        ×
      </button>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, computed, ref, watch, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useVideoPlayer } from '@/composables/useVideoPlayer'
import playback from '@/composables/usePlayback'
import 'video.js/dist/video-js.css'

export default defineComponent({
  name: 'MiniPlayer',
  setup() {
    const router = useRouter()
    const route = useRoute()
    const { player, currentVideo, isInitialized, disposePlayer } = useVideoPlayer()
    const miniVideoElement = ref<HTMLVideoElement | null>(null)

    const isOnVideoPage = computed(() => {
      return route.path.startsWith('/videos/') && route.params.id
    })

    const shouldShow = computed(() => {
      return (
        isInitialized.value &&
        currentVideo.value &&
        playback.currentMedia.value?.type === 'video' &&
        !isOnVideoPage.value
      )
    })

    const videoTitle = computed(() => {
      return currentVideo.value?.title || 'Video'
    })

    const navigateToVideo = () => {
      if (currentVideo.value) {
        router.push(`/videos/${currentVideo.value.id}`)
      }
    }

    const closePlayer = () => {
      playback.pause()
      disposePlayer()
    }

    // Watch for when mini player should appear and move the player to it
    watch(shouldShow, async (show) => {
      if (show) {
        await nextTick()
        if (miniVideoElement.value && player.value) {
          // Save the playing state before moving
          const wasPlaying = !player.value.paused()
          const currentTime = player.value.currentTime()

          // Move the video.js player to the mini player element
          const playerEl = player.value.el()
          if (miniVideoElement.value.parentNode && playerEl) {
            miniVideoElement.value.parentNode.replaceChild(playerEl, miniVideoElement.value)
            miniVideoElement.value = playerEl.querySelector('video')

            // Restore playing state after move - always call play if it was playing
            if (wasPlaying) {
              // Wait for DOM to settle
              await nextTick()
              setTimeout(() => {
                if (player.value) {
                  player.value.currentTime(currentTime)
                  player.value.play().catch((err) => console.warn('Failed to resume play in mini player:', err))
                }
              }, 50)
            }
          }
        }
      }
    }, { flush: 'post' })

    return {
      miniVideoElement,
      shouldShow,
      videoTitle,
      navigateToVideo,
      closePlayer,
    }
  },
})
</script>

<style scoped lang="scss">
.mini-player {
  position: fixed;
  bottom: 125px; // Above the media controls
  right: 20px;
  width: 320px;
  background: var(--color-main-background);
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  overflow: hidden;
  z-index: 9999;
  cursor: pointer;
  transition: transform 0.2s ease;

  &:hover {
    transform: scale(1.02);
  }
}

.mini-player-video {
  width: 100%;
  aspect-ratio: 16 / 9;
  background: #000;
  position: relative;

  video {
    width: 100%;
    height: 100%;
  }
}

.mini-player-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 12px;
  background: var(--color-background-dark);
  gap: 8px;
}

.mini-player-title {
  flex: 1;
  font-size: 0.85rem;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: var(--color-main-text);
}

.mini-player-close {
  flex-shrink: 0;
  background: transparent;
  border: none;
  padding: 4px 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  color: var(--color-main-text);
  transition: background-color 0.2s ease;
  font-size: 20px;
  line-height: 1;
  font-weight: bold;

  &:hover {
    background: var(--color-background-hover);
  }
}
</style>

<style lang="scss">
// Adjust video.js styles for mini player
.mini-player {
  .video-js {
    width: 100% !important;
    height: 100% !important;

    .vjs-big-play-button {
      display: none;
    }
  }
}
</style>
