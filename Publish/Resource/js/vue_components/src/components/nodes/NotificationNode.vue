<template>
    <div class="bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-xl p-5 min-w-[320px] relative shadow-2xl hover:shadow-amber-500/10 transition-all duration-300 hover:border-amber-500/30">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gradient-to-br from-amber-500/20 to-amber-600/20 rounded-lg flex items-center justify-center border border-amber-500/20">
                    <BellIcon class="w-5 h-5 text-amber-400" />
                </div>
                <div>
                    <h3 class="font-semibold text-white text-sm">Notification</h3>
                    <p class="text-xs text-gray-400 leading-none mt-0.5">Send alerts and messages</p>
                </div>
            </div>
            <button @click="$emit('delete')" class="w-8 h-8 rounded-lg hover:bg-red-500/10 text-gray-400 hover:text-red-400 transition-all duration-200 flex items-center justify-center group">
                <XIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
            </button>
        </div>

        <!-- Content -->
        <div class="space-y-4">
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Message</label>
                <textarea v-model="data.message" placeholder="User {{status}} has been updated" rows="2" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 resize-none focus:bg-white/10 focus:border-amber-500/50 focus:ring-2 focus:ring-amber-500/20 transition-all duration-200 outline-none"></textarea>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Type</label>
                <select v-model="data.type" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white focus:bg-white/10 focus:border-amber-500/50 focus:ring-2 focus:ring-amber-500/20 transition-all duration-200 outline-none">
                    <option value="info">Info</option>
                    <option value="warning">Warning</option>
                    <option value="error">Error</option>
                </select>
            </div>

            <!-- Variables preview -->
            <div v-if="availableVariables !== 'None'" class="bg-white/5 border border-white/10 rounded-lg p-3">
                <div class="text-xs text-gray-400">
                    <span class="font-medium text-gray-300">Available variables:</span>
                    <span class="text-amber-400">{{ availableVariables }}</span>
                </div>
            </div>
        </div>

        <!-- Connection Points -->
        <div class="absolute top-1/2 -left-2 transform -translate-y-1/2">
            <Handle type="target" position="left" class="!w-4 !h-4 !bg-gray-600 !border-2 !border-gray-800 hover:!bg-amber-500 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
        <div class="absolute bottom-0 left-1/2 transform translate-y-2 -translate-x-1/2">
            <Handle type="source" position="bottom" class="!w-4 !h-4 !bg-amber-500 !border-2 !border-gray-800 hover:!bg-amber-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
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
