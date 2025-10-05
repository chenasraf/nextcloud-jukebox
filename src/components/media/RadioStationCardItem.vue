<template>
  <div class="radio-card" :style="{ width }" @click="onPlay">
    <img
      v-if="station.favicon"
      :src="station.favicon"
      alt="Favicon"
      width="128"
      height="128"
      class="cover" />
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

        <NcActions class="actions-button" @click.stop="null">
          <slot name="actions-start" />

          <NcActionButton v-if="!disablePlay" @click.stop="onPlay">
            <template #icon>
              <Play :size="20" />
            </template>
            Play
          </NcActionButton>

          <NcActionButton v-if="!disablePlayNext" @click.stop="onPlayNext">
            <template #icon>
              <SkipNext :size="20" />
            </template>
            Play Next
          </NcActionButton>

          <NcActionButton v-if="!disableAddToQueue" @click.stop="onAddToQueue">
            <template #icon>
              <PlaylistPlus :size="20" />
            </template>
            Add to Queue
          </NcActionButton>

          <NcActionButton v-if="!disableDelete" @click.stop="remove(station)">
            <template #icon>
              <Delete :size="20" />
            </template>
            Remove
          </NcActionButton>

          <slot name="actions-end" />
        </NcActions>
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
  import NcActions from '@nextcloud/vue/components/NcActions'
  import NcActionButton from '@nextcloud/vue/components/NcActionButton'
  import RadioTower from '@icons/RadioTower.vue'
  import Star from '@icons/Star.vue'
  import StarOutline from '@icons/StarOutline.vue'
  import SkipNext from '@icons/SkipNext.vue'
  import Play from '@icons/Play.vue'
  import PlaylistPlus from '@icons/PlaylistPlus.vue'
  import Plus from '@icons/Plus.vue'
  import Delete from '@icons/Delete.vue'
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
      disablePlay: {
        type: Boolean,
        default: false,
      },
      disablePlayNext: {
        type: Boolean,
        default: false,
      },
      disableAddToQueue: {
        type: Boolean,
        default: false,
      },
      disableDelete: {
        type: Boolean,
        default: false,
      },
    },
    emits: ['click', 'add', 'remove', 'favorite', 'unfavorite'],
    components: {
      RadioTower,
      Star,
      StarOutline,
      NcButton,
      Plus,
      Play,
      SkipNext,
      PlaylistPlus,
      Delete,
      NcActions,
      NcActionButton,
    },
    setup(props, { emit }) {
      const onPlay = () => emit('click')
      const onPlayNext = () => emit('play-next', props.station)
      const onAddToQueue = () => emit('add-to-queue', props.station)

      const addStation = async (station: RadioStation) => {
        try {
          const res = await axios.post('/radio/stations', { station })
          emit('add', res.data.station)
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

      const remove = async (station: RadioStation) => {
        try {
          await axios.delete(`/radio/stations/${station.remoteUuid}`)
          emit('remove', station)
        } catch (err) {
          console.error('Failed to remove radio station:', err)
        }
      }

      return { onPlay, onPlayNext, onAddToQueue, addStation, setFavorite, remove }
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
  position: relative;

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

  .actions-button {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    transition: opacity 0.3s ease-in-out;
    opacity: 0;
  }

  &:hover .actions-button {
    opacity: 1;
  }
}
</style>
