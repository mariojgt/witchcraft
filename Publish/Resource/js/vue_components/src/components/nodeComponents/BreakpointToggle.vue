<template>
    <button
        @click="toggleBreakpoint"
        :class="buttonClass"
        :title="tooltipText"
        class="w-8 h-8 rounded-lg transition-all duration-200 flex items-center justify-center group"
    >
        <div class="relative">
            <!-- Filled red circle when breakpoint is active -->
            <div
                v-if="hasBreakpoint"
                class="w-3 h-3 bg-red-500 rounded-full shadow-sm"
            ></div>
            <!-- Empty circle with border when no breakpoint -->
            <div
                v-else
                class="w-3 h-3 border-2 border-current rounded-full transition-colors"
            ></div>

            <!-- Optional pulse animation when active -->
            <div
                v-if="hasBreakpoint && showPulse"
                class="absolute inset-0 w-3 h-3 bg-red-500 rounded-full animate-ping opacity-75"
            ></div>
        </div>
    </button>
</template>

<script setup>
import { ref, computed, inject, onMounted, watch } from 'vue'

// Props
const props = defineProps({
    nodeId: {
        type: String,
        required: true
    },
    size: {
        type: String,
        default: 'md', // sm, md, lg
        validator: (value) => ['sm', 'md', 'lg'].includes(value)
    },
    variant: {
        type: String,
        default: 'default', // default, minimal, prominent
        validator: (value) => ['default', 'minimal', 'prominent'].includes(value)
    },
    showPulse: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    }
})

// Emits
const emit = defineEmits(['breakpoint-toggled'])

// Inject simulation service
const simulationService = inject('simulationService', null)

// State
const hasBreakpoint = ref(false)

// Computed styles based on props
const buttonClass = computed(() => {
    const base = 'transition-all duration-200 flex items-center justify-center group'

    // Size variants
    const sizes = {
        sm: 'w-6 h-6',
        md: 'w-8 h-8',
        lg: 'w-10 h-10'
    }

    // Style variants
    const variants = {
        default: hasBreakpoint.value
            ? 'bg-red-500/20 text-red-400 hover:bg-red-500/30'
            : 'hover:bg-red-500/10 text-gray-400 hover:text-red-400',
        minimal: hasBreakpoint.value
            ? 'text-red-400'
            : 'text-gray-400 hover:text-red-400',
        prominent: hasBreakpoint.value
            ? 'bg-red-500/30 text-red-300 hover:bg-red-500/40 ring-1 ring-red-500/50'
            : 'hover:bg-red-500/10 text-gray-400 hover:text-red-400 hover:ring-1 hover:ring-red-500/30'
    }

    const disabledClass = props.disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'

    return `${base} ${sizes[props.size]} ${variants[props.variant]} ${disabledClass} rounded-lg`
})

const tooltipText = computed(() => {
    if (props.disabled) return 'Breakpoint disabled'
    return hasBreakpoint.value
        ? 'Remove breakpoint (F9)'
        : 'Add breakpoint (F9)'
})

// Methods
function toggleBreakpoint() {
    if (props.disabled || !simulationService) return

    console.log(`Toggling breakpoint for node: ${props.nodeId}`)

    const newState = simulationService.toggleBreakpoint(props.nodeId)
    hasBreakpoint.value = newState

    // Emit event for parent component
    emit('breakpoint-toggled', {
        nodeId: props.nodeId,
        hasBreakpoint: newState
    })

    console.log(`Breakpoint ${newState ? 'added' : 'removed'} for node: ${props.nodeId}`)
}

// Initialize breakpoint state
onMounted(() => {
    if (simulationService) {
        hasBreakpoint.value = simulationService.hasBreakpoint(props.nodeId)
        console.log(`Initial breakpoint state for ${props.nodeId}:`, hasBreakpoint.value)
    }
})

// Watch for external changes to breakpoints (like clear all)
watch(() => simulationService?.getBreakpoints?.(), (newBreakpoints) => {
    if (newBreakpoints) {
        hasBreakpoint.value = newBreakpoints.includes(props.nodeId)
    }
}, { deep: true })

// Expose methods for parent component if needed
defineExpose({
    hasBreakpoint: () => hasBreakpoint.value,
    toggleBreakpoint
})
</script>

<style scoped>
/* Custom animations for different variants */
.group:hover .animate-ping {
    animation-duration: 1s;
}

/* Ensure proper z-index for pulse animation */
.relative {
    isolation: isolate;
}
</style>
