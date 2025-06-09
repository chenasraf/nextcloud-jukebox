<template>
  <div class="album-list-item">
    <div ref="albumCardRef">
      <AlbumCardItem :album="album" width="256px" class="album-info" />
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
import type { Album } from '@/models/media'
import { useRouter } from 'vue-router'

import AlbumCardItem from '@/components/media/AlbumCardItem.vue'
import playback from '@/composables/usePlayback'

export default defineComponent({
  name: 'AlbumListItem',
  props: {
    album: {
      type: Object as PropType<Album>,
      required: true,
    },
  },
  components: {
    AlbumCardItem,
  },
  setup(props) {
    const { overwriteQueue, currentMedia } = playback
    const router = useRouter()
    const albumCardRef = ref<HTMLElement | null>(null)

    const collapsed = ref(true)
    const collapsedHeight = ref<number>(128)
    const expandedHeight = ref(1000)

    const trackListRef = ref<HTMLElement | null>(null)

    const updateHeights = () => {
      if (albumCardRef.value) {
        collapsedHeight.value = albumCardRef.value.offsetHeight
      }

      if (trackListRef.value) {
        expandedHeight.value = trackListRef.value.scrollHeight
      }
    }

    const toggleCollapse = () => {
      collapsed.value = !collapsed.value
    }

    const playTrack = (index: number) => {
      overwriteQueue([...props.album.tracks], index)
    }

    const trackListStyle = computed(() => {
      return {
        maxHeight: collapsed.value ? `${collapsedHeight.value}px` : `${expandedHeight.value}px`,
        transition: 'max-height 0.4s ease',
        overflow: 'hidden',
        maskImage: collapsed.value ? 'linear-gradient(to bottom, black 70%, transparent)' : '',
        WebkitMaskImage: collapsed.value ? 'linear-gradient(to bottom, black 70%, transparent)' : '',
      }
    })

    onMounted(() => {
      nextTick(() => updateHeights())
    })

    watch(collapsed, () => {
      nextTick(() => updateHeights())
    })


    return {
      collapsed,
      toggleCollapse,
      playTrack,
      trackListRef,
      trackListStyle,
      albumCardRef,
      activeId: computed(() => currentMedia.value?.id),
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
