import {createRouter, createWebHashHistory} from 'vue-router'
import ProjectsView from '@/views/ProjectsView.vue'
import HomeView from '@/views/HomeView.vue'
import AdminView from '@/views/AdminView'
import SingleProjectView from '@/views/SingleProjectView.vue'
import ProjectFormView from '@/views/ProjectFormView.vue'

const routes = [
    {
        path: '/',
        name: 'home',
        component: HomeView
    },
    {
        path: '/admin',
        name: "admin",
        component: AdminView
    },
    {
        path: '/projects',
        name: 'projects',
        component: ProjectsView
    },
    {
        path: '/project/:id',
        name: 'projectId',
        component: SingleProjectView,
        meta: {
            action: "list"
        }
    },
    {
        path: '/project/:id/new',
        name: 'projectIdNewTask',
        component: SingleProjectView,
        meta: {
            action: "add"
        }
    },
    {
        path: '/project/:id/new/:cloneID',
        name: 'projectIdCloneTask',
        component: SingleProjectView,
        meta: {
            action: "add"
        }
    },
    {
        path: '/project/:id/edit/:cloneID',
        name: 'projectIdEditTask',
        component: SingleProjectView,
        meta: {
            action: "edit"
        }
    },
    {
        path: '/project/:id/:task',
        name: 'projectIdTaskInfo',
        component: SingleProjectView,
        meta: {
            action: "task"
        }
    },
    {
        path: '/projects/new',
        name: 'newProject',
        component: ProjectFormView,
        meta: {
            action: "add"
        }
    }
]

const router = createRouter({
    history: createWebHashHistory(),
    routes
});

export default router
