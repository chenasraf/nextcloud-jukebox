<template>
  <div class="album-list-item">
    <div ref="albumInfoRef" class="album-info" @click="playAll">
      <img v-if="album.cover" :src="album.cover" alt="Cover" width="128" height="128" class="cover" />
      <Music v-else :size="128" />
      <div class="metadata">
        <div class="title">{{ album.album || 'Untitled Album' }}</div>
        <div class="artist">{{ album.albumArtist || 'Unknown Artist' }}</div>
        <div class="year" v-if="album.year">{{ album.year }}</div>
      </div>
    </div>

    <div class="track-list-wrapper">
      <div ref="trackListRef" class="track-list" :style="trackListStyle">
        <ul>
          <li v-for="(track, index) in album.tracks" :key="track.id"
            :class="['track', { active: activeId === track.id }]">
            <a href="#" @click.prevent.stop="playTrack(index)">
              {{ track.trackNumber || index + 1 }}. {{ track.title || 'Untitled Track' }}
            </a>
          </li>
        </ul>
      </div>

      <div v-if="album.tracks.length > 4" :class="['collapse-toggle', { expanded: !collapsed }]">
        <button class="button" @click="toggleCollapse">
          {{ collapsed ? 'Show more' : 'Show less' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted, nextTick, type PropType, watch } from 'vue'
import { type Media } from '@/models/media'

import Music from '@icons/Music.vue'
import playback from '@/composables/usePlayback'

export interface Album {
  album: string
  albumArtist: string
  year: number | null
  cover: string | null
  tracks: Media[]
}

export default defineComponent({
  name: 'AlbumListItem',
  props: {
    album: {
      type: Object as PropType<Album>,
      required: true,
    },
  },
  components: {
    Music,
  },
  setup(props) {
    const { overwriteQueue, currentMedia } = playback

    const collapsed = ref(true)
    const collapsedHeight = ref<number>(128)

    const albumInfoRef = ref<HTMLElement | null>(null)
    const trackListRef = ref<HTMLElement | null>(null)

    const updateCollapsedHeight = () => {
      if (albumInfoRef.value) {
        collapsedHeight.value = albumInfoRef.value.getBoundingClientRect().height
      }
    }

    const toggleCollapse = () => {
      collapsed.value = !collapsed.value
    }

    const playAll = () => {
      overwriteQueue([...props.album.tracks], 0)
    }

    const playTrack = (index: number) => {
      overwriteQueue([...props.album.tracks], index)
    }

    const trackListStyle = computed(() => {
      return {
        maxHeight: collapsed.value ? `${collapsedHeight.value}px` : '1000px',
        transition: 'max-height 0.4s ease',
        overflow: 'hidden',
        maskImage: collapsed.value ? 'linear-gradient(to bottom, black 70%, transparent)' : '',
        WebkitMaskImage: collapsed.value ? 'linear-gradient(to bottom, black 70%, transparent)' : '',
      }
    })

    onMounted(() => {
      nextTick(() => updateCollapsedHeight())
    })

    watch(collapsed, (val) => {
      if (val) updateCollapsedHeight()
    })

    return {
      collapsed,
      toggleCollapse,
      playAll,
      playTrack,
      albumInfoRef,
      trackListRef,
      trackListStyle,
      activeId: computed(() => currentMedia.value?.id)
    }
  },
})
</script>

<style scoped lang="scss">
.album-list-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  border-bottom: 1px solid var(--color-border);
  padding: 1rem;
}

.album-info {
  min-width: 144px;
  display: flex;
  flex-direction: column;
  align-items: start;
  cursor: pointer;
  transition: background 0.15s;
  border-radius: var(--border-radius-element);
  padding: 0.75rem;

  &:hover {
    background-color: var(--color-background-hover);
  }
}

.cover {
  border-radius: 8px;
  object-fit: cover;
}

.metadata {
  margin-top: 0.5rem;

  .title {
    font-weight: bold;
    font-size: 1.1rem;
  }

  .artist {
    color: var(--color-text-light);
  }

  .year {
    font-size: 0.85rem;
    color: var(--color-text-maxcontrast);
  }
}

.track-list-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
}

.track-list {
  ul {
    padding: 0;
    margin: 0;
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
}

.track {
  a {
    display: block;
    padding: 0.25rem 0.5rem;
    border-radius: var(--border-radius-element);
    text-decoration: none;
    color: inherit;
    transition: background 0.15s;

    &:hover {
      background-color: var(--color-background-hover);
    }
  }

  &.active a {
    background-color: var(--color-primary-element);
  }
}

.collapse-toggle {
  margin-top: -2.5rem;
  text-align: right;
  z-index: 1;
  transition: margin-top 0.2s ease;

  &.expanded {
    margin-top: 0.5rem;
  }

  .button {
    background: none;
    border: none;
    color: var(--color-primary);
    cursor: pointer;
    font-weight: bold;
    padding: 0.25rem 0.5rem;

    &:hover {
      text-decoration: underline;
    }
  }
}
</style>
