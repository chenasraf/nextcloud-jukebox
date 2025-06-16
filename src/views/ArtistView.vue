<template>
  <Page :loading="isLoading">
    <template #title>
      {{ artist?.name || 'Artist' }}
    </template>

    <div v-if="artist" class="artist-info">
      <img v-if="artist.cover" :src="artist.cover" alt="Artist Cover" class="cover" />
      <Music v-else :size="100" />
      <div class="meta">
        <h2>{{ artist.name }}</h2>
        <div class="details">
          <p v-if="artist.genre">Genre: {{ artist.genre }}</p>
        </div>
      </div>
    </div>

    <div v-if="artist?.albums?.length" class="album-list">
      <AlbumCardItem v-for="album in artist.albums" :key="`${album.albumArtist}|${album.album}`" width="220px"
        :album="album" />
    </div>

    <div v-if="artist">
      <TrackListItem v-for="track in artist.tracks" :key="track.id" :media="track" media-type="track"
        @play="handlePlay(track)" />
    </div>
  </Page>
</template>

<script lang="ts">
import { defineComponent, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { axios } from '@/axios'
import type { Track, Artist } from '@/models/media'

import Page from '@/components/Page.vue'
import TrackListItem from '@/components/media/TrackListItem.vue'
import AlbumCardItem from '@/components/media/AlbumCardItem.vue'
import Music from '@icons/Music.vue'
import playback, { trackToPlayable } from '@/composables/usePlayback'

export default defineComponent({
  name: 'ArtistView',
  components: { Page, TrackListItem, AlbumCardItem, Music },
  setup() {
    const route = useRoute()
    const artist = ref<Artist | null>(null)
    const isLoading = ref(true)
    const { overwriteQueue } = playback

    onMounted(async () => {
      const id = decodeURIComponent(route.params.id as string)
      try {
        const res = await axios.get(`/music/artists/${id}`)
        artist.value = res.data
      } catch (err) {
        console.error('Failed to load artist:', err)
      } finally {
        isLoading.value = false
      }
    })

    const handlePlay = (track: Track) => {
      if (artist.value) {
        const index = artist.value.tracks.findIndex((t) => t.id === track.id)
        if (index !== -1) {
          overwriteQueue(artist.value.tracks.map(trackToPlayable), index)
        }
      }
    }

    return {
      artist,
      isLoading,
      handlePlay,
    }
  },
})
</script>

<style scoped lang="scss">
.artist-info {
  display: flex;
  align-items: center;
  margin-bottom: 1em;

  .cover {
    width: 100px;
    height: 100px;
    object-fit: cover;
    margin-right: 1em;
  }

  .meta {
    h2 {
      margin: 0;
      font-size: 1.5em;
    }

    .details {
      display: flex;

      p {
        padding: 0 0.5em;

        &:not(:last-child) {
          border-right: 1px solid currentcolor;
        }

        &:first-child {
          padding-left: 0;
        }
      }
    }
  }
}

.album-list {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1em;
}
</style>
