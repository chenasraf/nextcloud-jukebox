<template>
  <NcModal :show="show" @close="onClose" title="Playback Queue">
    <div class="queue-list">
      <MediaListItem v-for="(media, index) in queue" :key="media.id" :media="media" mediaType="track" @play="onPlay" />
    </div>
  </NcModal>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import NcModal from '@nextcloud/vue/components/NcModal'
import MediaListItem from '@/components/media/MediaListItem.vue'
import { type Media } from '@/models/media'

export default defineComponent({
  name: 'QueuePopup',
  components: { NcModal, MediaListItem },
  props: {
    show: Boolean,
    queue: {
      type: Array as PropType<Media[]>,
      required: true,
    },
  },
  emits: ['close', 'play'],
  setup(_, { emit }) {
    const onClose = () => emit('close')
    const onPlay = (media: Media) => emit('play', media)

    return { onClose, onPlay }
  },
})
</script>

<style scoped>
.queue-list {
  max-height: 60vh;
  overflow-y: auto;
}
</style>
