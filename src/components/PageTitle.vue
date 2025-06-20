<template>
  <header class="page-title">
    <h3>
      <slot />
    </h3>
  </header>
</template>

<script lang="ts">
  import { defineComponent } from 'vue'

  export default defineComponent({
    name: 'PageTitle',
  })
</script>

<style scoped lang="scss">
  .page-title {
  position: sticky;
  top: -16px;
  background: var(--color-main-background);
  padding: 1rem;
  z-index: 10;
  border-bottom: 1px solid var(--color-border);

  h3 {
    text-align: center;
    margin: 0;
  }
}
</style>
