import Vue from 'vue'
import store from '../store'
import routes from './routes'
import Router from 'vue-router'
import { sync } from 'vuex-router-sync'
import check_auth from '../middleware/check-auth'

Vue.use(Router)

const router = createRouter()

sync(store, router)

export default router

/**
 * Create a new router instance.
 *
 * @return {Router}
 */
function createRouter () {
    const router = new Router({
        scrollBehavior,
        mode: 'history',
        routes,
        base:'/admin'
    })

    router.beforeEach(check_auth)
    router.afterEach(afterEach)

    return router
}

/**
 * Global router guard.
 *
 * @param {Route} to
 * @param {Route} from
 * @param {Function} next
 */
function beforeEach () {
    check_auth()
}

/**
 * Global after hook.
 *
 * @param {Route} to
 * @param {Route} from
 * @param {Function} next
 */
async function afterEach (to, from, next) {
    await router.app.$nextTick()
}

/**
 * Scroll Behavior
 *
 * @link https://router.vuejs.org/en/advanced/scroll-behavior.html
 *
 * @param  {Route} to
 * @param  {Route} from
 * @param  {Object|undefined} savedPosition
 * @return {Object}
 */
function scrollBehavior (to, from, savedPosition) {
    if (savedPosition) {
        return savedPosition
    }

    if (to.hash) {
        return { selector: to.hash }
    }

    const [component] = router.getMatchedComponents({ ...to }).slice(-1)

    if (component && component.scrollToTop === false) {
        return {}
    }

    return { x: 0, y: 0 }
}
