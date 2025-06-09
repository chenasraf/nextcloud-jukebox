<template>
  <div class="radio-card" :style="{ width }" @click="onClick">
    <img v-if="station.favicon" :src="station.favicon" alt="Favicon" width="128" height="128" class="cover" />
    <RadioTower v-else :size="128" />
    <div class="metadata">
      <div class="title">{{ station.name || 'Untitled Station' }}</div>
      <div class="meta" v-if="station.country || station.language">
        {{ [station.country, station.language].filter(Boolean).join(' · ') }}
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import RadioTower from '@icons/RadioTower.vue'
import type { RadioStation } from '@/models/media'

export default defineComponent({
  name: 'RadioStationCardItem',
  props: {
    station: {
      type: Object as PropType<RadioStation>,
      required: true,
    },
    width: {
      type: String,
      default: '128px',
    },
  },
  emits: ['click'],
  components: {
    RadioTower,
  },
  setup(_, { emit }) {

    const onClick = () => emit('click')

    return { onClick }
  },
})
</script>

<style scoped lang="scss">
.radio-card {
  padding: 0.75rem;
  border-radius: var(--border-radius-element);
  display: flex;
  flex-direction: column;
  align-items: start;
  transition: background 0.15s;

  &,
  & * {
    cursor: pointer;
  }

  &:hover {
    background-color: var(--color-background-hover);
  }

  .cover {
    border-radius: 8px;
    object-fit: cover;
  }

  .metadata {
    margin-top: 0.5rem;

    .title {
      font-weight: bold;
      font-size: 1.1rem;
    }

    .meta {
      font-size: 0.85rem;
      color: var(--color-text-maxcontrast);
    }
  }
}
</style>
