<template>
  <div class="podcast-card" :style="{ width }" @click="onClick">
    <img v-if="subscription.image" :src="subscription.image" alt="Cover" width="128" height="128" class="cover" />
    <Podcast v-else :size="128" />

    <div class="metadata-container">
      <div class="metadata">
        <div class="title">{{ subscription.title || 'Untitled Podcast' }}</div>
        <div class="author" v-if="subscription.author">{{ subscription.author }}</div>
        <div class="description" v-if="subscription.description">{{ subscription.description }}</div>
        <!-- Optional: show feed URL -->
        <!-- <div class="url" v-if="subscription.url">{{ subscription.url }}</div> -->
      </div>

      <NcButton class="remove-button" @click.stop="remove(subscription)">
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
import Podcast from '@icons/Podcast.vue'
import Delete from '@icons/Delete.vue'
import type { PodcastSubscription } from '@/models/media'

export default defineComponent({
  name: 'PodcastSubCardItem',
  props: {
    subscription: {
      type: Object as PropType<PodcastSubscription>,
      required: true,
    },
    width: {
      type: String,
      default: '256px',
    },
  },
  emits: ['click', 'remove'],
  components: {
    Podcast,
    Delete,
    NcButton,
  },
  setup(_, { emit }) {
    const onClick = () => emit('click')
    const remove = (sub: PodcastSubscription) => emit('remove', sub)

    return { onClick, remove }
  },
})
</script>

<style scoped lang="scss">
.podcast-card {
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

  .metadata-container {
    display: flex;
    width: 100%;
    gap: 0.5rem;
  }

  .metadata {
    margin-top: 0.5rem;
    flex: 1;
    overflow: hidden;

    .title {
      font-weight: bold;
      font-size: 1.1rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .author {
      font-size: 0.95rem;
      color: var(--color-text-maxcontrast);
    }

    .description {
      font-size: 0.85rem;
      color: var(--color-text-maxcontrast);
      margin-top: 0.25rem;
      max-height: 3.6em;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .url {
      font-size: 0.75rem;
      word-break: break-word;
      color: var(--color-text-lighter);
      margin-top: 0.25rem;
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
