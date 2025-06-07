<template>
  <NcListItem :active="isActive" :name="media.title || 'Untitled'" @click="onPlay" :bold="false">
    <template #icon>
      <img v-if="media.albumArt" :src="media.albumArt" alt="Cover" class="cover" width="44" height="44" />
      <!-- fallback if no album art -->
      <Music v-else :size="44" />
    </template>

    <template #subname>
      {{ media.artist || 'Unknown Artist' }} - {{ media.album || 'Unknown Album' }}
    </template>

    <template #actions>
      <NcActionButton @click.stop="onPlay">Play</NcActionButton>
      <NcActionButton @click.stop="onPlayNext">Next</NcActionButton>
      <NcActionButton @click.stop="onAddToQueue">Queue</NcActionButton>
    </template>
  </NcListItem>
</template>

<script lang="ts">
import { defineComponent, computed, type PropType } from 'vue'
import { type Media } from '@/models/media'
import playback from '@/composables/usePlayback'

import NcListItem from '@nextcloud/vue/components/NcListItem'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import Music from '@icons/Music.vue'

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
    NcActionButton,
    NcListItem,
    Music,
  },
  emits: ['play'],
  setup(props, { emit }) {
    const { currentMedia, addToQueue, addAsNext } = playback

    const isActive = computed(() => props.media.id === currentMedia.value?.id)

    const onPlay = () => emit('play', props.media)
    const onPlayNext = () => addAsNext(props.media)
    const onAddToQueue = () => addToQueue(props.media)

    return {
      isActive,
      onPlay,
      onPlayNext,
      onAddToQueue,
    }
  },
})
</script>

<style scoped lang="scss">
.cover {
  border-radius: 4px;
  object-fit: cover;
}
</style>
