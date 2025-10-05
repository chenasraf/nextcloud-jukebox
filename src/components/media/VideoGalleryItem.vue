<template>
  <div class="video-card" :style="{ width }" @click="handleClick">
    <div class="thumbnail-container">
      <img v-if="video.thumbnail" :src="video.thumbnail" alt="Thumbnail" class="thumbnail" />
      <Video v-else :size="96" class="placeholder-icon" />
      <div class="duration-overlay" v-if="video.duration">
        {{ formatDuration(video.duration) }}
      </div>
    </div>
    <div class="metadata">
      <div class="title">{{ video.title || 'Untitled' }}</div>
      <div class="info">
        <span v-if="video.width && video.height" class="resolution">
          {{ video.width }}×{{ video.height }}
        </span>
        <span v-if="video.year" class="year">{{ video.year }}</span>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
  import { defineComponent, type PropType } from 'vue'
  import type { Video } from '@/models/media'

  import Video from '@icons/Video.vue'

  export default defineComponent({
    name: 'VideoGalleryItem',
    props: {
      video: {
        type: Object as PropType<Video>,
        required: true,
      },
      width: {
        type: String,
        default: '200px',
      },
    },
    components: {
      Video,
    },
    emits: ['play'],
    setup(props, { emit }) {
      const formatDuration = (seconds: number): string => {
        const hours = Math.floor(seconds / 3600)
        const minutes = Math.floor((seconds % 3600) / 60)
        const secs = seconds % 60

        if (hours > 0) {
          return `${hours}:${minutes.toString().padStart(2, '0')}:${secs
            .toString()
            .padStart(2, '0')}`
        }
        return `${minutes}:${secs.toString().padStart(2, '0')}`
      }

      const handleClick = () => {
        emit('play', props.video)
      }

      return {
        formatDuration,
        handleClick,
        width: props.width,
      }
    },
  })
</script>

<style scoped lang="scss">
  .video-card {
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

  .thumbnail-container {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    border-radius: 8px;
    overflow: hidden;
    background-color: var(--color-background-dark);
    display: flex;
    align-items: center;
    justify-content: center;

    .thumbnail {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .placeholder-icon {
      color: var(--color-text-maxcontrast);
    }

    .duration-overlay {
      position: absolute;
      bottom: 0.5rem;
      right: 0.5rem;
      background-color: rgba(0, 0, 0, 0.8);
      color: white;
      padding: 0.125rem 0.375rem;
      border-radius: 4px;
      font-size: 0.75rem;
      font-weight: 500;
    }
  }

  .metadata {
    margin-top: 0.5rem;
    width: 100%;

    .title {
      font-weight: bold;
      font-size: 1rem;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .info {
      display: flex;
      gap: 0.5rem;
      font-size: 0.85rem;
      color: var(--color-text-maxcontrast);
      margin-top: 0.25rem;

      .resolution::after {
        content: '•';
        margin-left: 0.5rem;
      }

      .resolution:last-child::after {
        content: '';
      }
    }
  }
}
</style>
