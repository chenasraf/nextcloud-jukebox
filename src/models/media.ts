export interface Track {
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

export interface PodcastSubscription {
  id: number
  subscription_id: number | null
  title: string | null
  author: string | null
  description: string | null
  url: string | null
  user_id: string | null
  image: string | null
  subscribed: boolean
  updated: string
}

export interface PodcastEpisode {
  id: number
  action_id: number | null
  subscription_data_id: number
  title: string | null
  guid: string | null
  pub_date: string | null
  duration: number | null
  media_url: string | null
  description: string | null
  user_id: string | null
  image: string | null
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
  tracks: Track[]
}

export interface Artist {
  name: string
  cover: string | null
  genre: string | null
  albums: Album[]
  tracks: Track[]
}

export interface Video {
  id: number
  path: string
  title: string | null
  duration: number | null
  thumbnail: string | null
  genre: string | null
  year: number | null
  bitrate: number | null
  width: number | null
  height: number | null
  videoCodec: string | null
  audioCodec: string | null
  framerate: number | null
  userId: string
  mtime: number
  rawData: string | null
  favorited: boolean
}
