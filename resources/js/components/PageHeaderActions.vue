<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import Icon from '~/components/icon.vue'
import { ref } from 'vue'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'

interface PageAction {
  name: string
  label: string
  icon?: string
  variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link'
  size?: 'default' | 'sm' | 'lg' | 'icon'
  actionType: string
  url?: string | boolean
  method?: string
  confirm?: {
    title?: string
    message?: string
    confirmText?: string
    cancelText?: string
    requiresTypedConfirmation?: boolean
    typedConfirmationWord?: string
  }
}

interface Props {
  actions?: PageAction[]
  modelId?: string
}

const props = withDefaults(defineProps<Props>(), {
  actions: () => [],
})

const showConfirm = ref(false)
const currentAction = ref<PageAction | null>(null)
const typedConfirmation = ref('')

const handleAction = (action: PageAction) => {
  if (action.confirm) {
    currentAction.value = action
    showConfirm.value = true
    typedConfirmation.value = ''
    return
  }

  executeAction(action)
}

const executeAction = (action: PageAction) => {
  if (!action.url || typeof action.url !== 'string') {
    return
  }

  const method = (action.method || 'GET').toLowerCase()

  if (method === 'get') {
    router.visit(action.url)
  } else {
    router.visit(action.url, {
      method: method as any,
      preserveScroll: true,
      onSuccess: () => {
        showConfirm.value = false
        currentAction.value = null
      },
    })
  }
}

const confirmAction = () => {
  if (!currentAction.value) return

  // Verifica se requer confirmação por digitação
  if (
    currentAction.value.confirm?.requiresTypedConfirmation &&
    typedConfirmation.value !== currentAction.value.confirm?.typedConfirmationWord
  ) {
    return
  }

  executeAction(currentAction.value)
}

const cancelAction = () => {
  showConfirm.value = false
  currentAction.value = null
  typedConfirmation.value = ''
}
</script>

<template>
  <div v-if="actions.length > 0" class="flex items-center gap-2">
    <Button
      v-for="action in actions"
      :key="action.name"
      :variant="action.variant || 'default'"
      :size="action.size || 'sm'"
      @click="handleAction(action)"
    >
      <Icon v-if="action.icon" :is="action.icon" class="h-4 w-4 mr-2" />
      {{ action.label }}
    </Button>

    <AlertDialog :open="showConfirm" @update:open="(val) => (showConfirm = val)">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>
            {{ currentAction?.confirm?.title || 'Confirmar ação' }}
          </AlertDialogTitle>
          <AlertDialogDescription>
            {{ currentAction?.confirm?.message || 'Tem certeza que deseja realizar esta ação?' }}
          </AlertDialogDescription>
        </AlertDialogHeader>

        <div v-if="currentAction?.confirm?.requiresTypedConfirmation" class="my-4">
          <label class="text-sm font-medium mb-2 block">
            Digite "{{ currentAction.confirm.typedConfirmationWord }}" para confirmar:
          </label>
          <input
            v-model="typedConfirmation"
            type="text"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            :placeholder="currentAction.confirm.typedConfirmationWord"
          />
        </div>

        <AlertDialogFooter>
          <AlertDialogCancel @click="cancelAction">
            {{ currentAction?.confirm?.cancelText || 'Cancelar' }}
          </AlertDialogCancel>
          <AlertDialogAction
            @click="confirmAction"
            :disabled="
              currentAction?.confirm?.requiresTypedConfirmation &&
              typedConfirmation !== currentAction?.confirm?.typedConfirmationWord
            "
          >
            {{ currentAction?.confirm?.confirmText || 'Confirmar' }}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>
</template>
