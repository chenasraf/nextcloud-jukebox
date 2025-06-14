<template>
  <NcListItem :active="isActive" :name="episode.title || 'Untitled Episode'" @click.prevent="onPlay" :bold="false">
    <template #icon>
      <img v-if="cover" :src="cover" alt="Podcast cover" class="cover" width="44" height="44" />
      <Podcast v-else :size="44" />
    </template>

    <template #subname>
      {{ durationFormatted }} — {{ episode.pub_date ? new Date(episode.pub_date).toLocaleDateString() : 'Unknown date'
      }}
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
import { type PodcastEpisode } from '@/models/media'
import playback, { podcastEpisodeToPlayable } from '@/composables/usePlayback'

import NcListItem from '@nextcloud/vue/components/NcListItem'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'

import Podcast from '@icons/Podcast.vue'
import Play from '@icons/Play.vue'
import SkipNext from '@icons/SkipNext.vue'
import PlaylistPlus from '@icons/PlaylistPlus.vue'

export default defineComponent({
  name: 'PodcastEpisodeListItem',
  props: {
    episode: {
      type: Object as PropType<PodcastEpisode>,
      required: true,
    },
    cover: {
      type: String,
      required: false,
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

    const isActive = computed(() => props.episode.id === currentMedia.value?.id)

    const durationFormatted = computed(() => {
      if (!props.episode.duration) return 'No duration'
      const minutes = Math.floor(props.episode.duration / 60)
      const seconds = props.episode.duration % 60
      return `${minutes}:${seconds.toString().padStart(2, '0')}`
    })

    const onPlay = () => emit('play', props.episode)
    const onPlayNext = () => addAsNext(podcastEpisodeToPlayable(props.episode))
    const onAddToQueue = () => addToQueue(podcastEpisodeToPlayable(props.episode))

    return {
      isActive,
      durationFormatted,
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
