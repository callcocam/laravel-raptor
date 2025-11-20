<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import Icon from '~/components/icon.vue'

interface FormAction {
  name: string
  label: string
  icon?: string
  variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link'
  size?: 'default' | 'sm' | 'lg' | 'icon'
  actionType: string
  url?: string | boolean
  method?: string
}

interface Props {
  actions?: FormAction[]
  processing?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  actions: () => [],
  processing: false,
})

const handleAction = (action: FormAction) => {
  if (action.actionType === 'submit') {
    // Submit é tratado pelo form, não fazemos nada aqui
    return
  }

  if (action.actionType === 'cancel') {
    if (action.url && typeof action.url === 'string') {
      if (action.url.startsWith('javascript:')) {
        eval(action.url.replace('javascript:', ''))
      } else {
        router.visit(action.url)
      }
    } else {
      window.history.back()
    }
  }
}

const getButtonType = (action: FormAction): 'submit' | 'button' => {
  return action.actionType === 'submit' ? 'submit' : 'button'
}
</script>

<template>
  <div class="flex items-center justify-end gap-2">
    <Button
      v-for="action in actions"
      :key="action.name"
      :type="getButtonType(action)"
      :variant="action.variant || 'default'"
      :size="action.size || 'default'"
      :disabled="processing && action.actionType === 'submit'"
      @click="handleAction(action)"
    >
      <Icon
        v-if="action.icon"
        :is="action.icon"
        class="h-4 w-4 mr-2"
      />
      <span v-if="processing && action.actionType === 'submit'">
        Salvando...
      </span>
      <span v-else>
        {{ action.label }}
      </span>
    </Button>
  </div>
</template>
