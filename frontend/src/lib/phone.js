// Shared phone-number rules used by the register form and the complete-profile step.

// Keep only digits and the few valid phone symbols (+, space, -, parentheses).
export function sanitizePhone(value) {
  return value.replace(/[^\d+\-\s()]/g, '')
}

// A usable number has at least 7 digits (ignoring formatting characters).
export function isValidPhone(value) {
  return value.replace(/\D/g, '').length >= 7
}

// Canonical form used for uniqueness checks so that "0917 123 4567" and
// "09171234567" are treated as the same number. Keeps a leading + if present.
export function normalizePhone(value) {
  const trimmed = value.trim()
  const digits = trimmed.replace(/\D/g, '')
  return trimmed.startsWith('+') ? `+${digits}` : digits
}
