import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'

const styleUrl = new URL('../style.css', import.meta.url)
const shellUrl = new URL('./ParticipantShell.vue', import.meta.url)
const sheetUrl = new URL('./BottomSheet.vue', import.meta.url)
const loginUrl = new URL('./Login.vue', import.meta.url)
const registerUrl = new URL('./Register.vue', import.meta.url)

const [css, shell, sheet, login, register] = await Promise.all([
  readFile(styleUrl, 'utf8'),
  readFile(shellUrl, 'utf8'),
  readFile(sheetUrl, 'utf8'),
  readFile(loginUrl, 'utf8'),
  readFile(registerUrl, 'utf8'),
])

for (const token of [
  '--motion-press-duration',
  '--motion-enter-duration',
  '--motion-page-duration',
  '--motion-ease-out',
  '--motion-press-scale',
  '--motion-stagger',
  '--motion-sheet-offset',
  '--motion-panel-offset',
  '--motion-inline-offset',
]) assert.match(css, new RegExp(token))

assert.match(css, /:where\(button:not\(:disabled\), a\[href\], \[role="tab"\], \[role="button"\]\):not\(\.leaflet-container \*\)/)
assert.match(css, /:active \{\s*transform: scale\(var\(--motion-press-scale\)\)/)
assert.match(css, /\.motion-page > \.motion-group \{ animation: motion-page-assemble/)
assert.doesNotMatch(css, /\.motion-page > \.motion-group \{[^}]*\bboth\s*;/)
assert.match(css, /--motion-stagger: 40ms/)
assert.match(css, /@media \(prefers-reduced-motion: reduce\)[\s\S]*--motion-stagger: 0ms/)
assert.match(css, /\.motion-sheet-enter-active\s+\.bottom-sheet/)
assert.match(css, /\.motion-panel-enter-from, \.motion-panel-leave-to \{ transform: translateX\(var\(--motion-panel-offset\)\)/)
assert.doesNotMatch(css.match(/\.motion-page[\s\S]*?@keyframes motion-reduced-fade/)?.[0] || '', /transition:\s*all/)

assert.match(shell, /:key="activeTab.id" class="participant-content motion-page"/)
assert.match(shell, /motion-group--context/)
assert.match(shell, /motion-group--content/)
assert.match(shell, /<Transition name="motion-panel">/)
assert.match(sheet, /<Transition name="motion-sheet">/)
assert.match(login, /<Transition name="motion-inline">/)
assert.match(register, /<Transition name="motion-auth" mode="out-in">/)

console.log('motion contracts passed')
