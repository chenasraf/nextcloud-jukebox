<template>
  <NcContent app-name="jukebox">
    <NcAppNavigation>
      <template #search>
        <NcAppNavigationSearch v-model="searchValue" label="Search…" />
      </template>
      <template #list>
        <NcAppNavigationItem name="Tracks" :to="{ path: '/tracks' }">
          <template #icon>
            <Music :size="20" />
          </template>
        </NcAppNavigationItem>
        <NcAppNavigationItem
          name="Albums"
          :to="{ path: '/albums' }"
          :active="isPrefixRoute('/albums')">
          <template #icon>
            <Album :size="20" />
          </template>
        </NcAppNavigationItem>
        <NcAppNavigationItem name="Artists" :to="{ path: '/artists' }">
          <template #icon>
            <AccountMusic :size="20" />
          </template>
        </NcAppNavigationItem>
        <NcAppNavigationItem name="Genres" :to="{ path: '/genres' }">
          <template #icon>
            <Tag :size="20" />
          </template>
        </NcAppNavigationItem>
        <NcAppNavigationItem name="Podcasts" :to="{ path: '/podcasts' }">
          <template #icon>
            <Podcast :size="20" />
          </template>
        </NcAppNavigationItem>
        <NcAppNavigationItem name="Audiobooks" :to="{ path: '/audiobooks' }">
          <template #icon>
            <Book :size="20" />
          </template>
        </NcAppNavigationItem>
        <NcAppNavigationItem name="Videos" :to="{ path: '/videos' }">
          <template #icon>
            <Filmstrip :size="20" />
          </template>
        </NcAppNavigationItem>
        <NcAppNavigationItem name="Radio" :to="{ path: '/radio' }">
          <template #icon>
            <RadioTower :size="20" />
          </template>
        </NcAppNavigationItem>
      </template>
      <template #footer> <!-- Add footer controls if needed --> </template>
    </NcAppNavigation>
    <NcAppContent id="jukebox-main">
      <div id="jukebox-router">
        <div v-if="isRouterLoading" class="router-loading">
          <NcLoadingIcon :size="64" />
        </div>
        <router-view v-else />
      </div>
      <!-- Media Player -->
      <footer class="jukebox-player">
        <div class="controls">
          <NcButton variant="tertiary" aria-label="Previous" size="normal" @click="playback.prev">
            <template #icon>
              <SkipPrevious :size="20" />
            </template>
          </NcButton>
          <NcButton
            class="play-button"
            variant="primary"
            aria-label="Play/Pause"
            size="normal"
            @click="playback.togglePlay">
            <template #icon>
              <Play :size="24" v-if="!isPlaying" />
              <Pause :size="24" v-else />
            </template>
          </NcButton>
          <NcButton variant="tertiary" aria-label="Next" size="normal" @click="playback.next">
            <template #icon>
              <SkipNext :size="20" />
            </template>
          </NcButton>
          <QueuePopover
            :shown="showQueue"
            :queue="queue"
            @close="showQueue = false"
            @play="handlePlay">
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
          <input
            type="range"
            min="0"
            max="100"
            :value="seek"
            @input="setSeek(Number(($event.target as HTMLInputElement).value))"
            class="seekbar" />
          <span class="time">{{ formattedDuration }}</span>
        </div>
      </footer>
    </NcAppContent>
  </NcContent>
</template>

<script lang="ts">
  import { defineComponent, computed, ref } from 'vue'
  import { useRouter, useRoute } from 'vue-router'

  import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
  import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
  import NcAppNavigationSearch from '@nextcloud/vue/components/NcAppNavigationSearch'
  import NcAppContent from '@nextcloud/vue/components/NcAppContent'
  import NcContent from '@nextcloud/vue/components/NcContent'
  import NcButton from '@nextcloud/vue/components/NcButton'
  import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

  import QueuePopover from '@/components/media/QueuePopover.vue'
  import { type Media } from '@/models/media'

  import SkipPrevious from '@icons/SkipPrevious.vue'
  import SkipNext from '@icons/SkipNext.vue'
  import Play from '@icons/Play.vue'
  import Pause from '@icons/Pause.vue'
  import Music from '@icons/Music.vue'
  import Album from '@icons/Album.vue'
  import AccountMusic from '@icons/AccountMusic.vue'
  import Podcast from '@icons/Podcast.vue'
  import Book from '@icons/Book.vue'
  import Filmstrip from '@icons/Filmstrip.vue'
  import Tag from '@icons/Tag.vue'
  import PlaylistMusic from '@icons/PlaylistMusic.vue'
  import RadioTower from '@icons/RadioTower.vue'

  import playback from '@/composables/usePlayback'

  export default defineComponent({
    name: 'App',
    components: {
      NcContent,
      NcAppContent,
      NcAppNavigation,
      NcAppNavigationItem,
      NcAppNavigationSearch,
      NcLoadingIcon,
      NcButton,
      QueuePopover,
      SkipPrevious,
      SkipNext,
      Play,
      Pause,
      Music,
      Album,
      AccountMusic,
      PlaylistMusic,
      Podcast,
      Book,
      Filmstrip,
      Tag,
      RadioTower,
    },
    provide() {
      return {
        'NcContent:setHasAppNavigation': () => true,
      }
    },
    setup() {
      const router = useRouter()
      const route = useRoute()
      const isPrefixRoute = (prefix: string) => route.path.startsWith(prefix)
      const isRouterLoading = ref(true)

      router.beforeEach(() => (isRouterLoading.value = true))
      router.afterEach(() => (isRouterLoading.value = false))

      const showQueue = ref(false)

      const toggleQueue = () => {
        showQueue.value = !showQueue.value
      }

      const onPlayFromQueue = (media: Media) => {
        playback.play(media)
        showQueue.value = false
      }

      function formatTime(seconds: number): string {
        const m = Math.floor(seconds / 60)
        const s = Math.floor(seconds % 60)
        return `${m}:${s.toString().padStart(2, '0')}`
      }

      const formattedCurrentTime = computed(() => formatTime(playback.currentTime.value))
      const formattedDuration = computed(() => formatTime(playback.duration.value))

      return {
        searchValue: '',
        seek: computed(() => playback.seek.value),
        setSeek: playback.setSeek,
        playback,
        queue: computed(() => playback.queue.value),
        isPlaying: computed(() => playback.isPlaying.value),
        showQueue,
        toggleQueue,
        onPlayFromQueue,
        formattedCurrentTime,
        formattedDuration,
        isRouterLoading,
        isPrefixRoute,
      }
    },
  })
</script>

<style scoped lang="scss">
  #jukebox-main {
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow: hidden;
}

#jukebox-router {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
}

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
    gap: 1rem;
    align-items: center;

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

.router-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
}
</style>
