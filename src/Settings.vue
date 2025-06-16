<template>
  <div id="jukebox-user-settings" class="section">
    <h2>{{ strings.header }}</h2>
    <form @submit.prevent="save">
      <fieldset :disabled="loading">
        <NcAppSettingsSection
          :name="strings.musicLibrarySettings"
          id="jukebox-music-library-settings">
          <div class="sections">
            <div class="folder-select-wrapper">
              <div class="input-with-button">
                <NcTextField
                  v-model="musicFolder"
                  :label="strings.musicFolderLabel"
                  :placeholder="strings.musicFolderPlaceholder" />
                <NcButton
                  @click="openFolderPicker('musicFolder')"
                  icon="icon-folder"
                  :aria-label="strings.pickFolder"
                  :title="strings.pickFolder"
                  :disabled="loading"
                  class="folder-button">
                  {{ strings.pickFolder }}
                </NcButton>
              </div>
            </div>
          </div>
        </NcAppSettingsSection>

        <NcAppSettingsSection
          :name="strings.podcastLibrarySettings"
          id="jukebox-podcast-library-settings">
          <div class="sections">
            <NcCheckboxRadioSwitch v-model="downloadPodcasts"
              >{{ strings.downloadPodcastsLabel }}
            </NcCheckboxRadioSwitch>

            <div class="folder-select-wrapper">
              <div class="input-with-button">
                <NcTextField
                  v-model="podcastFolder"
                  :label="strings.podcastFolderLabel"
                  :placeholder="strings.podcastFolderPlaceholder" />
                <NcButton
                  @click="openFolderPicker('podcastFolder')"
                  :disabled="loading || !downloadPodcasts"
                  icon="icon-folder"
                  :aria-label="strings.pickFolder"
                  :title="strings.pickFolder"
                  class="folder-button">
                  {{ strings.pickFolder }}
                </NcButton>
              </div>
            </div>
          </div>
        </NcAppSettingsSection>

        <NcAppSettingsSection
          :name="strings.audiobooksLibrarySettings"
          id="jukebox-audiobooks-library-settings">
          <div class="sections">
            <div class="folder-select-wrapper">
              <div class="input-with-button">
                <NcTextField
                  v-model="audiobooksFolder"
                  :label="strings.audiobooksFolderLabel"
                  :placeholder="strings.audiobooksFolderPlaceholder" />
                <NcButton
                  @click="openFolderPicker('audiobooksFolder')"
                  icon="icon-folder"
                  :aria-label="strings.pickFolder"
                  :title="strings.pickFolder"
                  :disabled="loading"
                  class="folder-button">
                  {{ strings.pickFolder }}
                </NcButton>
              </div>
            </div>
          </div>
        </NcAppSettingsSection>

        <div class="submit-buttons">
          <NcButton type="submit" :disabled="loading">{{ strings.save }}</NcButton>
        </div>
      </fieldset>
    </form>
  </div>
</template>

<script lang="ts">
  import NcAppSettingsSection from '@nextcloud/vue/components/NcAppSettingsSection'
  import NcTextField from '@nextcloud/vue/components/NcTextField'
  import NcButton from '@nextcloud/vue/components/NcButton'
  import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
  import { axios } from './axios'
  import { t } from '@nextcloud/l10n'
  import { getFilePickerBuilder } from '@nextcloud/dialogs'
  import '@nextcloud/dialogs/style.css'

  export default {
    name: 'JukeboxUserSettings',
    components: {
      NcAppSettingsSection,
      NcTextField,
      NcButton,
      NcCheckboxRadioSwitch,
    },
    data() {
      return {
        loading: false,
        musicFolder: '',
        podcastFolder: '',
        downloadPodcasts: false,
        audiobooksFolder: '',
        strings: {
          header: t('jukebox', 'Jukebox'),
          musicLibrarySettings: t('jukebox', 'Music Library'),
          podcastLibrarySettings: t('jukebox', 'Podcast Library'),
          musicFolderLabel: t('jukebox', 'Music Folder Path'),
          musicFolderPlaceholder: t('jukebox', 'e.g. Music'),
          podcastFolderLabel: t('jukebox', 'Podcast Download Path'),
          podcastFolderPlaceholder: t('jukebox', 'e.g. Podcasts'),
          audiobooksLibrarySettings: t('jukebox', 'Audiobooks Library'),
          audiobooksFolderLabel: t('jukebox', 'Audiobooks Folder Path'),
          audiobooksFolderPlaceholder: t('jukebox', 'e.g. Audiobooks'),
          downloadPodcastsLabel: t('jukebox', 'Download podcasts for offline playback'),
          pickFolder: t('jukebox', 'Pick a folder'),
          save: t('jukebox', 'Save'),
        },
      }
    },
    created() {
      this.fetchSettings()
    },
    methods: {
      async fetchSettings() {
        this.loading = true
        try {
          const response = await axios.get('/settings')
          const data = response.data
          this.musicFolder = data.music_folder_path || 'Music'
          this.downloadPodcasts = data.download_podcast_episodes || false
          this.podcastFolder = data.podcast_download_path || 'Podcasts'
          this.audiobooksFolder = data.audiobooks_folder_path || 'Audiobooks'
        } catch (e) {
          console.error('Failed to fetch settings:', e)
        } finally {
          this.loading = false
        }
      },
      async save() {
        this.loading = true
        try {
          this.musicFolder = this.cleanPath(this.musicFolder)
          this.podcastFolder = this.cleanPath(this.podcastFolder)
          this.audiobooksFolder = this.cleanPath(this.audiobooksFolder)
          const data = {
            music_folder_path: this.musicFolder,
            download_podcast_episodes: this.downloadPodcasts,
            podcast_download_path: this.podcastFolder,
            audiobooks_folder_path: this.audiobooksFolder,
          }
          console.log('Saving settings :', data)
          await axios.put('/settings', { data })
        } catch (e) {
          console.error('Failed to save settings:', e)
        } finally {
          this.loading = false
        }
      },
      async openFolderPicker(folderType: string) {
        try {
          const picker = getFilePickerBuilder(this.strings.musicFolderLabel)
            .allowDirectories(true)
            .addButton({
              label: t('jukebox', 'Select'),
              callback: (nodes) => {
                console.log('Selected nodes:', nodes)
                const node = nodes?.[0] as any
                if (!node || !node._data?.root || !node._data?.attributes?.filename) return
                const root = node._data.root
                const fullPath = node._data.attributes.filename
                const self = this as unknown as Record<string, string>
                self[folderType] = fullPath.startsWith(root)
                  ? fullPath.slice(root.length) || '/'
                  : fullPath
                self[folderType] = this.cleanPath(self[folderType])
                // console.log('Selected folder path:', self[folderType])
              },
            })
            .build()

          await picker.pick()
        } catch (e) {
          if ((e as Error).message.includes('No nodes selected')) return
          console.error('Failed to open folder picker:', e)
        }
      },
      cleanPath(path: string): string {
        return path.startsWith('/') ? path.slice(1) : path
      },
    },
  }
</script>

<style scoped>
  #jukebox-user-settings {
  h2 {
    margin-top: 0;
  }

  .submit-buttons {
    margin-top: 16px;
  }

  .input-with-button {
    display: flex;
    align-items: flex-end;
    gap: 8px;
  }

  .folder-button {
    flex-shrink: 0;
  }

  .sections {
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }
}
</style>
