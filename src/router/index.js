import { createRouter, createWebHashHistory } from 'vue-router'
import HelloI18n from '@/views/HelloI18n.vue'
import ProjectsView from '@/views/ProjectsView.vue'
import ProjectFormView from '@/views/ProjectFormView.vue'

const routes = [
  {
    path: '/',
    beforeEnter: (to, from, next) => {
      // Check login type
      next("/projects");
    },
    name: 'home'
  },
  {
    path: '/i18n',
    name: 'i18n',
    component: HelloI18n
  },
  {
    path: '/projects',
    name: 'projects',
    component: ProjectsView
  },
  {
    path: '/projects/new',
    name: 'newProject',
    component: ProjectFormView,
    props: {
      action: "add"
    }
  }
]

const router = createRouter({
  history: createWebHashHistory(),
  routes
})

export default router
