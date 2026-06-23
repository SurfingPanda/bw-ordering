// Build a dynamic QR Ph (EMVCo merchant-presented QR) string from a base
// merchant payload by injecting the transaction amount and recomputing the CRC,
// so the generated code is amount-specific rather than a static image.
//
// The base payload is the data encoded in a merchant's QR Ph code (decode your
// printed/static QR with any QR reader to get it). We:
//   - set tag 01 (Point of Initiation Method) to "12" (dynamic),
//   - set/insert tag 54 (Transaction Amount) to the order total,
//   - drop and recompute tag 63 (CRC16-CCITT) so the code stays valid.

function crc16(str) {
  let crc = 0xffff
  for (let i = 0; i < str.length; i++) {
    crc ^= str.charCodeAt(i) << 8
    for (let j = 0; j < 8; j++) {
      crc = crc & 0x8000 ? (crc << 1) ^ 0x1021 : crc << 1
      crc &= 0xffff
    }
  }
  return crc.toString(16).toUpperCase().padStart(4, '0')
}

function field(id, value) {
  return `${id}${String(value.length).padStart(2, '0')}${value}`
}

// Parse an EMVCo string into ordered { id, value } TLV objects, or null if it
// isn't a well-formed TLV sequence.
function parse(payload) {
  const out = []
  let i = 0
  while (i + 4 <= payload.length) {
    const id = payload.slice(i, i + 2)
    const len = parseInt(payload.slice(i + 2, i + 4), 10)
    if (Number.isNaN(len)) return null
    const value = payload.slice(i + 4, i + 4 + len)
    if (value.length !== len) return null
    out.push({ id, value })
    i += 4 + len
  }
  return i === payload.length ? out : null
}

// Returns the amount-injected payload string, or null if `base` isn't a valid
// EMVCo / QR Ph string (so callers can fall back to a demo QR).
export function buildQrphPayload(base, amount) {
  const fields = parse((base || '').trim())
  // A real EMVCo QR starts with a Payload Format Indicator (tag 00).
  if (!fields || !fields.some((f) => f.id === '00')) return null

  const out = fields.filter((f) => f.id !== '63')

  const init = out.find((f) => f.id === '01')
  if (init) init.value = '12'
  else out.splice(1, 0, { id: '01', value: '12' })

  const amt = Number(amount || 0).toFixed(2)
  const amtField = out.find((f) => f.id === '54')
  if (amtField) amtField.value = amt
  else {
    const idx = out.findIndex((f) => f.id === '53') // place after currency
    if (idx >= 0) out.splice(idx + 1, 0, { id: '54', value: amt })
    else out.push({ id: '54', value: amt })
  }

  const body = out.map((f) => field(f.id, f.value)).join('') + '6304'
  return body + crc16(body)
}
