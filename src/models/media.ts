export interface Media {
  id: number
  path: string
  title: string | null
  trackNumber: number | null
  artist: string | null
  album: string | null
  albumArtist: string | null
  duration: number | null
  albumArt: string | null
  genre: string | null
  year: number | null
  bitrate: number | null
  codec: string | null
  userId: string
  mtime: number
  rawData: string | null
  remoteUuid: string | null
  homepage: string | null
  favicon: string | null
  country: string | null
  state: string | null
  language: string | null
}

export interface RadioStation {
  id: number
  remoteUuid: string
  name: string
  streamUrl: string
  homepage: string | null
  country: string | null
  state: string | null
  language: string | null
  tags: string | null
  codec: string | null
  bitrate: number | null
  favicon: string | null
  rawData: string | null
  favorited: boolean
  lastUpdated: number
}

export interface Album {
  album: string
  albumArtist: string
  year: number | null
  cover: string | null
  genre: string | null
  tracks: Media[]
}

export interface Artist {
  name: string
  cover: string | null
  genre: string | null
  albums: Album[]
  tracks: Media[]
}
