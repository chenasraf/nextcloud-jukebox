<template>
  <div class="tracks-view">
    <h3>Track List</h3>
    <MediaListItem v-for="track in tracks" :key="track.id" :media="track" media-type="track" @play="handlePlay" />
  </div>
</template>

<script lang="ts">
import { defineComponent, onMounted, ref } from 'vue'
import { axios } from '@/axios'

import MediaListItem from '@/components/media/MediaListItem.vue'
import { usePlayback } from '@/composables/usePlayback'

export default defineComponent({
  name: 'TracksView',
  components: { MediaListItem },
  setup() {
    const tracks = ref([])
    const { play } = usePlayback()

    onMounted(async () => {
      try {
        const res = await axios.get('/tracks')
        tracks.value = res.data.tracks
      } catch (err) {
        console.error('Failed to load tracks:', err)
      }
    })

    const handlePlay = (track: any) => {
      play(track)
    }

    return {
      tracks,
      handlePlay,
    }
  },
})
</script>
<style lang="scss">
h3 {
  text-align: center;
}
</style>
