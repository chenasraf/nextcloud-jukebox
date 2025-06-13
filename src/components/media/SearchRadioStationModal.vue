<template>
  <NcModal @close="$emit('close')">
    <div class="modal">
      <h4>Search Radio Stations</h4>

      <div class="modal-search-bar">
        <NcAppNavigationSearch v-model="searchTerm" placeholder="Search stations…" />
      </div>

      <div v-if="searchResults.length">
        <div class="radio-station-list">
          <RadioStationCardItem v-for="station in searchResults" :key="station.remoteUuid" :station="station"
            @add="onAddStation" />
        </div>
      </div>

      <div v-else-if="searching" class="empty-state">
        <NcLoadingIcon :size="36" />
      </div>

      <div v-else-if="searchTerm.trim()" class="empty-state">
        <p>No results found.</p>
      </div>

      <div v-else class="empty-state">
        <p>Start searching for radio stations!</p>
      </div>
    </div>
  </NcModal>
</template>

<script lang="ts">
import { defineComponent, ref, watch } from 'vue'
import NcModal from '@nextcloud/vue/components/NcModal'
import NcAppNavigationSearch from '@nextcloud/vue/components/NcAppNavigationSearch'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import RadioStationCardItem from '@/components/media/RadioStationCardItem.vue'
import { axios } from '@/axios'
import type { RadioStation } from '@/models/media'

export default defineComponent({
  name: 'SearchRadioStationModal',
  components: {
    NcModal,
    NcAppNavigationSearch,
    RadioStationCardItem,
    NcLoadingIcon,
  },
  emits: ['close', 'add-station'],
  props: {
    stations: {
      type: Array as () => RadioStation[],
      default: () => [],
    },
  },
  setup(props, { emit }) {
    const searchTerm = ref('')
    const searchResults = ref<RadioStation[]>([])
    const searching = ref(false)

    const performSearch = async () => {
      if (!searchTerm.value.trim()) {
        searchResults.value = []
        return
      }

      try {
        const res = await axios.get(`/radio/search/${encodeURIComponent(searchTerm.value)}`)
        searchResults.value = res.data.stations.filter((station: RadioStation) => {
          return !props.stations.some((s) => s.remoteUuid === station.remoteUuid)
        })
      } catch (err) {
        console.error('Search failed:', err)
      }
    }

    const onAddStation = (station: RadioStation) => {
      emit('add-station', station)
      const index = searchResults.value.findIndex((s) => s.remoteUuid === station.remoteUuid)
      searchResults.value.splice(index, 1)
    }

    let debounceTimeout: number | undefined
    watch(searchTerm, () => {
      clearTimeout(debounceTimeout)
      debounceTimeout = window.setTimeout(performSearch, 400)
    })

    return {
      searching,
      searchTerm,
      searchResults,
      onAddStation,
    }
  },
})
</script>

<style scoped>
.modal {
  padding: 1rem;
}

.modal h4 {
  margin-top: 0;
}

.modal-search-bar {
  margin-bottom: 1rem;
}

.radio-station-list {
  display: flex;
  flex-wrap: wrap;
}

.empty-state {
  text-align: center;
  font-style: italic;
  color: var(--color-text-maxcontrast);
}
</style>
