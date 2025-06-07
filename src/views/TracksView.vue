<template>
  <Page :loading="isLoading">
    <template #title>
      Tracks
    </template>

    <MediaListItem v-for="track in tracks" :key="track.id" :media="track" media-type="track" @play="handlePlay" />
  </Page>
</template>

<script lang="ts">
import { defineComponent, onMounted, ref } from 'vue'
import { axios } from '@/axios'
import { type Media } from '@/models/media'

import MediaListItem from '@/components/media/MediaListItem.vue'
import Page from '@/components/Page.vue'
import playback from '@/composables/usePlayback'

export default defineComponent({
  name: 'TracksView',
  components: { MediaListItem, Page },
  setup() {
    const tracks = ref<Media[]>([])
    const isLoading = ref(true)
    const { overwriteQueue } = playback

    onMounted(async () => {
      try {
        const res = await axios.get('/tracks')
        tracks.value = res.data.tracks
      } catch (err) {
        console.error('Failed to load tracks:', err)
      } finally {
        isLoading.value = false
      }
    })

    const handlePlay = (track: Media) => {
      const index = tracks.value.findIndex(t => t.id === track.id)
      if (index !== -1) {
        overwriteQueue([...tracks.value], index)
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
