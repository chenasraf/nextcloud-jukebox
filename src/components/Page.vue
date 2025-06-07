<template>
  <div class="page">
    <PageTitle>
      <slot name="title" />
    </PageTitle>

    <div v-if="loading" class="loader-wrapper">
      <NcLoadingIcon :size="64" />
    </div>

    <main v-else class="page-content">
      <slot />
    </main>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import PageTitle from '@/components/PageTitle.vue'

import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

export default defineComponent({
  name: 'Page',
  components: { PageTitle, NcLoadingIcon },
  props: {
    loading: {
      type: Boolean,
      default: false,
    },
  },
})
</script>

<style scoped lang="scss">
.page {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.page-content {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
}

.loader-wrapper {
  display: flex;
  flex: 1;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.loader {
  font-weight: bold;
  font-size: 1.1rem;
  opacity: 0.8;
}
</style>
