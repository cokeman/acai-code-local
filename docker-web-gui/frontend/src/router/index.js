import { createRouter, createWebHashHistory } from 'vue-router'

import ProjectsPage from '../pages/ProjectsPage.vue'
import NuevaWebPage from '../pages/NuevaWebPage.vue'
import WebsLocalesPage from '../pages/WebsLocalesPage.vue'
import WatcherPage from '../pages/WatcherPage.vue'
import SettingsPage from '../pages/SettingsPage.vue'

const routes = [
  { path: '/', redirect: '/projects' },
  { path: '/projects', name: 'projects', component: ProjectsPage },
  { path: '/nueva-web', name: 'nueva-web', component: NuevaWebPage },
  { path: '/webs-locales', name: 'webs-locales', component: WebsLocalesPage },
  { path: '/watcher', name: 'watcher', component: WatcherPage },
  { path: '/settings', name: 'settings', component: SettingsPage },
]

const router = createRouter({
  history: createWebHashHistory(),
  routes,
})

export default router
