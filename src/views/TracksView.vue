<template>
  <div class="tracks-view">
    <PageTitle>
      Track List
    </PageTitle>
    <MediaListItem v-for="track in tracks" :key="track.id" :media="track" media-type="track" @play="handlePlay" />
  </div>
</template>

<script lang="ts">
import { defineComponent, onMounted, ref } from 'vue'
import { axios } from '@/axios'
import { type Media } from '@/models/media'

import MediaListItem from '@/components/media/MediaListItem.vue'
import PageTitle from '@/components/PageTitle.vue'

import playback from '@/composables/usePlayback'

export default defineComponent({
  name: 'TracksView',
  components: { MediaListItem, PageTitle },
  setup() {
    const tracks = ref([])
    const { play, overwriteQueue, playIndex } = playback

    onMounted(async () => {
      try {
        const res = await axios.get('/tracks')
        tracks.value = res.data.tracks
      } catch (err) {
        console.error('Failed to load tracks:', err)
      }
    })

    const handlePlay = (track: Media) => {
      const index = tracks.value.findIndex(t => t.id === track.id)
      if (index !== -1) {
        console.debug('[TracksView] Overwriting queue with starting index:', index)
        overwriteQueue([...tracks.value], index)
      } else {
        console.warn('Track not found in current view list:', track)
      }
    }

    return {
      tracks,
      handlePlay,
    }
  },
})
</script>
<style lang="scss">
.tracks-view {
  display: flex;
  flex-direction: column;
  height: 100%;

  .track-list {
    overflow-y: auto;
    flex: 1;
  }
}
</style>
