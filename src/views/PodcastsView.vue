<template>
  <Page :loading="isLoading">
    <template #title>
      Podcasts
    </template>

    <AddPodcastModal v-if="isAddModalOpen" @add-subscription="addSubscription" @close="isAddModalOpen = false"
      :subscriptions="subscriptions" />

    <div v-if="nextEpisodes.length">
      <div class="recent-episodes">
        <h4>Recently Played / Latest Episodes</h4>
        <PodcastEpisodeCardItem v-for="ep in nextEpisodes" :key="ep.id" :episode="ep" />
        <p class="empty-state">Coming soon…</p>
      </div>
    </div>

    <div v-if="subscriptions.length">
      <h4>
        My Podcasts

        <NcButton @click="isAddModalOpen = true">
          <template #icon>
            <Plus />
          </template>
          Add
        </NcButton>
      </h4>
      <div class="podcast-sub-list">
        <PodcastSubscriptionCardItem v-for="sub in subscriptions" :key="sub.id" :subscription="sub"
          @click="openSubscription(sub.id)" @remove="removeSubscription(sub)" />
      </div>
    </div>

    <div v-if="!subscriptions.length" class="empty-state">
      <p>No podcast subscriptions found. Add some to get started!</p>
      <NcButton @click="isAddModalOpen = true">
        <template #icon>
          <Plus />
        </template>
        Add
      </NcButton>
    </div>
  </Page>
</template>

<script lang="ts">
import { defineComponent, onMounted, ref } from 'vue'
import { axios } from '@/axios'
import Page from '@/components/Page.vue'
import AddPodcastModal from '@/components/media/AddPodcastModal.vue'
import PodcastSubscriptionCardItem from '@/components/media/PodcastSubscriptionCardItem.vue'
import PodcastEpisodeCardItem from '@/components/media/PodcastEpisodeCardItem.vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import Plus from '@icons/Plus.vue'
import type { PodcastSubscription, PodcastEpisode } from '@/models/media'
import { useGoToPodcast } from '@/utils/routing'

export default defineComponent({
  name: 'PodcastsView',
  components: {
    Page,
    NcButton,
    AddPodcastModal,
    PodcastSubscriptionCardItem,
    PodcastEpisodeCardItem,
    Plus,
  },
  setup() {
    const subscriptions = ref<PodcastSubscription[]>([])
    const nextEpisodes = ref<PodcastEpisode[]>([])
    const isAddModalOpen = ref(false)
    const isLoading = ref(true)
    const goToPodcast = useGoToPodcast()

    const fetchSubscriptions = async () => {
      try {
        const res = await axios.get('/podcasts/subscriptions')
        subscriptions.value = res.data.subscriptions
      } catch (err) {
        console.error('Failed to load subscriptions:', err)
      } finally {
        isLoading.value = false
      }
    }

    const fetchEpisodes = async () => {
      try {
        const res = await axios.get('/podcasts/next')
        subscriptions.value = res.data.subscriptions
      } catch (err) {
        console.error('Failed to load subscriptions:', err)
      } finally {
        isLoading.value = false
      }
    }

    const addSubscription = async (sub: PodcastSubscription) => {
      const index = subscriptions.value.findIndex(s => s.id === sub.id)
      if (index !== -1) {
        subscriptions.value[index] = sub
        return
      }
      subscriptions.value.unshift(sub)
    }

    const removeSubscription = async (sub: PodcastSubscription) => {
      const index = subscriptions.value.findIndex(s => s.id === sub.id)
      if (index !== -1) {
        subscriptions.value.splice(index, 1)
      }
    }

    const openSubscription = (id: number) => {
      goToPodcast(id)
    }

    onMounted(() => {
      fetchSubscriptions()
    })

    return {
      isLoading,
      subscriptions,
      nextEpisodes,
      isAddModalOpen,
      addSubscription,
      removeSubscription,
      openSubscription,
    }
  },
})
</script>

<style scoped>
.podcast-sub-list {
  display: flex;
  flex-wrap: wrap;
}

.recent-episodes {
  margin-top: 2rem;
}

h4 {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.empty-state {
  text-align: center;
  font-style: italic;
  color: var(--color-text-maxcontrast);
}
</style>
