<template>
  <div class="media-item">
    <div class="clickable" @click="onPlay">
      <img v-if="media.albumArt" :src="media.albumArt" alt="Cover" class="cover" />
      <div class="info">
        <strong>{{ media.title || 'Untitled' }}</strong>
        <small v-if="media.artist">{{ media.artist }}</small>
      </div>
    </div>

    <NcActions :title="media.title || 'Media actions'" :closeAfterClick="true">
      <template #trigger>
        <NcButton variant="tertiary" size="small" aria-label="More actions">
          <DotsHorizontal />
        </NcButton>
      </template>
      <NcActionButton @click="onPlay">Play Now</NcActionButton>
      <NcActionButton @click="onPlayNext">Play Next</NcActionButton>
      <NcActionButton @click="onAddToQueue">Add to Queue</NcActionButton>
    </NcActions>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import { type Media } from '@/models/media'
import playback from '@/composables/usePlayback'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcButton from '@nextcloud/vue/components/NcButton'
import DotsHorizontal from '@icons/DotsHorizontal.vue'

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
  components: {
    NcActions,
    NcActionButton,
    NcButton,
    DotsHorizontal,
  },
  emits: ['play'],
  setup(props, { emit }) {
    const onPlay = () => {
      console.debug('[MediaListItem] Playing media:', props.media)
      emit('play', props.media)
      playback.play(props.media)
      playback.overwriteQueue([props.media])
    }

    const onPlayNext = () => {
      playback.addAsNext(props.media)
    }

    const onAddToQueue = () => {
      playback.addToQueue(props.media)
    }

    return {
      onPlay,
      onPlayNext,
      onAddToQueue,
    }
  },
})
</script>

<style scoped lang="scss">
.media-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5em;
  border-bottom: 1px solid #ccc;

  &:hover {
    background-color: #f8f8f8;
    color: #333;
  }

  .clickable {
    display: flex;
    align-items: center;
    flex: 1;
    cursor: pointer;
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
