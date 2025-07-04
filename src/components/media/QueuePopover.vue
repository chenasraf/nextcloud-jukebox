<template>
  <NcPopover popup-role="dialog" v-model:shown="shown" @close="onClose">
    <template #trigger>
      <div ref="triggerRef">
        <slot name="trigger" />
      </div>
    </template>

    <template #default>
      <div class="queue-container" tabindex="0" role="dialog" aria-labelledby="queue-popover-title" ref="popoverRef">
        <h2 id="queue-popover-title" class="popover-title">Playback Queue</h2>
        <div v-if="queue.length > 0" class="queue-list">
          <ul v-for="(media, index) in queue">
            <TrackListItem v-if="media.type == 'track'" :key="'track-' + media.id" :media="media as unknown as Track"
              mediaType="track" @play="onPlay(media)" disable-play-next disable-add-to-queue>
              <template #actions-end>
                <NcActionButton @click.stop="onRemove(media)">
                  <template #icon>
                    <Delete :size="20" />
                  </template>
                  Remove from Queue
                </NcActionButton>
              </template>
            </TrackListItem>
            <PodcastEpisodeListItem v-else-if="media.type == 'podcast'" :key="'podcast-' + media.id"
              :episode="media as unknown as PodcastEpisode" @play="onPlay(media)" disable-play-next
              disable-add-to-queue>
              <template #actions-end>
                <NcActionButton @click.stop="onRemove(media)">
                  <template #icon>
                    <Delete :size="20" />
                  </template>
                  Remove from Queue
                </NcActionButton>
              </template>
            </PodcastEpisodeListItem>
            <RadioStationListItem v-else-if="media.type == 'radio'" :key="'radio-' + media.id"
              :station="media as unknown as RadioStation" @play="onPlay(media)" disable-play-next disable-add-to-queue>
              <template #actions-end>
                <NcActionButton @click.stop="onRemove(media)">
                  <template #icon>
                    <Delete :size="20" />
                  </template>
                  Remove from Queue
                </NcActionButton>
              </template>
            </RadioStationListItem>
          </ul>
        </div>
        <p v-else class="empty-message">The queue is empty.</p>
      </div>
    </template>
  </NcPopover>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, computed, onBeforeUnmount, type PropType } from 'vue'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import TrackListItem from '@/components/media/TrackListItem.vue'
import PodcastEpisodeListItem from '@/components/media/PodcastEpisodeListItem.vue'
import RadioStationListItem from '@/components/media/RadioStationListItem.vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import Delete from '@icons/Delete.vue'
import playback, { toPlayable, type Playable } from '@/composables/usePlayback'
import type { Track, PodcastEpisode, RadioStation } from '@/models/media'

export default defineComponent({
  name: 'QueuePopover',
  components: {
    NcPopover,
    TrackListItem,
    PodcastEpisodeListItem,
    RadioStationListItem,
    NcActionButton,
    Delete
  },
  props: {
    shown: Boolean,
    queue: {
      type: Array as PropType<Playable[]>,
      required: true,
    },
  },
  setup(props, { emit }) {
    const popoverRef = ref<HTMLElement | null>(null)
    const triggerRef = ref<HTMLElement | null>(null)

    const onClose = () => {
      emit('update:shown', false)
    }
    const onPlay = (media: Playable) => playback.playFromQueue(toPlayable(media))

    const handleClickOutside = (event: MouseEvent) => {
      if (!popoverRef.value || !triggerRef.value) return
      if (
        !popoverRef.value.contains(event.target as Node) &&
        !triggerRef.value.contains(event.target as Node)
      ) {
        onClose()
      }
    }

    onMounted(() => {
      document.addEventListener('mousedown', handleClickOutside)
    })

    onBeforeUnmount(() => {
      document.removeEventListener('mousedown', handleClickOutside)
    })

    const onRemove = (media: Playable) => {
      playback.removeFromQueue(toPlayable(media))
      emit('update:shown', true)
    }

    return {
      shown: computed({
        get: () => props.shown,
        set: (val) => emit('update:shown', val),
      }),
      onClose,
      onPlay,
      onRemove,
      popoverRef,
      triggerRef,
    }
  },
})
</script>

<style scoped>
.queue-container {
  width: 500px;
  max-height: 70vh;
  overflow-y: auto;
  padding: 1em;
}

h2 {
  margin-top: 0;
}

.popover-title {
  font-size: 1.2em;
  margin-bottom: 0.5em;
}

.queue-list {
  max-height: 60vh;
  overflow-y: auto;
}

.empty-message {
  color: var(--color-text-light);
  font-style: italic;
  padding: 1em 0;
  text-align: center;
}
</style>
