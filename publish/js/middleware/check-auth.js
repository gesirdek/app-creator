import store from '../store'

export default async (to, from, next) => {
  if (store.getters.token) {
    try {
      await store.dispatch('fetchUser')
    } catch (e) { }
  }else{
    console.log("logged out");
  }

  return next();
}
