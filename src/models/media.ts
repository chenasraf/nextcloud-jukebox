export interface Media {
  id: number
  mediaType: string
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
  rawId3: string | null
}
