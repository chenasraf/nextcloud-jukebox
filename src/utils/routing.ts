import { useRouter } from 'vue-router'

export function hashedPath(str: string) {
  return btoa(unescape(encodeURIComponent(str)))
}

export function getArtistPath(artist: string): string {
  return `/artists/${hashedPath(artist)}`
}

export function getAlbumPath(artist: string, album: string): string {
  return `/albums/${hashedPath(artist)}/${hashedPath(album)}`
}

export function getRadioStationPath(uuid: string): string {
  return `/radio/${uuid}`
}

export function getPodcastPath(id: number): string {
  return `/podcasts/${id}`
}

export function useGoToRoute() {
  const router = useRouter()

  return (route: string) => {
    router.push(route)
  }
}

export function useGoToAlbum() {
  const goToRoute = useGoToRoute()
  return (artist: string, album: string) => goToRoute(getAlbumPath(artist, album))
}

export function useGoToArtist() {
  const goToRoute = useGoToRoute()
  return (artist: string) => goToRoute(getArtistPath(artist))
}

export function useGoToRadioStation() {
  const goToRoute = useGoToRoute()
  return (uuid: string) => goToRoute(getRadioStationPath(uuid))
}

export function useGoToPodcast() {
  const goToRoute = useGoToRoute()
  return (id: number) => goToRoute(getPodcastPath(id))
}
