<script lang="ts">
import { cn } from '~/lib/utils'
import { cloneVNode, defineComponent, h, mergeProps } from 'vue'

export default defineComponent({
    name: 'SidebarMenuSubButton',
    inheritAttrs: false,
    props: {
        as: { type: [String, Object], default: 'a' },
        asChild: { type: Boolean, default: false },
        size: { type: String as () => 'sm' | 'md', default: 'md' },
        isActive: { type: Boolean, default: false },
        class: { type: String, default: '' },
    },
    setup(props, { slots, attrs }) {
        return () => {
            const buttonClass = cn(
                'text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground [&>svg]:text-sidebar-accent-foreground flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-hidden focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50 [&>span:last-child]:truncate [&>svg]:size-4 [&>svg]:shrink-0',
                'data-[active=true]:bg-sidebar-accent data-[active=true]:text-sidebar-accent-foreground',
                props.size === 'sm' ? 'text-xs' : 'text-sm',
                'group-data-[collapsible=icon]:hidden',
                props.class,
            )

            const mergedProps = mergeProps(attrs, {
                'data-slot': 'sidebar-menu-sub-button',
                'data-sidebar': 'menu-sub-button',
                'data-size': props.size,
                ...(props.isActive ? { 'data-active': 'true' } : {}),
                class: buttonClass,
            })

            if (props.asChild && slots.default) {
                const children = slots.default()
                const firstChild = children.find(vnode => vnode.type !== Comment && vnode.type !== Text)
                if (firstChild) {
                    return cloneVNode(firstChild, mergedProps)
                }
            }

            return h(props.as as string, mergedProps, slots.default?.())
        }
    },
})
</script>
