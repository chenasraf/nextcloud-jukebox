<template>
  <div class="albums-view">
    <PageTitle>
      Albums
    </PageTitle>
    <AlbumListItem v-for="album in albums" :key="album.album + '|' + album.albumArtist" :album="album"
      @play="handlePlay" />
  </div>
</template>

<script lang="ts">
import { defineComponent, onMounted, ref } from 'vue'
import { axios } from '@/axios'
import { type Media } from '@/models/media'

import AlbumListItem from '@/components/media/AlbumListItem.vue'
import PageTitle from '@/components/PageTitle.vue'

import playback from '@/composables/usePlayback'

export interface Album {
  album: string
  albumArtist: string
  year: number | null
  cover: string | null
  tracks: Media[]
}

export default defineComponent({
  name: 'AlbumsView',
  components: { AlbumListItem, PageTitle },
  setup() {
    const albums = ref<Album[]>([])
    const { overwriteQueue } = playback

    onMounted(async () => {
      try {
        const res = await axios.get('/albums')
        albums.value = res.data.albums
      } catch (err) {
        console.error('Failed to load albums:', err)
      }
    })

    const handlePlay = (track: Media) => {
      for (const album of albums.value) {
        const index = album.tracks.findIndex(t => t.id === track.id)
        if (index !== -1) {
          console.debug('[AlbumsView] Overwriting queue with album:', album.album, 'starting at track', index)
          overwriteQueue([...album.tracks], index)
          return
        }
      }
      console.warn('Track not found in current albums:', track)
    }

    return {
      albums,
      handlePlay,
    }
  },
})
</script>

<style lang="scss">
.albums-view {
  display: flex;
  flex-direction: column;
  height: 100%;
}
</style>
