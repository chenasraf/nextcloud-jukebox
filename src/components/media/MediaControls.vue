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
      <span class="time">{{ formattedCurrentTime }}</span>
      <input type="range" min="0" max="100" :value="seek" @input="onSeek" class="seekbar" :disabled="isLoading" />
      <span class="time">{{ formattedDuration }}</span>
    </div>
  </footer>
</template>

<script lang="ts">
import { defineComponent, ref, computed } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import QueuePopover from '@/components/media/QueuePopover.vue'
import playback from '@/composables/usePlayback'

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

    function toggleQueue() {
      if (!showQueue.value) {
        showQueue.value = true
      }
    }

    function formatTime(seconds: number): string {
      const m = Math.floor(seconds / 60)
      const s = Math.floor(seconds % 60)
      return `${m}:${s.toString().padStart(2, '0')}`
    }

    const queue = computed(() => playback.queue.value as unknown[] as Track[])
    const isPlaying = computed(() => playback.isPlaying.value)
    const seek = computed(() => playback.seek.value)
    const formattedCurrentTime = computed(() => formatTime(playback.currentTime.value))
    const formattedDuration = computed(() => formatTime(playback.duration.value))

    const isLoading = computed(() => playback.loading.value)

    function onSeek(event: Event) {
      const target = event.target as HTMLInputElement
      playback.setSeek(Number(target.value))
    }

    return {
      showQueue,
      toggleQueue,
      playback,
      queue,
      isPlaying,
      seek,
      formattedCurrentTime,
      formattedDuration,
      onSeek,
      isLoading,
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
  flex: 1;
}

.time {
  font-size: 0.85rem;
  width: 3rem;
  text-align: center;
  color: var(--color-text-light);
}
</style>
