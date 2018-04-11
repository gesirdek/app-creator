import Home from '~/components/Home'
import PageNotFound from '../components/PageNotFound'

export default [
    { path: '/', name: 'Home', component: Home },
    { path: "*",name:'404' , component: PageNotFound }
]