<template>
  <div class="radio-card" :style="{ width }" @click="onClick">
    <img v-if="station.favicon" :src="station.favicon" alt="Favicon" width="128" height="128" class="cover" />
    <RadioTower v-else :size="128" />
    <div class="metadata-container">
      <div class="metadata">
        <div class="title">{{ station.name || 'Untitled Station' }}</div>
        <div class="meta" v-if="station.country || station.language">
          {{ [station.country, station.language].filter(Boolean).join(' · ') }}
        </div>
      </div>
      <div v-if="station.id != null">
        <NcButton @click.stop="setFavorite(station, !station.favorited)">
          <template #icon>
            <div v-if="station.favorited">
              <Star :size="20" />
            </div>
            <div v-else>
              <StarOutline :size="20" />
            </div>
          </template>
        </NcButton>
      </div>
      <div v-else>
        <NcButton @click.stop="addStation(station)">
          <template #icon>
            <Plus :size="20" />
          </template>
        </NcButton>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import { axios } from '@/axios'
import NcButton from '@nextcloud/vue/components/NcButton'
import RadioTower from '@icons/RadioTower.vue'
import Star from '@icons/Star.vue'
import StarOutline from '@icons/StarOutline.vue'
import Plus from '@icons/Plus.vue'
import type { RadioStation } from '@/models/media'

export default defineComponent({
  name: 'RadioStationCardItem',
  props: {
    station: {
      type: Object as PropType<RadioStation>,
      required: true,
    },
    width: {
      type: String,
      default: '256px',
    },
  },
  emits: ['click', 'add', 'favorite', 'unfavorite'],
  components: {
    RadioTower, Star, StarOutline, NcButton, Plus,
  },
  setup(_, { emit }) {
    const onClick = () => emit('click')

    const addStation = async (station: RadioStation) => {
      try {
        const res = await axios.post('/radio/stations', { station })
        emit('add', res.data)
      } catch (err) {
        console.error('Failed to add radio station:', err)
      }
    }

    const setFavorite = async (station: RadioStation, favorited: boolean) => {
      try {
        await axios.put(`/radio/stations/${station.remoteUuid}`, { station: { favorited } })
        station.favorited = favorited
        emit(favorited ? 'favorite' : 'unfavorite', station)
      } catch (err) {
        console.error('Failed to favorite radio station:', err)
      }
    }
    return { onClick, addStation, setFavorite }
  },
})
</script>

<style scoped lang="scss">
.radio-card {
  padding: 0.75rem;
  border-radius: var(--border-radius-element);
  display: flex;
  flex-direction: column;
  align-items: start;
  transition: background 0.15s;

  &,
  & * {
    cursor: pointer;
  }

  &:hover {
    background-color: var(--color-background-hover);
  }

  .cover {
    border-radius: 8px;
    object-fit: cover;
  }

  .metadata {
    margin-top: 0.5rem;
    flex: 1;

    .title {
      font-weight: bold;
      font-size: 1.1rem;
    }

    .meta {
      font-size: 0.85rem;
      color: var(--color-text-maxcontrast);
    }
  }

  .metadata-container {
    display: flex;
    width: 100%;
    gap: 0.5rem;
  }
}
</style>
