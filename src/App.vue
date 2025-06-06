<template>
   <NcContent app-name="jukebox"
    > <NcAppNavigation
      > <template #search
        > <NcAppNavigationSearch v-model="searchValue" label="Search…" /> </template
      > <template #list
        > <NcAppNavigationItem name="Tracks" :to="{ path: '/tracks' }"
          > <template #icon> <Music :size="20" /> </template> </NcAppNavigationItem
        > <NcAppNavigationItem name="Albums" :to="{ path: '/albums' }"
          > <template #icon> <Album :size="20" /> </template> </NcAppNavigationItem
        > <NcAppNavigationItem name="Artists" :to="{ path: '/artists' }"
          > <template #icon> <AccountMusic :size="20" /> </template> </NcAppNavigationItem
        > <NcAppNavigationItem name="Podcasts" :to="{ path: '/podcasts' }"
          > <template #icon> <Podcast :size="20" /> </template> </NcAppNavigationItem
        > <NcAppNavigationItem name="Audiobooks" :to="{ path: '/audiobooks' }"
          > <template #icon> <Book :size="20" /> </template> </NcAppNavigationItem
        > <NcAppNavigationItem name="Videos" :to="{ path: '/videos' }"
          > <template #icon> <Filmstrip :size="20" /> </template> </NcAppNavigationItem
        > <NcAppNavigationItem name="Genres" :to="{ path: '/genres' }"
          > <template #icon> <Tag :size="20" /> </template> </NcAppNavigationItem
        > </template
      > <template #footer> <!-- Add footer controls if needed --> </template> </NcAppNavigation
    > <NcAppContent id="jukebox-main"
      >
      <div id="jukebox-router"> <router-view /> </div>
       <!-- Media Player -->
      <footer class="jukebox-player">

        <div class="controls">
           <NcButton
            :disabled="false"
            variant="tertiary"
            aria-label="Previous"
            size="normal"
            @click="prev"
            > <template #icon> <SkipPrevious :size="20" /> </template> </NcButton
          > <NcButton
            :disabled="false"
            variant="primary"
            aria-label="Play/Pause"
            size="normal"
            @click="togglePlay"
            > <template #icon
              > <Play :size="20" v-if="!isPlaying" /> <Pause :size="20" v-else /> </template
            > </NcButton
          > <NcButton
            :disabled="false"
            variant="tertiary"
            aria-label="Next"
            size="normal"
            @click="next"
            > <template #icon> <SkipNext :size="20" /> </template> </NcButton
          >
        </div>
         <input type="range" min="0" max="100" v-model="seek" class="seekbar" />
      </footer>
       </NcAppContent
    > </NcContent
  >
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcAppNavigationSearch from '@nextcloud/vue/components/NcAppNavigationSearch'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcButton from '@nextcloud/vue/components/NcButton'

import SkipPrevious from 'vue-material-design-icons/SkipPrevious.vue'
import SkipNext from 'vue-material-design-icons/SkipNext.vue'
import Play from 'vue-material-design-icons/Play.vue'
import Pause from 'vue-material-design-icons/Pause.vue'
import Music from 'vue-material-design-icons/Music.vue'
import Album from 'vue-material-design-icons/Album.vue'
import AccountMusic from 'vue-material-design-icons/AccountMusic.vue'
import Podcast from 'vue-material-design-icons/Podcast.vue'
import Book from 'vue-material-design-icons/Book.vue'
import Filmstrip from 'vue-material-design-icons/Filmstrip.vue'
import Tag from 'vue-material-design-icons/Tag.vue'

export default defineComponent({
  name: 'App',
  components: {
    NcContent,
    NcAppContent,
    NcAppNavigation,
    NcAppNavigationItem,
    NcAppNavigationSearch,
    NcButton,
    SkipPrevious,
    SkipNext,
    Play,
    Pause,
    Music,
    Album,
    AccountMusic,
    Podcast,
    Book,
    Filmstrip,
    Tag,
  },
  provide() {
    return {
      'NcContent:setHasAppNavigation': () => true,
    }
  },
  data() {
    return {
      searchValue: '',
      isPlaying: false,
      seek: 0,
    }
  },
  methods: {
    togglePlay() {
      this.isPlaying = !this.isPlaying
    },
    next() {
      // Placeholder
    },
    prev() {
      // Placeholder
    },
  },
})
</script>

<style scoped lang="scss">
#jukebox-main {
  display: flex;
  flex-direction: column;
}

#jukebox-router {
  flex: 1;
}

.jukebox-player {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0.5rem;
  border-top: 1px solid var(--color-border);
  background: var(--color-background-light);

  .controls {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.5rem;
  }
}
</style>

