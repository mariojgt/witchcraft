<template>
    <div class="bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-xl p-5 min-w-[320px] relative shadow-2xl hover:shadow-pink-500/10 transition-all duration-300 hover:border-pink-500/30">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gradient-to-br from-pink-500/20 to-pink-600/20 rounded-lg flex items-center justify-center border border-pink-500/20">
                    <WorkflowIcon class="w-5 h-5 text-pink-400" />
                </div>
                <div>
                    <h3 class="font-semibold text-white text-sm">Trigger Flow</h3>
                    <p class="text-xs text-gray-400 leading-none mt-0.5">Execute other workflows</p>
                </div>
            </div>
            <button @click="$emit('delete')" class="w-8 h-8 rounded-lg hover:bg-red-500/10 text-gray-400 hover:text-red-400 transition-all duration-200 flex items-center justify-center group">
                <XIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
            </button>
        </div>

        <!-- Content -->
        <div class="space-y-4">
            <!-- Flow Selection -->
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Target Flow</label>
                <div class="relative">
                    <input v-model="flowSearchQuery" @input="searchFlows" @focus="showFlowDropdown = true" placeholder="Search and select flow..." class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 pr-10 text-white placeholder-gray-500 focus:bg-white/10 focus:border-pink-500/50 focus:ring-2 focus:ring-pink-500/20 transition-all duration-200 outline-none" />
                    <SearchIcon class="absolute right-3 top-3 w-4 h-4 text-gray-400" />

                    <!-- Dropdown with search results -->
                    <div v-if="showFlowDropdown && filteredFlows.length > 0" class="absolute top-full left-0 right-0 mt-1 bg-white/10 backdrop-blur-xl border border-white/20 rounded-lg shadow-xl z-50 max-h-40 overflow-y-auto">
                        <div v-for="flow in filteredFlows" :key="flow.id" @click="selectFlow(flow)" class="p-3 hover:bg-white/10 cursor-pointer border-b border-white/10 last:border-b-0 transition-all duration-200">
                            <div class="font-medium text-white">{{ flow.name }}</div>
                            <div class="text-xs text-gray-400">ID: {{ flow.id }} • {{ flow.description || 'No description' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Selected flow display -->
                <div v-if="data.selectedFlow" class="bg-white/5 border border-white/10 rounded-lg p-3">
                    <div class="text-sm font-medium text-pink-400">Selected: {{ data.selectedFlow.name }}</div>
                    <div class="text-xs text-gray-400">ID: {{ data.selectedFlow.id }}</div>
                </div>
            </div>

            <!-- Manual Flow ID (alternative) -->
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Or Flow ID</label>
                <input v-model="data.flowId" type="number" placeholder="Enter flow ID manually" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-pink-500/50 focus:ring-2 focus:ring-pink-500/20 transition-all duration-200 outline-none" />
                <div class="text-xs text-pink-400/70">Use this if you know the exact Flow ID</div>
            </div>

            <!-- Simple Variable Selection -->
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-300 tracking-wide">Input Variable</label>
                    <input v-model="data.inputVariable" placeholder="status" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-pink-500/50 focus:ring-2 focus:ring-pink-500/20 transition-all duration-200 outline-none" />
                </div>
                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-300 tracking-wide">Target Variable Name</label>
                    <input v-model="data.targetVariableName" placeholder="dataInput" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-pink-500/50 focus:ring-2 focus:ring-pink-500/20 transition-all duration-200 outline-none" />
                </div>
            </div>

            <!-- Execution Options -->
            <div class="space-y-3">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative">
                        <input type="checkbox" v-model="data.waitForCompletion" class="sr-only" />
                        <div class="w-4 h-4 border-2 border-white/20 rounded group-hover:border-pink-400/50 transition-colors flex items-center justify-center" :class="data.waitForCompletion ? 'bg-pink-500 border-pink-500' : ''">
                            <CheckIcon v-if="data.waitForCompletion" class="w-3 h-3 text-white" />
                        </div>
                    </div>
                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">Wait for Completion</span>
                </label>

                <div v-if="data.waitForCompletion" class="ml-7 space-y-2">
                    <label class="block text-xs font-medium text-gray-300 tracking-wide">Result Variable</label>
                    <input v-model="data.resultVariable" placeholder="flowResult" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-pink-500/50 focus:ring-2 focus:ring-pink-500/20 transition-all duration-200 outline-none" />
                </div>

                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative">
                        <input type="checkbox" v-model="data.async" class="sr-only" />
                        <div class="w-4 h-4 border-2 border-white/20 rounded group-hover:border-pink-400/50 transition-colors flex items-center justify-center" :class="data.async ? 'bg-pink-500 border-pink-500' : ''">
                            <CheckIcon v-if="data.async" class="w-3 h-3 text-white" />
                        </div>
                    </div>
                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">Async Execution</span>
                </label>
            </div>
        </div>

        <!-- Connection Points -->
        <div class="absolute top-1/2 -left-2 transform -translate-y-1/2">
            <Handle type="target" position="left" class="!w-4 !h-4 !bg-gray-600 !border-2 !border-gray-800 hover:!bg-pink-500 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
        <div class="absolute top-1/2 -right-2 transform -translate-y-1/2">
            <Handle type="source" position="right" class="!w-4 !h-4 !bg-pink-500 !border-2 !border-gray-800 hover:!bg-pink-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Handle } from '@vue-flow/core'
import { XIcon, WorkflowIcon, SearchIcon, CheckIcon } from 'lucide-vue-next'
import { defineOptions } from 'vue'

const props = defineProps(['data', 'variables'])
const emit = defineEmits(['delete'])

// Flow search state
const flowSearchQuery = ref('')
const showFlowDropdown = ref(false)
const availableFlows = ref([])
const isLoadingFlows = ref(false)

// Available variables from current workflow
const availableVariables = computed(() => {
    if (!props.variables) return []
    return Object.keys(props.variables)
})

// Load available flows on mount
onMounted(async () => {
    await loadFlows()
})

// Load flows from API
const loadFlows = async () => {
    try {
        isLoadingFlows.value = true
        const response = await fetch('/api/witchcraft/diagrams')
        if (response.ok) {
            availableFlows.value = await response.json()
        }
    } catch (error) {
        console.error('Failed to load flows:', error)
    } finally {
        isLoadingFlows.value = false
    }
}

// Computed filtered flows
const filteredFlows = computed(() => {
    if (!flowSearchQuery.value) return availableFlows.value.slice(0, 10) // Show first 10 if no search

    const query = flowSearchQuery.value.toLowerCase()
    return availableFlows.value.filter(flow =>
        flow.name.toLowerCase().includes(query) ||
        flow.description?.toLowerCase().includes(query) ||
        flow.id.toString().includes(query)
    ).slice(0, 10) // Limit to 10 results
})

// Search flows (debounced)
let searchTimeout
const searchFlows = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        showFlowDropdown.value = true
    }, 300)
}

// Select flow from dropdown
const selectFlow = (flow) => {
    props.data.selectedFlow = flow
    props.data.flowId = flow.id
    flowSearchQuery.value = flow.name
    showFlowDropdown.value = false
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (!event.target.closest('.relative')) {
        showFlowDropdown.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

defineOptions({
    nodeMetadata: {
        category: 'Flow Control',
        icon: WorkflowIcon,
        label: 'Trigger Flow',
        initialData: {
            flowId: '',
            selectedFlow: null,
            inputVariable: 'status',
            targetVariableName: 'dataInput',
            waitForCompletion: true,
            resultVariable: 'flowResult',
            async: false
        }
    }
})
</script>
