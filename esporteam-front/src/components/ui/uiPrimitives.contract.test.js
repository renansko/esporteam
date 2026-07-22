import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const files = ['UiButton.vue', 'UiChip.vue', 'UiSegmented.vue', 'UiSlider.vue', 'UiStepper.vue', 'UiToggle.vue', 'UiFormFooter.vue']
for (const file of files) {
  const source = readFileSync(new URL(`./${file}`, import.meta.url), 'utf8')
  assert(source.includes('<template'), `${file} must be a reusable Vue primitive`)
}
const button = readFileSync(new URL('./UiButton.vue', import.meta.url), 'utf8')
const styles = readFileSync(new URL('../../style.css', import.meta.url), 'utf8')
for (const variant of ['primary', 'confirm', 'back', 'exit', 'destructive']) {
  assert.match(styles, new RegExp(`ui-button--${variant}`))
}
assert.match(button, /aria-busy/)

console.log('ui primitive contracts: ok')
