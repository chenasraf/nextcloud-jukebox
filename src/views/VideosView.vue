<template>
  <Page :loading="isLoading">
    <template #title> Videos </template>

    <div class="video-gallery">
      <VideoGalleryItem v-for="video in videos" :key="video.id" :video="video" />
    </div>
  </Page>
</template>

<script lang="ts">
  import { defineComponent, onMounted, ref } from 'vue'
  import { axios } from '@/axios'
  import { type Video } from '@/models/media'

  import VideoGalleryItem from '@/components/media/VideoGalleryItem.vue'
  import Page from '@/components/Page.vue'

  export default defineComponent({
    name: 'VideosView',
    components: { VideoGalleryItem, Page },
    setup() {
      const videos = ref<Video[]>([])
      const isLoading = ref(true)

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

      return {
        videos,
        isLoading,
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
