import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? '/api',
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
})

api.interceptors.request.use(config => {
  const token = sessionStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use(
  res => res,
  err => {
    if (err.response?.status === 401) {
      sessionStorage.removeItem('token')
      localStorage.removeItem('user')
      // Use Vue Router instead of hard redirect so pages can handle errors gracefully
      import('@/router').then(({ default: router }) => router.push('/login'))
    }
    return Promise.reject(err)
  }
)

export default api
