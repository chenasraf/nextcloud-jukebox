<template>
  <Page :loading="isLoading">
    <template #title>
      Albums
    </template>

    <AlbumListItem v-for="album in albums" :key="album.album + '|' + album.albumArtist" :album="album"
      @play="handlePlay" />
  </Page>
</template>

<script lang="ts">
import { defineComponent, onMounted, ref } from 'vue'
import { axios } from '@/axios'
import { type Media } from '@/models/media'

import AlbumListItem from '@/components/media/AlbumListItem.vue'
import Page from '@/components/Page.vue'
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
  components: { AlbumListItem, Page },
  setup() {
    const albums = ref<Album[]>([])
    const isLoading = ref(true)
    const { overwriteQueue } = playback

    onMounted(async () => {
      try {
        const res = await axios.get('/albums')
        albums.value = res.data.albums
      } catch (err) {
        console.error('Failed to load albums:', err)
      } finally {
        isLoading.value = false
      }
    })

    const handlePlay = (track: Media) => {
      for (const album of albums.value) {
        const index = album.tracks.findIndex(t => t.id === track.id)
        if (index !== -1) {
          overwriteQueue([...album.tracks], index)
          return
        }
      }
      console.warn('Track not found in albums:', track)
    }

    return {
      albums,
      isLoading,
      handlePlay,
    }
  },
})
</script>

<style scoped lang="scss">
/* Page component handles layout, no need for separate .albums-view styles */
</style>
