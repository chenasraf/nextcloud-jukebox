<template>
  <NcListItem :active="isActive" :name="station.name || 'Unnamed Station'" @click.prevent="onPlay" :bold="false">
    <template #icon>
      <img v-if="station.favicon" :src="station.favicon" alt="Station icon" class="cover" width="44" height="44" />
      <Podcast v-else :size="44" />
    </template>

    <template #subname>
      {{ codecInfo }}
      <span v-if="station.country"> — {{ station.country }}</span>
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
import { type RadioStation } from '@/models/media'
import playback, { radioStationToPlayable } from '@/composables/usePlayback'

import NcListItem from '@nextcloud/vue/components/NcListItem'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'

import Podcast from '@icons/Podcast.vue'
import Play from '@icons/Play.vue'
import SkipNext from '@icons/SkipNext.vue'
import PlaylistPlus from '@icons/PlaylistPlus.vue'

export default defineComponent({
  name: 'RadioStationListItem',
  props: {
    station: {
      type: Object as PropType<RadioStation>,
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
    NcListItem,
    NcActionButton,
    Podcast,
    Play,
    SkipNext,
    PlaylistPlus,
  },
  emits: ['play'],
  setup(props, { emit }) {
    const { currentMedia, addToQueue, addAsNext } = playback

    const isActive = computed(() => props.station.id === currentMedia.value?.id)

    const codecInfo = computed(() => {
      if (!props.station.codec && !props.station.bitrate) return 'Unknown format'
      return `${props.station.codec || 'Unknown codec'}${props.station.bitrate ? ` (${props.station.bitrate} kbps)` : ''}`
    })

    const onPlay = () => emit('play', props.station)
    const onPlayNext = () => addAsNext(radioStationToPlayable(props.station))
    const onAddToQueue = () => addToQueue(radioStationToPlayable(props.station))

    return {
      isActive,
      codecInfo,
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
