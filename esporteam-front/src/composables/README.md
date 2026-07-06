# Composables

Use this folder for reusable Vue composition logic.

Rules:

- Name files as `useThing.js`.
- Keep HTTP calls in `src/services`.
- Return refs/computed values and action functions.
- Avoid importing components from composables.

Example:

```js
import { computed, ref } from 'vue'
import { listItems } from '../services/items'

export function useItems() {
  const items = ref([])
  const loading = ref(false)

  const total = computed(() => items.value.length)

  async function load() {
    loading.value = true
    try {
      items.value = await listItems()
    } finally {
      loading.value = false
    }
  }

  return { items, loading, total, load }
}
```
