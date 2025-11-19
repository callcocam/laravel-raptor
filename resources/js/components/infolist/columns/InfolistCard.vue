<template>
  <Card class="shadow-md">
    <Collapsible
      v-if="column.collapsible"
      v-model:open="isOpen"
    >
      <CardHeader>
        <CollapsibleTrigger class="w-full">
          <div class="flex items-center justify-between">
            <CardTitle class="flex items-center gap-2">
              <Icon v-if="column.icon" :is="column.icon" class="h-5 w-5 text-primary" />
              {{ column.title || column.text }}
            </CardTitle>
            <Icon
              is="ChevronDown"
              :class="[
                'h-4 w-4 transition-transform',
                isOpen ? 'rotate-180' : ''
              ]"
            />
          </div>
        </CollapsibleTrigger>
        <CardDescription v-if="column.description">
          {{ column.description }}
        </CardDescription>
      </CardHeader>

      <CollapsibleContent>
        <CardContent>
          <div
            :class="[
              'grid gap-4',
              gridClass
            ]"
          >
            <div
              v-for="(item, key) in column.columns"
              :key="key"
              class="flex justify-between items-center py-3 border-b border-border last:border-0"
            >
              <span class="text-sm font-medium text-muted-foreground">{{ item.label }}</span>
              <div class="text-sm font-semibold">
                <InfoReander :column="item" />
              </div>
            </div>
          </div>
        </CardContent>
      </CollapsibleContent>
    </Collapsible>

    <template v-else>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Icon v-if="column.icon" :is="column.icon" class="h-5 w-5 text-primary" />
          {{ column.title || column.text }}
        </CardTitle>
        <CardDescription v-if="column.description">
          {{ column.description }}
        </CardDescription>
      </CardHeader>

      <CardContent>
        <div
          :class="[
            'grid gap-4',
            gridClass
          ]"
        >
          <div
            v-for="(item, key) in column.columns"
            :key="key"
            class="flex justify-between items-center py-3 border-b border-border last:border-0"
          >
            <span class="text-sm font-medium text-muted-foreground">{{ item.label }}</span>
            <div class="text-sm font-semibold">
              <InfoReander :column="item" />
            </div>
          </div>
        </div>
      </CardContent>
    </template>
  </Card>
</template>

<script lang="ts" setup>
import { ref, computed } from 'vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import Icon from '~/components/icon.vue'
import InfoReander from '../InfoReander.vue'

const props = defineProps<{
  column: {
    text: string
    icon?: string
    tooltip?: string
    type: string
    columns: Record<string, any>
    title?: string
    description?: string
    gridCols?: string
    collapsible?: boolean
    defaultExpanded?: boolean
  }
}>()

const isOpen = ref(props.column.defaultExpanded ?? true)

const gridClass = computed(() => {
  const cols = props.column.gridColumns || '1'
  const gap = props.column.gap || '4'
  const responsive = props.column.responsive?.grid || {}

  const classes = ['grid', `gap-${gap}`]

  // Grid columns padrão
  const gridMap: Record<string, string> = {
    '1': 'grid-cols-1',
    '2': 'grid-cols-1 md:grid-cols-2',
    '3': 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
    '4': 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
  }
  classes.push(gridMap[cols] || 'grid-cols-1')

  // Responsive grid columns
  if (responsive.sm) classes.push(`sm:grid-cols-${responsive.sm}`)
  if (responsive.md) classes.push(`md:grid-cols-${responsive.md}`)
  if (responsive.lg) classes.push(`lg:grid-cols-${responsive.lg}`)
  if (responsive.xl) classes.push(`xl:grid-cols-${responsive.xl}`)

  return classes.join(' ')
})
</script>
