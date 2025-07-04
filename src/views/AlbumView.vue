<template>
  <Page :loading="isLoading">
    <template #title> Album </template>

    <div v-if="album" class="album-info">
      <img v-if="album.cover" :src="album.cover" alt="Album Cover" class="cover" />
      <Music v-else :size="100" />
      <div class="meta">
        <h2>{{ album.album }}</h2>
        <a href="#" class="artist" @click.prevent="goToArtist(album.albumArtist)">
          {{ album.albumArtist }}
        </a>
        <div class="details">
          <p v-if="album.year">Released: {{ album.year }}</p>
          <p v-if="album.genre">Genre: {{ album.genre }}</p>
        </div>
      </div>
    </div>

    <div v-if="album">
      <TrackListItem
        v-for="track in album.tracks"
        :key="track.id"
        :media="track"
        media-type="track"
        @play="handlePlay(track)" />
    </div>
  </Page>
</template>

<script lang="ts">
  import { defineComponent, onMounted, ref } from 'vue'
  import { useRoute } from 'vue-router'
  import { axios } from '@/axios'
  import type { Album, Track } from '@/models/media'
  import { useGoToArtist } from '@/utils/routing'

  import Page from '@/components/Page.vue'
  import TrackListItem from '@/components/media/TrackListItem.vue'
  import Music from '@icons/Music.vue'
  import playback, { trackToPlayable } from '@/composables/usePlayback'
  import NcButton from '@nextcloud/vue/components/NcButton'

  export default defineComponent({
    name: 'AlbumView',
    components: { Page, TrackListItem, Music, NcButton },
    setup() {
      const route = useRoute()
      const album = ref<Album | null>(null)
      const isLoading = ref(true)
      const { overwriteQueue } = playback

      onMounted(async () => {
        const artistId = decodeURIComponent(route.params.artist as string)
        const albumId = decodeURIComponent(route.params.album as string)
        try {
          const res = await axios.get(`/music/albums/${artistId}/${albumId}`)
          album.value = res.data
        } catch (err) {
          console.error('Failed to load album:', err)
        } finally {
          isLoading.value = false
        }
      })

      const handlePlay = (track: Track) => {
        if (album.value) {
          const index = album.value.tracks.findIndex((t) => t.id === track.id)
          if (index !== -1) {
            overwriteQueue(album.value.tracks.map(trackToPlayable), index)
          }
        }
      }

      const goToArtist = useGoToArtist()

      return {
        goToArtist,
        album,
        isLoading,
        handlePlay,
      }
    },
  })
</script>

<style scoped lang="scss">
  .album-info {
  display: flex;
  align-items: center;
  margin-bottom: 1em;

  .cover {
    width: 100px;
    height: 100px;
    object-fit: cover;
    margin-right: 1em;
  }

  .meta {
    h2 {
      margin: 0;
      font-size: 1.5em;
    }

    >p {
      padding: 0.5em 0;
    }

    .details {
      display: flex;

      p {
        padding: 0 0.5em;

        &:not(:last-child) {
          border-right: 1px solid currentcolor;
        }

        &:first-child {
          padding-left: 0;
        }
      }
    }
  }
}
</style>
