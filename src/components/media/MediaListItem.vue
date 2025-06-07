<template>
  <div class="media-item" @click="onPlay">
    <img v-if="media.albumArt" :src="media.albumArt" alt="Cover" class="cover" />
    <div class="info">
      <strong>{{ media.title || 'Untitled' }}</strong>
      <small v-if="media.artist">{{ media.artist }}</small>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, computed, type PropType } from 'vue'
import { type Media } from '@/models/media'

export default defineComponent({
  name: 'MediaListItem',
  props: {
    media: {
      type: Object as PropType<Media>,
      required: true,
    },
    mediaType: {
      type: String,
      required: true,
    },
  },
  emits: ['play'],
  setup(props, { emit }) {
    const onPlay = () => emit('play', props.media)

    return {
      onPlay,
    }
  },
})
</script>

<style scoped lang="scss">
.media-item {
  display: flex;
  align-items: center;
  cursor: pointer;
  padding: 0.5em;
  border-bottom: 1px solid #ccc;

  &:hover {
    background-color: #f8f8f8;
    color: #333;
  }

  .cover {
    width: 48px;
    height: 48px;
    object-fit: cover;
    margin-right: 1em;
    border-radius: 4px;
  }

  .info {
    display: flex;
    flex-direction: column;
  }
}
</style>
