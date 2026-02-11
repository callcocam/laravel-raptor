import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

export function useEditableColumn(
  record: { id: string },
  column: { name: string; executeUrl?: string; statusKey?: string }
) {
  const isUpdating = ref(false)

  const fieldKey = column.statusKey || column.name

  function submit(value: string | number | boolean) {
    if (!column.executeUrl) return
    isUpdating.value = true
    router.post(column.executeUrl, {
      actionType: 'column',
      actionName: column.name,
      fieldName: column.name,
      record: record.id,
      [fieldKey]: value,
    }, {
      preserveScroll: true,
      preserveState: true,
      onError: () => {
        toast.error('Erro ao atualizar')
      },
      onFinish: () => {
        isUpdating.value = false
      },
    })
  }

  return { isUpdating, fieldKey, submit }
}

export function getNestedValue(record: Record<string, any>, path: string): any {
  const keys = path.split('.')
  let result = record
  for (const key of keys) {
    if (result && typeof result === 'object' && key in result) {
      result = result[key]
    } else {
      return ''
    }
  }
  return result ?? ''
}
