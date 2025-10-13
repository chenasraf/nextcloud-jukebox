<template>
  <NcListItem
    :active="isActive"
    :name="video.title || 'Untitled'"
    @click.prevent="onPlay"
    :bold="false">
    <template #icon>
      <img
        v-if="video.thumbnail"
        :src="video.thumbnail"
        alt="Thumbnail"
        class="thumbnail"
        width="44"
        height="44" />
      <Filmstrip v-else :size="44" />
    </template>

    <template #subname>
      <span v-if="video.year || video.genre">
        <span v-if="video.year">{{ video.year }}</span>
        <span v-if="video.year && video.genre"> • </span>
        <span v-if="video.genre">{{ video.genre }}</span>
      </span>
      <span v-else-if="video.duration">{{ formatDuration(video.duration) }}</span>
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
  import { type Video } from '@/models/media'
  import playback from '@/composables/usePlayback'

  import NcListItem from '@nextcloud/vue/components/NcListItem'
  import NcActionButton from '@nextcloud/vue/components/NcActionButton'

  import Filmstrip from '@icons/Filmstrip.vue'
  import Play from '@icons/Play.vue'
  import SkipNext from '@icons/SkipNext.vue'
  import PlaylistPlus from '@icons/PlaylistPlus.vue'

  export default defineComponent({
    name: 'VideoListItem',
    props: {
      video: {
        type: Object as PropType<Video>,
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
      Filmstrip,
      Play,
      SkipNext,
      PlaylistPlus,
    },
    emits: ['play'],
    setup(props, { emit }) {
      const { currentMedia, addToQueue, addAsNext } = playback

      const isActive = computed(() =>
        props.video.id === currentMedia.value?.id && currentMedia.value?.type === 'video'
      )

      const onPlay = () => emit('play', props.video)
      const onPlayNext = () => addAsNext({ type: 'video', ...props.video })
      const onAddToQueue = () => addToQueue({ type: 'video', ...props.video })

      const formatDuration = (seconds: number): string => {
        const hours = Math.floor(seconds / 3600)
        const minutes = Math.floor((seconds % 3600) / 60)
        const secs = seconds % 60

        if (hours > 0) {
          return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
        }
        return `${minutes}:${secs.toString().padStart(2, '0')}`
      }

      return {
        isActive,
        onPlay,
        onPlayNext,
        onAddToQueue,
        formatDuration,
      }
    },
  })
</script>

<style scoped lang="scss">
  .thumbnail {
  border-radius: 4px;
  object-fit: cover;
}
</style>
