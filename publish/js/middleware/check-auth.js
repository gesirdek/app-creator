import store from '../store'

export default async (to, from, next) => {
  console.log("check auth middleware worked");
  if (store.getters.token) {
      console.log("check auth middleware worked 2");
    try {
      await store.dispatch('fetchUser')
    } catch (e) { }
  }else{
    console.log("token yok logout ol");
  }

  return next();
}
