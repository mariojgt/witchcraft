<template>
    <div
        class="bg-gray-900/95 backdrop-blur border-[1px] border-amber-500/30 rounded-xl p-4 min-w-[300px] relative text-gray-100">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4 pb-2 border-b border-amber-500/30">
            <div class="flex items-center gap-2">
                <BellIcon class="w-5 h-5 text-amber-400" />
                <h3 class="font-bold text-amber-400">Notification</h3>
            </div>
            <button @click="$emit('delete')" class="hover:bg-red-500/20 p-1 rounded transition-colors">
                <XIcon class="w-4 h-4 text-red-400" />
            </button>
        </div>

        <!-- Content -->
        <div class="space-y-4">
            <div class="space-y-2">
                <label class="text-xs uppercase tracking-wider text-amber-400">Message Template</label>
                <textarea v-model="data.message" placeholder="Example: User {{status}} changed" rows="3"
                    class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-amber-500 text-gray-100 resize-none"></textarea>
                <div class="text-xs text-amber-400/70 bg-gray-800 p-2 rounded">
                    <span class="font-medium">Variables:</span> {{ availableVariables }}
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-xs uppercase tracking-wider text-amber-400">Notification Type</label>
                <select v-model="data.type"
                    class="w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-amber-500 text-gray-100">
                    <option value="info" class="bg-gray-800">Info</option>
                    <option value="warning" class="bg-gray-800">Warning</option>
                    <option value="error" class="bg-gray-800">Error</option>
                </select>
            </div>
        </div>

        <!-- Connection Point -->
        <div
            class="absolute top-1/2 -left-3 w-3 h-6 bg-gray-800 rounded-l-lg border-l-2 border-y-2 border-amber-500/50">
            <Handle type="target" position="left" />
        </div>
        <div
        class="absolute -bottom-3 left-1/2 transform -translate-x-1/2 h-3 w-6 bg-gray-800 rounded-b-lg border-b-2 border-x-2 border-red-500/50"
      >
        <Handle type="source" position="bottom" id="false" class="!bg-red-500" />
      </div>
    </div>
</template>

<script setup>
import { Handle } from '@vue-flow/core'
import { XIcon, BellIcon } from 'lucide-vue-next'
import { defineOptions, computed } from 'vue'

const props = defineProps(['data', 'variables'])

defineOptions({
    nodeMetadata: {
        category: 'Alerts',
        icon: BellIcon,
        label: 'Notification',
        initialData: {
            message: 'Status changed to {{status}}',
            type: 'info'
        }
    }
})

defineEmits(['delete'])

const availableVariables = computed(() => {
    if (!props.variables) return 'None'
    return Object.keys(props.variables).join(', ')
})
</script>
