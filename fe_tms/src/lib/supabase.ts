import { createClient } from '@supabase/supabase-js'

// Kita pakai fallback string kosong/dummy biar aplikasi nggak crash 
// kalau lu nggak punya file .env yang isinya kredensial asli
const supabaseUrl = import.meta.env.VITE_SUPABASE_URL || 'https://dummy-project.supabase.co'
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY || 'dummy-anon-key'

export const supabase = createClient(supabaseUrl, supabaseAnonKey)