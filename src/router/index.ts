import { createRouter, createWebHashHistory, type RouteRecordRaw } from 'vue-router'
import TracksView from '@/views/TracksView.vue'

const routes: RouteRecordRaw[] = [
  { path: '/', redirect: '/tracks' },
  { path: '/tracks', component: TracksView },
  // { path: '/albums', component: () => import('../views/AlbumsView.vue') },
  // { path: '/artists', component: () => import('../views/ArtistsView.vue') },
  // { path: '/podcasts', component: () => import('../views/PodcastsView.vue') },
  // { path: '/audiobooks', component: () => import('../views/AudiobooksView.vue') },
  // { path: '/videos', component: () => import('../views/VideosView.vue') },
  // { path: '/genres', component: () => import('../views/GenresView.vue') },
  // { path: '/radio', component: () => import('../views/RadioView.vue') },
]

const router = createRouter({
  history: createWebHashHistory(),
  routes,
})

export default router
