<template>
  <Page :loading="isLoading">
    <template #title>
      Radio Stations
    </template>

    <div class="search-bar">
      <NcAppNavigationSearch v-model="searchTerm" placeholder="Search stations..." />
    </div>

    <div v-if="searchTerm.trim()">
      <h4>Search Results</h4>
      <div class="radio-station-list">
        <RadioStationCardItem v-for="station in searchResults" :key="station.remoteUuid" :station="station"
          @click="goToStation(station.remoteUuid)" @add="addStation(station)" />
      </div>
    </div>

    <div v-else>
      <div v-if="favorites.length">
        <h4>Favorites</h4>
        <div class="radio-station-list">
          <RadioStationCardItem v-for="station in favorites" :key="station.remoteUuid" :station="station"
            @click="goToStation(station.remoteUuid)" @unfavorite="setFavorite(station, false)" />
        </div>
      </div>

      <div v-if="stations.length">
        <h4>My Stations</h4>
        <div class="radio-station-list">
          <RadioStationCardItem v-for="station in stations" :key="station.remoteUuid" :station="station"
            @click="goToStation(station.remoteUuid)" @favorite="setFavorite(station, true)"
            @unfavorite="setFavorite(station, false)" />
        </div>
      </div>

      <div v-if="!favorites.length && !stations.length" class="empty-state">
        <p>No radio stations found. Add some to get started!</p>
      </div>
    </div>
  </Page>
</template>

<script lang="ts">
import { defineComponent, onMounted, computed, ref, watch } from 'vue'
import { axios } from '@/axios'
import { useRouter } from 'vue-router'
import Page from '@/components/Page.vue'
import RadioStationCardItem from '@/components/media/RadioStationCardItem.vue'
import NcAppNavigationSearch from '@nextcloud/vue/components/NcAppNavigationSearch'
import type { RadioStation } from '@/models/media'
import { useGoToRadioStation } from '@/utils/routing'

export default defineComponent({
  name: 'RadioStationsView',
  components: {
    Page,
    RadioStationCardItem,
    NcAppNavigationSearch,
  },
  setup() {
    const stations = ref<RadioStation[]>([])
    const favorites = ref<RadioStation[]>([])
    const searchResults = ref<RadioStation[]>([])
    const searchTerm = ref('')
    const isLoading = ref(true)

    const router = useRouter()

    const goToStation = useGoToRadioStation()

    const fetchFavorites = async () => {
      try {
        const res = await axios.get('/radio/favorites')
        favorites.value = res.data.stations
      } catch (err) {
        console.error('Failed to load radio stations:', err)
      } finally {
        isLoading.value = false
      }
    }

    const fetchStations = async () => {
      try {
        const res = await axios.get('/radio/stations')
        stations.value = res.data.stations
      } catch (err) {
        console.error('Failed to load radio stations:', err)
      } finally {
        isLoading.value = false
      }
    }

    const addStation = async (station: RadioStation) => {
      try {
        stations.value.unshift(station)
        const index = searchResults.value.findIndex(s => s.remoteUuid === station.remoteUuid)
        if (index !== -1) {
          searchResults.value[index] = station
        }
      } catch (err) {
        console.error('Failed to add radio station:', err)
      }
    }

    const setFavorite = async (station: RadioStation, favorited: boolean) => {
      try {
        station.favorited = favorited
        if (favorited) {
          favorites.value.unshift(station)
        } else {
          const index = favorites.value.findIndex(s => s.remoteUuid === station.remoteUuid)
          if (index !== -1) {
            favorites.value.splice(index, 1)
          }
        }
      } catch (err) {
        console.error('Failed to favorite radio station:', err)
      }
    }

    const handleSearch = async () => {
      if (!searchTerm.value.trim()) {
        searchResults.value = []
        return
      }

      try {
        const res = await axios.get(`/radio/search/${encodeURIComponent(searchTerm.value)}`)
        searchResults.value = res.data.stations
      } catch (err) {
        console.error('Failed to search radio stations:', err)
      }
    }

    let debounceTimeout: number | undefined
    watch(searchTerm, () => {
      clearTimeout(debounceTimeout)
      debounceTimeout = window.setTimeout(handleSearch, 400)
    })

    onMounted(() => {
      fetchStations()
      fetchFavorites()
    })

    return {
      stations,
      favorites,
      searchTerm,
      searchResults,
      isLoading,
      handleSearch,
      goToStation,
      addStation,
      setFavorite,
    }
  },
})
</script>

<style scoped>
.search-bar {
  margin-bottom: 1rem;
}

.empty-state {
  text-align: center;
  color: var(--color-text-maxcontrast);
  font-style: italic;
}

.radio-station-list {
  display: flex;
  flex-wrap: wrap;
}
</style>
