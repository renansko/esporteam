import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'

const css = await readFile(new URL('../style.css', import.meta.url), 'utf8')

const participantContentRule = css.match(/\.participant-content\s*\{([^}]*)\}/)?.[1] || ''
assert.match(participantContentRule, /overflow:\s*auto/)
assert.match(participantContentRule, /min-height:\s*0/)

const mobileStart = css.indexOf('@media (max-width: 520px)')
const mobileEnd = css.indexOf('@media (prefers-reduced-motion: reduce)', mobileStart)
const mobileRules = css.slice(mobileStart, mobileEnd)
assert.match(mobileRules, /\.participant-stage\s*\{[\s\S]*height:\s*100dvh/)
assert.match(mobileRules, /\.participant-shell\s*\{[\s\S]*height:\s*100%/)
assert.doesNotMatch(mobileRules, /\.participant-topbar\s*\{\s*display:\s*none/)
assert.match(mobileRules, /\.participant-nav-item\s*\{[\s\S]*min-height:\s*48px/)
assert.doesNotMatch(mobileRules, /\.participant-nav-item\s*\{[\s\S]*min-height:\s*58px/)
