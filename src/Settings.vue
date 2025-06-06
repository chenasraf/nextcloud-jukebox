<template>
  <div id="jukebox-user-settings" class="section">
    <h2>{{ strings.header }}</h2>
    <form @submit.prevent="save">
      <NcAppSettingsSection :name="strings.musicLibrarySettings">
        <div class="folder-select-wrapper">
          <div class="input-with-button">
            <NcTextField
              v-model="musicFolder"
              :label="strings.musicFolderLabel"
              :placeholder="strings.musicFolderPlaceholder"
              :disabled="true" />
            <NcButton
              @click="openFolderPicker"
              icon="icon-folder"
              :aria-label="strings.pickFolder"
              :title="strings.pickFolder"
              :disabled="loading"
              class="folder-button">
              {{ strings.pickFolder }}
            </NcButton>
          </div>
        </div>
      </NcAppSettingsSection>
      <div class="submit-buttons">
        <NcButton type="submit" :disabled="loading">{{ strings.save }}</NcButton>
      </div>
    </form>
  </div>
</template>

<script>
  import NcAppSettingsSection from '@nextcloud/vue/components/NcAppSettingsSection'
  import NcTextField from '@nextcloud/vue/components/NcTextField'
  import NcButton from '@nextcloud/vue/components/NcButton'
  import { settingsAxios } from './axios'
  import { t } from '@nextcloud/l10n'
  import { getFilePickerBuilder } from '@nextcloud/dialogs'
  import '@nextcloud/dialogs/style.css'

  export default {
    name: 'JukeboxUserSettings',
    components: {
      NcAppSettingsSection,
      NcTextField,
      NcButton,
    },
    data() {
      return {
        loading: false,
        musicFolder: '',
        strings: {
          header: t('jukebox', 'Jukebox'),
          musicLibrarySettings: t('jukebox', 'Music Library'),
          musicFolderLabel: t('jukebox', 'Music Folder Path'),
          musicFolderPlaceholder: t('jukebox', 'e.g. Music'),
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
          const response = await settingsAxios.get('/settings')
          const data = response.data.ocs.data
          this.musicFolder = data.music_folder_path || ''
        } catch (e) {
          console.error('Failed to fetch settings:', e)
        } finally {
          this.loading = false
        }
      },
      async save() {
        this.loading = true
        try {
          const data = {
            music_folder_path: this.musicFolder,
          }
          console.log('Saving settings :', data)
          await settingsAxios.put('/settings', { data })
        } catch (e) {
          console.error('Failed to save settings:', e)
        } finally {
          this.loading = false
        }
      },
      async openFolderPicker() {
        try {
          const picker = getFilePickerBuilder(this.strings.musicFolderLabel)
            .allowDirectories(true)
            .addButton({
              label: t('jukebox', 'Select'),
              callback: (nodes) => {
                console.log('Selected nodes:', nodes)
                const node = nodes?.[0]
                if (!node || !node._data?.root || !node._data?.attributes?.filename) return
                const root = node._data.root
                const fullPath = node._data.attributes.filename
                this.musicFolder = fullPath.startsWith(root)
                  ? fullPath.slice(root.length) || '/'
                  : fullPath
                if (this.musicFolder.startsWith('/')) {
                  this.musicFolder = this.musicFolder.slice(1)
                }
                console.log('Selected folder path:', this.musicFolder)
              },
            })
            .build()

          await picker.pick()
        } catch (e) {
          if (e.message.includes('No nodes selected')) return
          console.error('Failed to open folder picker:', e)
        }
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
}
</style>
