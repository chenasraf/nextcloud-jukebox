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
        <NcAppNavigationItem
          name="Artists"
          :to="{ path: '/artists' }"
          :active="isPrefixRoute('/artists')">
          <template #icon>
            <AccountMusic :size="20" />
          </template>
        </NcAppNavigationItem>
        <NcAppNavigationItem name="Genres" :to="{ path: '/genres' }">
          <template #icon>
            <Tag :size="20" />
          </template>
        </NcAppNavigationItem>
        <NcAppNavigationItem
          name="Podcasts"
          :to="{ path: '/podcasts' }"
          :active="isPrefixRoute('/podcasts')">
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
        <NcAppNavigationItem
          name="Radio"
          :to="{ path: '/radio' }"
          :active="isPrefixRoute('/radio')">
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
      <MediaControls />
    </NcAppContent>
  </NcContent>
</template>

<script lang="ts">
  import { defineComponent, computed, ref, onBeforeUnmount, onBeforeMount } from 'vue'
  import { useRouter, useRoute } from 'vue-router'

  import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
  import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
  import NcAppNavigationSearch from '@nextcloud/vue/components/NcAppNavigationSearch'
  import NcAppContent from '@nextcloud/vue/components/NcAppContent'
  import NcContent from '@nextcloud/vue/components/NcContent'
  import NcButton from '@nextcloud/vue/components/NcButton'
  import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

  import MediaControls from '@/components/media/MediaControls.vue'
  import { type Track } from '@/models/media'

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
      MediaControls,
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
      const isRouterLoading = ref(true)

      const isPrefixRoute = (prefix: string) => route.path.startsWith(prefix)

      router.beforeEach(() => {
        isRouterLoading.value = true
      })
      router.afterEach(() => {
        isRouterLoading.value = false
      })

      const handleBeforeUnload = () => {
        if (playback.currentMedia.value && !playback.audio.paused) {
          playback.trackAction(playback.currentMedia.value, 'pause')
        }
      }

      onBeforeMount(() => {
        window.addEventListener('beforeunload', handleBeforeUnload)
      })

      onBeforeUnmount(() => {
        window.removeEventListener('beforeunload', handleBeforeUnload)
      })

      return {
        searchValue: '',
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
