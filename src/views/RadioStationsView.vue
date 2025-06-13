<template>
  <Page :loading="isLoading">
    <template #title>
      Radio Stations
    </template>

    <SearchRadioStationModal v-if="isSearchModalOpen" @add-station="addStation" @close="isSearchModalOpen = false"
      :stations="stations" />

    <div v-if="favorites.length">
      <h4>Favorites</h4>
      <div class="radio-station-list">
        <RadioStationCardItem v-for="station in favorites" :key="station.remoteUuid" :station="station"
          @click="playStation(station.remoteUuid)" @unfavorite="setFavorite(station, false)"
          @remove="removeStation(station)" />
      </div>
    </div>

    <div v-if="stations.length">
      <h4>
        My Stations

        <NcButton @click="isSearchModalOpen = true">
          <template #icon>
            <Plus />
          </template>
          Add
        </NcButton>
      </h4>
      <div class="radio-station-list">
        <RadioStationCardItem v-for="station in stations" :key="station.remoteUuid" :station="station"
          @click="playStation(station.remoteUuid)" @favorite="setFavorite(station, true)"
          @unfavorite="setFavorite(station, false)" @remove="removeStation(station)" />
      </div>
    </div>

    <div v-if="!favorites.length && !stations.length" class="empty-state">
      <p>No radio stations found. Add some to get started!</p>
      <NcButton @click="isSearchModalOpen = true">
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
import RadioStationCardItem from '@/components/media/RadioStationCardItem.vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import SearchRadioStationModal from '@/components/media/SearchRadioStationModal.vue'
import Plus from '@icons/Plus.vue'
import type { RadioStation } from '@/models/media'
// import { useGoToRadioStation } from '@/utils/routing'
import playback from '@/composables/usePlayback'

export default defineComponent({
  name: 'RadioStationsView',
  components: {
    Page,
    NcButton,
    RadioStationCardItem,
    SearchRadioStationModal,
    Plus,
  },
  setup() {
    const stations = ref<RadioStation[]>([])
    const favorites = ref<RadioStation[]>([])
    const isSearchModalOpen = ref(false)
    const isLoading = ref(true)

    // const playStation = useGoToRadioStation()

    const playStation = (remoteUuid: string) => {
      playback.playRadioStation(remoteUuid)
    }

    const fetchFavorites = async () => {
      try {
        const res = await axios.get('/radio/favorites')
        favorites.value = res.data.stations
      } catch (err) {
        console.error('Failed to load favorites:', err)
      }
    }

    const fetchStations = async () => {
      try {
        const res = await axios.get('/radio/stations')
        stations.value = res.data.stations
        isLoading.value = false
      } catch (err) {
        console.error('Failed to load stations:', err)
      }
    }

    const addStation = async (station: RadioStation) => {
      stations.value.unshift(station)
    }

    const setFavorite = async (station: RadioStation, favorited: boolean) => {
      station.favorited = favorited
      if (favorited) {
        favorites.value.unshift(station)
      } else {
        const index = favorites.value.findIndex(s => s.remoteUuid === station.remoteUuid)
        if (index !== -1) {
          favorites.value.splice(index, 1)
        }
      }
    }

    const removeStation = async (station: RadioStation) => {
      try {
        const index = stations.value.findIndex(s => s.remoteUuid === station.remoteUuid)
        if (index !== -1) {
          stations.value.splice(index, 1)
        }
        if (station.favorited) {
          setFavorite(station, false)
        }
      } catch (err) {
        console.error('Failed to remove station:', err)
      }
    }

    onMounted(() => {
      fetchStations()
      fetchFavorites()
    })

    return {
      isLoading,
      stations,
      favorites,
      isSearchModalOpen,
      playStation,
      addStation,
      removeStation,
      setFavorite,
    }
  },
})
</script>

<style scoped>
.radio-station-list {
  display: flex;
  flex-wrap: wrap;
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
