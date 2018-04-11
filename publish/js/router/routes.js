import Home from '~/components/Home'
import Action from '~/components/Action'
import Address from '~/components/Address'
import BRequest from '~/components/BRequest'
import Confirm from '~/components/Confirm'
import Contact from '~/components/Contact'
import Ilan from '~/components/Ilan'
import Image from '~/components/Image'
import JobGroup from '~/components/JobGroup'
import Level from '~/components/Level'
import Login from '~/components/Login'
import Navigation from '~/components/Navigation'
import NewsCategory from '~/components/NewsCategory'
import Pagination from '~/components/Pagination'
import PasswordReset from '~/components/PasswordReset'
import Profile from '~/components/Profile'
import Proposal from '~/components/Proposal'
import Redirect from '~/components/Redirect'
import Request from '~/components/Request'
import Role from '~/components/Role'
import Route from '~/components/Route'
import Sector from '~/components/Sector'
import User from '~/components/User'
import Verification from '~/components/Verification'
import AdminApp from '~/AdminApp'

import PageNotFound from '../components/PageNotFound'

export default [
    { path: '/', name: 'Home', component: Home },
    { path: '/admin', name: 'Admin', component: AdminApp,
        children: [
            {
                path: 'action',
                component: Action,
                name:'Action'
            },
            {
                path: 'address',
                component: Address,
                name: 'Address'
            }
        ]
    },
    { path: "*",name:'404' , component: PageNotFound }
]