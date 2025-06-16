<template>
  <footer class="jukebox-player">
    <div class="controls">
      <NcButton variant="tertiary" aria-label="Previous" size="normal" @click="playback.prev" :disabled="isLoading">
        <template #icon>
          <SkipPrevious :size="20" />
        </template>
      </NcButton>

      <NcButton class="play-button" variant="primary" aria-label="Play/Pause" size="normal" @click="playback.togglePlay"
        :disabled="isLoading">
        <template #icon>
          <NcLoadingIcon v-if="isLoading" :size="24" />
          <Play v-else-if="!isPlaying" :size="24" />
          <Pause v-else :size="24" />
        </template>
      </NcButton>

      <NcButton variant="tertiary" aria-label="Next" size="normal" @click="playback.next" :disabled="isLoading">
        <template #icon>
          <SkipNext :size="20" />
        </template>
      </NcButton>

      <QueuePopover v-model:shown="showQueue" :queue="queue">
        <template #trigger>
          <NcButton variant="tertiary" aria-label="Queue" size="normal" @click="toggleQueue">
            <template #icon>
              <PlaylistMusic :size="20" />
            </template>
          </NcButton>
        </template>
      </QueuePopover>
    </div>

    <div class="seekbar-row">
      <span class="time">{{ displayedCurrentTime }}</span>
      <div ref="seekbarRef" class="seekbar" @pointerdown="startSeek">
        <div class="seekbar-fill" :style="{ width: (effectiveSeekPercent * 100) + '%' }"></div>
      </div>
      <span class="time">{{ formattedDuration }}</span>
    </div>
  </footer>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onBeforeUnmount } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import QueuePopover from '@/components/media/QueuePopover.vue'
import rawPlayback from '@/composables/usePlayback'
import { formatDuration } from '@/utils/time'

import SkipPrevious from '@icons/SkipPrevious.vue'
import SkipNext from '@icons/SkipNext.vue'
import Play from '@icons/Play.vue'
import Pause from '@icons/Pause.vue'
import PlaylistMusic from '@icons/PlaylistMusic.vue'

import type { Track } from '@/models/media'

export default defineComponent({
  name: 'MediaControls',
  components: {
    NcButton,
    NcLoadingIcon,
    QueuePopover,
    SkipPrevious,
    SkipNext,
    Play,
    Pause,
    PlaylistMusic,
  },
  setup() {
    const showQueue = ref(false)
    const isDragging = ref(false)
    const seekPercent = ref(0)
    const cachedDuration = ref(0)

    const seekbarRef = ref<HTMLElement | null>(null)
    let lastPointerX = 0

    const playback = {
      ...rawPlayback,
      queue: computed(() => rawPlayback.queue.value as unknown[] as Track[]),
      isPlaying: computed(() => rawPlayback.isPlaying.value),
      isLoading: computed(() => rawPlayback.loading.value),
      currentTime: computed(() => rawPlayback.currentTime.value),
      duration: computed(() => rawPlayback.duration.value),
    }

    const formattedCurrentTime = computed(() => formatDuration(playback.currentTime.value))
    const formattedDuration = computed(() => formatDuration(playback.duration.value))
    const displayedCurrentTime = computed(() =>
      isDragging.value
        ? formatDuration(seekPercent.value * cachedDuration.value)
        : formattedCurrentTime.value
    )
    const effectiveSeekPercent = computed(() =>
      isDragging.value ? seekPercent.value : (playback.currentTime.value / playback.duration.value)
    )

    function updateSeekPosition(event: PointerEvent) {
      if (!seekbarRef.value || playback.duration.value <= 0) return

      const rect = seekbarRef.value.getBoundingClientRect()
      const offsetX = event.clientX - rect.left
      const percent = Math.min(Math.max(offsetX / rect.width, 0), 1)
      seekPercent.value = percent
      lastPointerX = event.clientX
    }


    function startSeek(event: PointerEvent) {
      console.log('Seek start')
      isDragging.value = true
      cachedDuration.value = playback.duration.value
      updateSeekPosition(event)
      window.addEventListener('pointermove', updateSeekPosition)
      window.addEventListener('pointerup', stopSeek)
    }

    function applySeek() {
      if (cachedDuration.value <= 0) return
      const newTime = seekPercent.value * cachedDuration.value
      console.log(`Seek commit to ${newTime.toFixed(2)}s (of ${cachedDuration.value}s)`)
      playback.setSeek(newTime)
    }

    function stopSeek() {
      if (!isDragging.value) return
      console.log('Seek end')
      applySeek()
      isDragging.value = false
      window.removeEventListener('pointermove', updateSeekPosition)
      window.removeEventListener('pointerup', stopSeek)
    }

    onBeforeUnmount(() => {
      stopSeek()
    })

    return {
      showQueue,
      toggleQueue: () => { showQueue.value = !showQueue.value },
      playback,
      queue: playback.queue,
      isPlaying: playback.isPlaying,
      currentTime: playback.currentTime,
      duration: playback.duration,
      isLoading: playback.isLoading,
      formattedCurrentTime,
      displayedCurrentTime,
      formattedDuration,
      effectiveSeekPercent,
      startSeek,
      seekbarRef,
    }
  },
})
</script>

<style lang="scss">
.jukebox-player {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0.5rem;
  border-top: 1px solid var(--color-border);
  background: var(--color-background-light);
  z-index: 1;
  height: 160px;

  .controls {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 1rem;

    button {
      border-radius: 50%;
      height: var(--button-size) !important;
      width: var(--button-size) !important;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .play-button {
      --button-size: 3.5rem;
      font-size: 1.25rem;
    }
  }
}

.seekbar-row {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 0 1rem;
  gap: 0.5rem;
}

.seekbar {
  position: relative;
  width: 100%;
  height: 8px;
  border-radius: 4px;
  background: var(--color-border);
  cursor: pointer;
  flex: 1;
  user-select: none;
}

.seekbar-fill {
  position: absolute;
  height: 100%;
  left: 0;
  top: 0;
  border-radius: 4px;
  background: var(--color-primary);
  pointer-events: none;
}

.time {
  font-size: 0.85rem;
  width: 3rem;
  text-align: center;
  color: var(--color-text-light);
}
</style>
