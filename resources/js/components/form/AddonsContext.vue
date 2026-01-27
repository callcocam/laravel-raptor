<!--
 * AddonsContext - Wrapper for inputs with prepend/append addons
 *
 * Reusable component that handles prefix/suffix/prepend/append logic
 * for any form field component
 -->
<template>
  <div :class="hasAddons ? 'flex rounded-md shadow-sm' : ''">
    <!-- Prepend/Prefix addon -->
    <div
      v-if="prepend || prefix"
      class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-input bg-muted text-muted-foreground text-sm"
    >
      <component
        v-if="prependIcon"
        :is="prependIcon"
        class="h-4 w-4"
      />
      <span v-else>{{ prepend || prefix }}</span>
    </div>

    <!-- Input slot -->
    <slot :inputClass="inputClass" />

    <!-- Append/Suffix addon -->
    <div
      v-if="append || suffix"
      class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-input bg-muted text-muted-foreground text-sm"
    >
      <component
        v-if="appendIcon"
        :is="appendIcon"
        class="h-4 w-4"
      />
      <span v-else>{{ append || suffix }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, h } from 'vue'
import * as LucideIcons from 'lucide-vue-next'

interface Props {
  prepend?: string
  append?: string
  prefix?: string
  suffix?: string
}

const props = defineProps<Props>()

const hasAddons = computed(() => {
  return !!(props.prepend || props.append || props.prefix || props.suffix)
})

const inputClass = computed(() => {
  const classes = []
  if (props.prepend || props.prefix) {
    classes.push('rounded-l-none')
  }
  if (props.append || props.suffix) {
    classes.push('rounded-r-none')
  }
  return classes.join(' ')
})

const prependIcon = computed(() => {
  if (!props.prepend) return null
  const IconComponent = (LucideIcons as any)[props.prepend]
  return IconComponent ? h(IconComponent) : null
})

const appendIcon = computed(() => {
  if (!props.append) return null
  const IconComponent = (LucideIcons as any)[props.append]
  return IconComponent ? h(IconComponent) : null
})
</script>