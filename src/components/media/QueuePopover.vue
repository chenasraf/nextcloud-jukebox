<template>
  <NcPopover popup-role="dialog" :shown="shown" @close="onClose">
    <template #trigger>
      <slot name="trigger" />
    </template>

    <template #default>
      <div class="queue-container" tabindex="0" role="dialog" aria-labelledby="queue-popover-title" ref="popoverRef">
        <h2 id="queue-popover-title" class="popover-title">Playback Queue</h2>
        <div v-if="queue.length > 0" class="queue-list">
          <MediaListItem v-for="(media, index) in queue" :key="media.id" :media="media" mediaType="track"
            @play="onPlay(media)" disable-play-next disable-add-to-queue>
            <template #actions-end>
              <NcActionButton @click.stop="onRemove(media)">
                <template #icon>
                  <Delete :size="20" />
                </template>
                Remove from Queue
              </NcActionButton>
            </template>
          </MediaListItem>
        </div>
        <p v-else class="empty-message">The queue is empty.</p>
      </div>
    </template>

  </NcPopover>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, onBeforeUnmount, type PropType } from 'vue'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import MediaListItem from '@/components/media/MediaListItem.vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import Delete from '@icons/Delete.vue'
import { type Media } from '@/models/media'
import playback from '@/composables/usePlayback'

export default defineComponent({
  name: 'QueuePopover',
  components: { NcPopover, MediaListItem, NcActionButton, Delete },
  props: {
    shown: Boolean,
    queue: {
      type: Array as PropType<Media[]>,
      required: true,
    },
  },
  emits: ['close'],
  setup(_, { emit }) {
    const popoverRef = ref<HTMLElement | null>(null)

    const onClose = () => emit('close')
    const onPlay = (media: Media) => playback.playFromQueue(media)

    const handleClickOutside = (event: MouseEvent) => {
      if (popoverRef.value && !popoverRef.value.contains(event.target as Node)) {
        emit('close')
      }
    }

    onMounted(() => {
      document.addEventListener('mousedown', handleClickOutside)
    })

    onBeforeUnmount(() => {
      document.removeEventListener('mousedown', handleClickOutside)
    })

    const onRemove = (media: Media) => {
      playback.removeFromQueue(media)
      if (popoverRef.value) {
        popoverRef.value.focus() // Keep focus on the popover after removing an item
      }
    }

    return {
      onClose,
      onPlay,
      onRemove,
      popoverRef,
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
