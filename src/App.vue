<template>

  <div id="jukebox-content" class="section">

    <h2>Jukebox</h2>

  </div>

</template>

<script>
import NcButton from '@nextcloud/vue/components/NcButton'
import NcTextField from '@nextcloud/vue/components/NcTextField'

import axios from '@nextcloud/axios'
import { t, n } from '@nextcloud/l10n'
import { parseISO as parseDate } from 'date-fns/parseISO'
import { format as formatDate } from 'date-fns/format'

export default {
  name: 'App',
  components: {
    NcButton,
    NcTextField,
  },
  data() {
    return {
      //
    }
  },
  created() {
    //
  },
  methods: {
    //
  },
  computed: {
    //
  },
}
</script>

<style scoped lang="scss">
#jukebox-content {
  /* */
}
</style>

