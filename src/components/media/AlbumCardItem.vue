<template>
  <div class="album-card" :style="{ width }" @click="goToAlbum(album)">
    <img v-if="album.cover" :src="album.cover" alt="Cover" width="128" height="128" class="cover" />
    <Music v-else :size="128" />
    <div class="metadata">
      <div class="title">{{ album.album || 'Untitled Album' }}</div>
      <div class="artist">{{ album.albumArtist || 'Unknown Artist' }}</div>
      <div class="year" v-if="album.year">{{ album.year }}</div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import type { Album } from '@/models/media'
import { useRouter } from 'vue-router'

import Music from '@icons/Music.vue'

export default defineComponent({
  name: 'AlbumCardItem',
  props: {
    album: {
      type: Object as PropType<Album>,
      required: true,
    },
    width: {
      type: String,
      default: '128px',
    },
  },
  components: {
    Music,
  },
  setup(props) {
    const router = useRouter()

    const goToAlbum = (album: Album) => {
      const id = btoa(unescape(encodeURIComponent(`${album.albumArtist}|${album.album}`)))
      router.push(`/albums/${id}`)
    }

    return { goToAlbum, width: props.width }
  },
})
</script>

<style scoped lang="scss">
.album-card {
  cursor: pointer;
  padding: 0.75rem;
  border-radius: var(--border-radius-element);
  display: flex;
  flex-direction: column;
  align-items: start;
  transition: background 0.15s;

  &:hover {
    background-color: var(--color-background-hover);
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
}
</style>
