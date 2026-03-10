<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '~/lib/utils'

const props = defineProps<{
    errors?: Array<{ message: string }>
    class?: HTMLAttributes['class']
}>()
</script>

<template>
    <div
        v-if="props.errors?.length"
        :class="cn('flex flex-col gap-0.5', props.class)"
        role="alert"
    >
        <p
            v-for="(err, i) in props.errors"
            :key="i"
            class="text-sm font-medium text-destructive"
        >
            {{ err.message }}
        </p>
    </div>
</template>
