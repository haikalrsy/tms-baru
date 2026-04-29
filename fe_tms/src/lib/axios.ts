import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL as string || 'http://localhost:8001/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  withCredentials: false,
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use(
  (res) => res,
  (err) => {
    const status = err.response?.status
    const code   = err.response?.data?.code

    if (status === 401) {
      localStorage.removeItem('token')
      window.location.href = '/login'
    }

    if (status === 403 && code === 'ACCOUNT_PENDING') {
      window.location.href = '/pending-approval'
    }

    return Promise.reject(err)
  }
)

export default api