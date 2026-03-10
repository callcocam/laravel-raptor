<script setup lang="ts">
import type { Ref } from 'vue'
import { inject } from 'vue'
import { Search } from 'lucide-vue-next'

const props = defineProps<{ placeholder?: string }>()
const search = inject<Ref<string>>('commandSearch')!
</script>

<template>
    <div class="flex items-center border-b px-3">
        <Search class="mr-2 size-4 shrink-0 opacity-50" />
        <input
            v-model="search"
            :placeholder="props.placeholder ?? 'Buscar...'"
            class="flex h-9 w-full rounded-md bg-transparent py-2 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
        />
    </div>
</template>
