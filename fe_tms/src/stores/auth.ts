import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/lib/axios'

export type AppRole      = 'admin' | 'driver'
export type AccountStatus = 'pending' | 'approved' | 'rejected' | 'suspended'

export interface Profile {
  id:             string
  full_name:      string
  email:          string
  phone:          string | null
  status:         AccountStatus
  requested_role: AppRole
}

export const useAuthStore = defineStore('auth', () => {
  const user    = ref<any>(JSON.parse(localStorage.getItem('auth_user') ?? 'null'))
  const token   = ref<string | null>(localStorage.getItem('token'))
  const profile = ref<Profile | null>(JSON.parse(localStorage.getItem('auth_profile') ?? 'null'))
  const roles   = ref<AppRole[]>(JSON.parse(localStorage.getItem('auth_roles') ?? '[]'))
  const loading = ref(false)

  const isLoggedIn = computed(() => !!token.value)
  const isAdmin    = computed(() => roles.value.includes('admin'))
  const isApproved = computed(() => profile.value?.status === 'approved')

  function setAuth(data: any) {
    token.value   = data.token
    user.value    = data.user
    profile.value = {
      id:             String(data.user.id),
      full_name:      data.user.name,
      email:          data.user.email,
      phone:          data.user.phone ?? null,
      status:         data.user.account_status,
      requested_role: data.user.role,
    }
    roles.value = [data.user.role]

    localStorage.setItem('token',        data.token)
    localStorage.setItem('auth_user',    JSON.stringify(data.user))
    localStorage.setItem('auth_profile', JSON.stringify(profile.value))
    localStorage.setItem('auth_roles',   JSON.stringify(roles.value))
  }

  function clearAuth() {
    token.value   = null
    user.value    = null
    profile.value = null
    roles.value   = []
    localStorage.removeItem('token')
    localStorage.removeItem('auth_user')
    localStorage.removeItem('auth_profile')
    localStorage.removeItem('auth_roles')
  }

  async function fetchMe() {
    const { data } = await api.get('/auth/me')
    setAuth({ token: token.value, user: data.data })
  }

  async function signIn(emailVal: string, passwordVal: string) {
    const { data } = await api.post('/auth/login', {
      email:    emailVal,
      password: passwordVal,
    })
    setAuth(data)
  }

  async function signOut() {
    try { await api.post('/auth/logout') } catch {}
    clearAuth()
    window.location.href = '/login'
  }

  // Legacy compat (dipakai komponen lama)
  const session        = computed(() => token.value ? { access_token: token.value } : null)
  const init           = () => () => {}
  const loadProfile    = async () => {}
  const refreshProfile = async () => {}
  const signUp         = async (params: any) => {
    const res = await api.post('/auth/register', {
      name:                  params.fullName,
      email:                 params.email,
      phone:                 params.phone ?? null,
      password:              params.password,
      password_confirmation: params.password,
    })
    return res.data
  }

  return {
    user, token, profile, roles, loading,
    isLoggedIn, isAdmin, isApproved,
    session,
    setAuth, clearAuth, fetchMe,
    signIn, signOut, signUp,
    init, loadProfile, refreshProfile,
  }
})