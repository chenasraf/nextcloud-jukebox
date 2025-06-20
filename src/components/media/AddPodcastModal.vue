<template>
  <div class="modal-backdrop">
    <div class="modal">
      <h3>Add Podcast via RSS</h3>

      <NcTextField
        v-model="rssUrl"
        label="RSS Feed URL"
        placeholder="https://example.com/feed.xml"
        :autofocus="true" />

      <div class="actions">
        <NcButton :disabled="!isValid" @click="submit">
          <template #icon>
            <Plus :size="16" />
          </template>
          Add
        </NcButton>
        <NcButton @click="close" class="cancel"> Cancel </NcButton>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
  import { defineComponent, ref, computed } from 'vue'
  import NcButton from '@nextcloud/vue/components/NcButton'
  import NcTextField from '@nextcloud/vue/components/NcTextField'
  import Plus from '@icons/Plus.vue'
  import { axios } from '@/axios'

  export default defineComponent({
    name: 'AddPodcastModal',
    emits: ['add-subscription', 'close'],
    components: {
      NcButton,
      NcTextField,
      Plus,
    },
    setup(_, { emit }) {
      const rssUrl = ref('')

      const isValid = computed(() => {
        try {
          new URL(rssUrl.value)
          return true
        } catch {
          return false
        }
      })

      const submit = async () => {
        if (isValid.value) {
          const res = await axios.post('/podcasts/subscriptions', {
            url: rssUrl.value.trim(),
          })
          emit('add-subscription', res.data.subscription)
          rssUrl.value = ''
          emit('close')
        }
      }

      const close = () => emit('close')

      return {
        rssUrl,
        isValid,
        submit,
        close,
      }
    },
  })
</script>

<style scoped lang="scss">
  .modal-backdrop {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal {
  background: var(--color-background-dark);
  border-radius: var(--border-radius-large);
  padding: 2rem;
  width: 100%;
  max-width: 500px;
  box-shadow: var(--box-shadow-dialog);

  h3 {
    margin-bottom: 1rem;
  }

  .actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 1.5rem;
    gap: 1rem;

    .cancel {
      background: transparent;
      color: var(--color-text-lighter);
    }
  }
}
</style>
