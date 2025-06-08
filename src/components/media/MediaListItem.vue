<template>
  <NcListItem :active="isActive" :name="media.title || 'Untitled'" @click.prevent="onPlay" :bold="false">
    <template #icon>
      <img v-if="media.albumArt" :src="media.albumArt" alt="Cover" class="cover" width="44" height="44" />
      <Music v-else :size="44" />
    </template>

    <template #subname>
      {{ media.artist || 'Unknown Artist' }} - {{ media.album || 'Unknown Album' }}
    </template>

    <template #actions>
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

      <slot name="actions-end" />
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
import Play from '@icons/Play.vue'
import SkipNext from '@icons/SkipNext.vue'
import PlaylistPlus from '@icons/PlaylistPlus.vue'

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
  },
  components: {
    NcActionButton,
    NcListItem,
    Music,
    Play,
    SkipNext,
    PlaylistPlus,
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
