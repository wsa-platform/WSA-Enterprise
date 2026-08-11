export const DEMO_HINT = 'Demo: admin@wsa.test / password'

export function isDemoLoginEnabled(): boolean {
  const flag = import.meta.env.VITE_SHOW_DEMO_LOGIN

  if (flag === 'true' || flag === '1') {
    return true
  }

  if (flag === 'false' || flag === '0') {
    return false
  }

  return import.meta.env.DEV
}

export function getLoginDefaults(): { email: string; password: string } {
  if (isDemoLoginEnabled()) {
    return { email: 'admin@wsa.test', password: 'password' }
  }

  return { email: '', password: '' }
}
