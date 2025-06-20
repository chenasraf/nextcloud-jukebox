<template>
  <NcListItem :name="artist.name" @click.prevent="onSelect" :bold="false">
    <template #icon>
      <img
        v-if="artist.cover"
        :src="artist.cover"
        alt="Cover"
        class="cover"
        width="44"
        height="44" />
      <AccountMusic v-else :size="44" />
    </template>

    <template #subname>
      {{ artist.genre || 'Unknown Genre' }}
    </template>

    <template #actions>
      <slot name="actions-start" />

      <NcActionButton @click.stop="onSelect">
        <template #icon>
          <ChevronRight :size="20" />
        </template>
        View
      </NcActionButton>

      <slot name="actions-end" />
    </template>
  </NcListItem>
</template>

<script lang="ts">
  import { defineComponent, type PropType } from 'vue'

  import NcListItem from '@nextcloud/vue/components/NcListItem'
  import NcActionButton from '@nextcloud/vue/components/NcActionButton'

  import AccountMusic from '@icons/AccountMusic.vue'
  import ChevronRight from '@icons/ChevronRight.vue'

  import type { Artist } from '@/models/media'

  export default defineComponent({
    name: 'ArtistListItem',
    props: {
      artist: {
        type: Object as PropType<Artist>,
        required: true,
      },
    },
    components: {
      NcListItem,
      NcActionButton,
      AccountMusic,
      ChevronRight,
    },
    emits: ['select'],
    setup(props, { emit }) {
      const onSelect = () => emit('select', props.artist)

      return {
        onSelect,
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
