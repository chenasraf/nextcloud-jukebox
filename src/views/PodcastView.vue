<template>
  <Page :loading="isLoading">
    <template #title>
      {{ podcast?.title || 'Podcast' }}
    </template>

    <div v-if="podcast" class="podcast-info">
      <img v-if="podcast.image" :src="podcast.image" alt="Podcast cover" class="cover" />
      <Podcast v-else :size="100" />

      <div class="meta">
        <h2>{{ podcast.title }}</h2>
        <p v-if="podcast.author">By {{ podcast.author }}</p>
        <p v-if="podcast.description" class="description">{{ podcast.description }}</p>
      </div>
    </div>

    <div v-if="episodes.length">
      <PodcastEpisodeListItem v-for="ep in episodes" :key="ep.id" :episode="ep" :cover="podcast?.image ?? undefined"
        @play="handlePlay(ep)" />
    </div>
  </Page>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { axios } from '@/axios'
import Page from '@/components/Page.vue'
import PodcastEpisodeListItem from '@/components/media/PodcastEpisodeListItem.vue'
import Podcast from '@icons/Podcast.vue'
import playback, { podcastEpisodeToPlayable } from '@/composables/usePlayback'
import type { PodcastSubscription, PodcastEpisode } from '@/models/media'

export default defineComponent({
  name: 'PodcastView',
  components: {
    Page,
    PodcastEpisodeListItem,
    Podcast,
  },
  setup() {
    const route = useRoute()
    const podcast = ref<PodcastSubscription | null>(null)
    const episodes = ref<PodcastEpisode[]>([])
    const isLoading = ref(true)
    const { overwriteQueue } = playback

    onMounted(async () => {
      const id = decodeURIComponent(route.params.id as string)

      try {
        const res = await axios.get(`/podcasts/subscriptions/${id}`)
        podcast.value = res.data.subscription

        const epRes = await axios.get(`/podcasts/subscriptions/${id}/episodes`)
        episodes.value = (epRes.data.episodes as PodcastEpisode[]).sort((a, b) => {
          const aDate = new Date(a.pub_date || 0).getTime()
          const bDate = new Date(b.pub_date || 0).getTime()
          return bDate - aDate
        })
      } catch (err) {
        console.error('Failed to load podcast view:', err)
      } finally {
        isLoading.value = false
      }
    })

    const handlePlay = (episode: PodcastEpisode) => {
      const index = episodes.value.findIndex((e) => e.id === episode.id)
      if (index !== -1) {
        overwriteQueue([podcastEpisodeToPlayable(episodes.value[index])], index)
      }
    }

    return {
      podcast,
      episodes,
      isLoading,
      handlePlay,
    }
  },
})
</script>

<style scoped lang="scss">
.podcast-info {
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

    p {
      margin: 0.25em 0;
    }

    .description {
      opacity: 0.7;
      font-style: italic;
    }
  }
}
</style>
