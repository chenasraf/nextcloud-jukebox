<template>
  <Page :loading="isLoading">
    <template #title>
      Artists
    </template>

    <ArtistListItem v-for="artist in artists" :key="artist.name" :artist="artist" @select="openArtistPage" />
  </Page>
</template>

<script lang="ts">
import { defineComponent, onMounted, ref } from 'vue'
import { axios } from '@/axios'
import { useRouter } from 'vue-router'
import type { Media, Artist } from '@/models/media'

import ArtistListItem from '@/components/media/ArtistListItem.vue'
import Page from '@/components/Page.vue'
import playback from '@/composables/usePlayback'

export default defineComponent({
  name: 'ArtistsView',
  components: { ArtistListItem, Page },
  setup() {
    const router = useRouter()
    const artists = ref<Artist[]>([])
    const isLoading = ref(true)
    const { overwriteQueue } = playback

    onMounted(async () => {
      try {
        const res = await axios.get('/music/artists')
        artists.value = res.data.artists
      } catch (err) {
        console.error('Failed to load artists:', err)
      } finally {
        isLoading.value = false
      }
    })

    const handlePlay = (track: Media) => {
      for (const artist of artists.value) {
        const index = artist.tracks.findIndex(t => t.id === track.id)
        if (index !== -1) {
          overwriteQueue([...artist.tracks], index)
          return
        }
      }
      console.warn('Track not found in artist list:', track)
    }

    const openArtistPage = (artist: Artist) => {
      const id = btoa(unescape(encodeURIComponent(artist.name)))
      router.push(`/artists/${id}`)
    }

    return {
      artists,
      isLoading,
      handlePlay,
      openArtistPage,
    }
  },
})
</script>

<style scoped lang="scss">
/* Page handles layout */
</style>
