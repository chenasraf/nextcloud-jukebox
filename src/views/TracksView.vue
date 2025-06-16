<template>
  <Page :loading="isLoading">
    <template #title>
      Tracks
    </template>

    <TrackListItem v-for="track in tracks" :key="track.id" :media="track" media-type="track" @play="handlePlay" />
  </Page>
</template>

<script lang="ts">
import { defineComponent, onMounted, ref } from 'vue'
import { axios } from '@/axios'
import { type Track } from '@/models/media'

import TrackListItem from '@/components/media/TrackListItem.vue'
import Page from '@/components/Page.vue'
import playback, { trackToPlayable } from '@/composables/usePlayback'

export default defineComponent({
  name: 'TracksView',
  components: { TrackListItem, Page },
  setup() {
    const tracks = ref<Track[]>([])
    const isLoading = ref(true)
    const { overwriteQueue } = playback

    onMounted(async () => {
      try {
        const res = await axios.get('/music/tracks')
        tracks.value = res.data.tracks
      } catch (err) {
        console.error('Failed to load tracks:', err)
      } finally {
        isLoading.value = false
      }
    })

    const handlePlay = (track: Track) => {
      const index = tracks.value.findIndex(t => t.id === track.id)
      if (index !== -1) {
        overwriteQueue(tracks.value.map(trackToPlayable), index)
      }
    }

    return {
      tracks,
      isLoading,
      handlePlay,
    }
  },
})
</script>
