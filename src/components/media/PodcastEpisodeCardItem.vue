<template>
  <div class="episode-card" :style="{ width }" @click="onClick">
    <PlayCircle :size="128" class="cover" />

    <div class="metadata-container">
      <div class="metadata">
        <div class="title">{{ episode.title || 'Untitled Episode' }}</div>
        <div class="pubdate" v-if="episode.pub_date">
          {{ formatDate(episode.pub_date) }}
        </div>
        <div class="duration" v-if="episode.duration !== null">
          {{ formatDuration(episode.duration) }}
        </div>
        <div class="description" v-if="episode.description">{{ episode.description }}</div>
      </div>

      <NcButton class="remove-button" @click.stop="remove(episode)">
        <template #icon>
          <Delete :size="20" />
        </template>
      </NcButton>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import PlayCircle from '@icons/PlayCircle.vue'
import Delete from '@icons/Delete.vue'
import type { PodcastEpisode } from '@/models/media'
import { formatDuration, formatDate } from '@/utils/time'

export default defineComponent({
  name: 'PodcastEpCardItem',
  props: {
    episode: {
      type: Object as PropType<PodcastEpisode>,
      required: true,
    },
    width: {
      type: String,
      default: '100%',
    },
  },
  emits: ['click', 'remove'],
  components: {
    PlayCircle,
    Delete,
    NcButton,
  },
  setup(_, { emit }) {
    const onClick = () => emit('click')
    const remove = (ep: PodcastEpisode) => emit('remove', ep)

    return {
      onClick,
      remove,
      formatDate,
      formatDuration,
    }
  },
})
</script>

<style scoped lang="scss">
.episode-card {
  padding: 0.75rem;
  border-radius: var(--border-radius-element);
  display: flex;
  align-items: start;
  transition: background 0.15s;
  position: relative;
  gap: 1rem;

  &,
  & * {
    cursor: pointer;
  }

  &:hover {
    background-color: var(--color-background-hover);
  }

  .cover {
    flex-shrink: 0;
    border-radius: 8px;
  }

  .metadata-container {
    display: flex;
    width: 100%;
    gap: 0.5rem;
  }

  .metadata {
    flex: 1;
    overflow: hidden;

    .title {
      font-weight: bold;
      font-size: 1.1rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .pubdate,
    .duration {
      font-size: 0.85rem;
      color: var(--color-text-maxcontrast);
      margin-top: 0.2rem;
    }

    .description {
      font-size: 0.85rem;
      color: var(--color-text-maxcontrast);
      margin-top: 0.25rem;
      max-height: 3.6em;
      overflow: hidden;
      text-overflow: ellipsis;
    }
  }

  .remove-button {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    transition: opacity 0.3s ease-in-out;
    opacity: 0;
  }

  &:hover .remove-button {
    opacity: 1;
  }
}
</style>
