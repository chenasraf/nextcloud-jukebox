<template>
  <Page :loading="isLoading">
    <template #title> Videos </template>

    <div class="video-gallery">
      <VideoGalleryItem v-for="video in videos" :key="video.id" :video="video" @play="handlePlay" />
    </div>
  </Page>
</template>

<script lang="ts">
  import { defineComponent, onMounted, ref } from 'vue'
  import { axios } from '@/axios'
  import { type Video } from '@/models/media'

  import VideoGalleryItem from '@/components/media/VideoGalleryItem.vue'
  import Page from '@/components/Page.vue'
  import playback from '@/composables/usePlayback'

  export default defineComponent({
    name: 'VideosView',
    components: { VideoGalleryItem, Page },
    setup() {
      const videos = ref<Video[]>([])
      const isLoading = ref(true)
      const { overwriteQueue } = playback

      onMounted(async () => {
        try {
          const res = await axios.get('/video')
          videos.value = res.data.videos
        } catch (err) {
          console.error('Failed to load videos:', err)
        } finally {
          isLoading.value = false
        }
      })

      const handlePlay = (video: Video) => {
        const index = videos.value.findIndex((v) => v.id === video.id)
        if (index !== -1) {
          // Convert videos to playable format
          const playableVideos = videos.value.map((v) => ({
            id: v.id,
            title: v.title || 'Untitled',
            artist: null,
            album: null,
            duration: v.duration || 0,
            thumbnail: v.thumbnail,
            streamUrl: `/video/${v.id}/stream`,
            type: 'video' as const,
          }))
          overwriteQueue(playableVideos, index)
        }
      }

      return {
        videos,
        isLoading,
        handlePlay,
      }
    },
  })
</script>

<style scoped lang="scss">
  .video-gallery {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
  padding: 1rem;
}
</style>
