<template>
    <div :class="`bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-xl p-5 min-w-[320px] relative shadow-2xl transition-all duration-300 node-${data.colorTheme || 'pink'}`">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center border transition-all duration-300"
                     :class="getHeaderIconClass()">
                    <WorkflowIcon :class="`w-5 h-5 transition-colors duration-300 ${getIconColorClass()}`" />
                </div>
                <div class="flex-1 min-w-0">
                    <!-- Editable Title -->
                    <div v-if="!isEditingTitle"
                         @click="startEditingTitle"
                         class="font-semibold text-white text-sm cursor-pointer hover:text-pink-400 transition-colors truncate"
                         :title="data.customTitle || 'Trigger Flow'">
                        {{ data.customTitle || 'Trigger Flow' }}
                    </div>
                    <input v-else
                           v-model="editingTitle"
                           @blur="finishEditingTitle"
                           @keydown.enter="finishEditingTitle"
                           @keydown.escape="cancelEditingTitle"
                           ref="titleInput"
                           class="font-semibold text-white text-sm bg-transparent border-b border-pink-500 outline-none w-full"
                           placeholder="Enter custom title" />

                    <div class="flex items-center gap-2 mt-0.5">
                        <p class="text-xs text-gray-400 leading-none truncate">
                            {{ data.customDescription || 'Execute other workflows' }}
                        </p>
                        <!-- Comment indicator with hover tooltip -->
                        <div v-if="data.comment && data.comment.trim()"
                             class="relative comment-indicator"
                             @mouseenter="showTooltip = true"
                             @mouseleave="showTooltip = false">
                            <div class="w-3 h-3 bg-yellow-500/30 border border-yellow-500/50 rounded-full flex items-center justify-center">
                                <MessageSquareIcon class="w-2 h-2 text-yellow-400" />
                            </div>

                            <!-- Hover Tooltip -->
                            <div v-if="showTooltip"
                                 class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 z-50">
                                <div class="bg-gray-900/95 backdrop-blur-sm border border-gray-700 rounded-lg p-3 shadow-xl max-w-xs">
                                    <div class="text-xs font-medium text-yellow-400 mb-1">Comment:</div>
                                    <div class="text-xs text-gray-200 whitespace-pre-wrap">{{ data.comment }}</div>
                                    <!-- Tooltip arrow -->
                                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gray-900 border-r border-b border-gray-700 rotate-45"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-1 ml-2">
                <!-- Comment button -->
                <button @click="toggleComment"
                        :class="`w-8 h-8 rounded-lg transition-all duration-200 flex items-center justify-center group ${
                            data.comment && data.comment.trim()
                                ? 'bg-yellow-500/20 text-yellow-400 hover:bg-yellow-500/30'
                                : 'hover:bg-yellow-500/10 text-gray-400 hover:text-yellow-400'
                        }`"
                        title="Add/Edit Comment">
                    <MessageSquareIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
                </button>

                <!-- Settings button -->
                <button @click="toggleSettings"
                        class="w-8 h-8 rounded-lg hover:bg-pink-500/10 text-gray-400 hover:text-pink-400 transition-all duration-200 flex items-center justify-center group"
                        title="Node Settings">
                    <SettingsIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
                </button>

                <!-- Delete button -->
                <button @click="$emit('delete')"
                        class="w-8 h-8 rounded-lg hover:bg-red-500/10 text-gray-400 hover:text-red-400 transition-all duration-200 flex items-center justify-center group">
                    <XIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
                </button>
            </div>
        </div>

        <!-- Settings Panel -->
        <div v-if="showSettings" class="mb-4 p-3 bg-white/5 border border-white/10 rounded-lg space-y-3">
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Custom Title</label>
                <input v-model="data.customTitle"
                       placeholder="Enter custom node title"
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:bg-white/10 focus:border-pink-500/50 focus:ring-2 focus:ring-pink-500/20 transition-all duration-200 outline-none" />
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Custom Description</label>
                <input v-model="data.customDescription"
                       placeholder="Enter custom description"
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:bg-white/10 focus:border-pink-500/50 focus:ring-2 focus:ring-pink-500/20 transition-all duration-200 outline-none" />
            </div>

            <!-- Color Theme -->
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Color Theme</label>
                <div class="flex gap-2">
                    <button v-for="color in colorThemes" :key="color.name"
                            @click="data.colorTheme = color.name"
                            :class="`w-6 h-6 rounded-lg border-2 transition-all duration-200 ${
                                data.colorTheme === color.name
                                    ? 'border-white scale-110'
                                    : 'border-gray-600 hover:border-gray-500'
                            }`"
                            :style="{ background: color.gradient }"
                            :title="color.label">
                    </button>
                </div>
            </div>
        </div>

        <!-- Comment Panel -->
        <div v-if="showComment" class="mb-4 p-3 bg-yellow-500/5 border border-yellow-500/20 rounded-lg">
            <div class="flex items-center gap-2 mb-2">
                <MessageSquareIcon class="w-4 h-4 text-yellow-400" />
                <span class="text-sm font-medium text-yellow-400">Comment</span>
            </div>
            <textarea v-model="data.comment"
                      placeholder="Add a comment to help document this node's purpose..."
                      rows="3"
                      class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-500 resize-none focus:bg-white/10 focus:border-yellow-500/50 focus:ring-2 focus:ring-yellow-500/20 transition-all duration-200 outline-none">
            </textarea>
            <div class="flex justify-end gap-2 mt-2">
                <button @click="showComment = false"
                        class="px-3 py-1 text-xs text-gray-400 hover:text-white transition-colors">
                    Cancel
                </button>
                <button @click="saveComment"
                        class="px-3 py-1 text-xs bg-yellow-500/20 text-yellow-400 hover:bg-yellow-500/30 rounded transition-colors">
                    Save
                </button>
            </div>
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
import { ref, onMounted, computed, nextTick } from 'vue'
import { Handle } from '@vue-flow/core'
import { XIcon, WorkflowIcon, SearchIcon, CheckIcon, MessageSquareIcon, SettingsIcon } from 'lucide-vue-next'
import { defineOptions } from 'vue'

const props = defineProps(['data', 'variables'])
const emit = defineEmits(['delete'])

// Flow search state
const flowSearchQuery = ref('')
const showFlowDropdown = ref(false)
const availableFlows = ref([])
const isLoadingFlows = ref(false)

// UI state
const showComment = ref(false)
const showSettings = ref(false)
const isEditingTitle = ref(false)
const editingTitle = ref("")
const titleInput = ref(null)
const showTooltip = ref(false)

// Color themes
const colorThemes = [
    { name: 'purple', label: 'Purple', gradient: 'linear-gradient(145deg, #8b5cf6, #7c3aed)' },
    { name: 'blue', label: 'Blue', gradient: 'linear-gradient(145deg, #3b82f6, #1d4ed8)' },
    { name: 'green', label: 'Green', gradient: 'linear-gradient(145deg, #10b981, #059669)' },
    { name: 'red', label: 'Red', gradient: 'linear-gradient(145deg, #ef4444, #dc2626)' },
    { name: 'yellow', label: 'Yellow', gradient: 'linear-gradient(145deg, #f59e0b, #d97706)' },
    { name: 'pink', label: 'Pink', gradient: 'linear-gradient(145deg, #ec4899, #db2777)' },
    { name: 'gray', label: 'Gray', gradient: 'linear-gradient(145deg, #6b7280, #4b5563)' },
    { name: 'orange', label: 'Orange', gradient: 'linear-gradient(145deg, #f97316, #ea580c)' }
]

// Color theme functions
function getHeaderIconClass() {
    const theme = props.data.colorTheme || 'pink'
    const classes = {
        purple: 'bg-gradient-to-br from-purple-500/20 to-purple-600/20 border-purple-500/20',
        blue: 'bg-gradient-to-br from-blue-500/20 to-blue-600/20 border-blue-500/20',
        green: 'bg-gradient-to-br from-green-500/20 to-green-600/20 border-green-500/20',
        red: 'bg-gradient-to-br from-red-500/20 to-red-600/20 border-red-500/20',
        yellow: 'bg-gradient-to-br from-yellow-500/20 to-yellow-600/20 border-yellow-500/20',
        pink: 'bg-gradient-to-br from-pink-500/20 to-pink-600/20 border-pink-500/20',
        gray: 'bg-gradient-to-br from-gray-500/20 to-gray-600/20 border-gray-500/20',
        orange: 'bg-gradient-to-br from-orange-500/20 to-orange-600/20 border-orange-500/20'
    }
    return classes[theme] || classes.pink
}

function getIconColorClass() {
    const theme = props.data.colorTheme || 'pink'
    const classes = {
        purple: 'text-purple-400',
        blue: 'text-blue-400',
        green: 'text-green-400',
        red: 'text-red-400',
        yellow: 'text-yellow-400',
        pink: 'text-pink-400',
        gray: 'text-gray-400',
        orange: 'text-orange-400'
    }
    return classes[theme] || classes.pink
}

// Comment functions
function toggleComment() {
    showComment.value = !showComment.value
    showSettings.value = false
}

function saveComment() {
    showComment.value = false
}

// Settings functions
function toggleSettings() {
    showSettings.value = !showSettings.value
    showComment.value = false
}

// Title editing functions
function startEditingTitle() {
    isEditingTitle.value = true
    editingTitle.value = props.data.customTitle || 'Trigger Flow'
    nextTick(() => {
        titleInput.value?.focus()
        titleInput.value?.select()
    })
}

function finishEditingTitle() {
    props.data.customTitle = editingTitle.value.trim()
    isEditingTitle.value = false
}

function cancelEditingTitle() {
    isEditingTitle.value = false
    editingTitle.value = ""
}

// Available variables from current workflow
const availableVariables = computed(() => {
    if (!props.variables) return []
    return Object.keys(props.variables)
})

// Load available flows on mount
onMounted(async () => {
    await loadFlows()
    document.addEventListener('click', handleClickOutside)
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
            async: false,
            // Enhanced properties
            customTitle: "",
            customDescription: "",
            comment: "",
            colorTheme: "pink"
        }
    }
})
</script>

<style scoped>
/* Dynamic color theming */
.node-purple {
    box-shadow: 0 0 0 1px rgba(139, 92, 246, 0.1);
}
.node-purple:hover {
    box-shadow: 0 25px 50px -12px rgba(139, 92, 246, 0.1);
    border-color: rgba(139, 92, 246, 0.3);
}

.node-blue {
    box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.1);
}
.node-blue:hover {
    box-shadow: 0 25px 50px -12px rgba(59, 130, 246, 0.1);
    border-color: rgba(59, 130, 246, 0.3);
}

.node-green {
    box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.1);
}
.node-green:hover {
    box-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.1);
    border-color: rgba(16, 185, 129, 0.3);
}

.node-red {
    box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.1);
}
.node-red:hover {
    box-shadow: 0 25px 50px -12px rgba(239, 68, 68, 0.1);
    border-color: rgba(239, 68, 68, 0.3);
}

.node-yellow {
    box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.1);
}
.node-yellow:hover {
    box-shadow: 0 25px 50px -12px rgba(245, 158, 11, 0.1);
    border-color: rgba(245, 158, 11, 0.3);
}

.node-pink {
    box-shadow: 0 0 0 1px rgba(236, 72, 153, 0.1);
}
.node-pink:hover {
    box-shadow: 0 25px 50px -12px rgba(236, 72, 153, 0.1);
    border-color: rgba(236, 72, 153, 0.3);
}

.node-gray {
    box-shadow: 0 0 0 1px rgba(107, 114, 128, 0.1);
}
.node-gray:hover {
    box-shadow: 0 25px 50px -12px rgba(107, 114, 128, 0.1);
    border-color: rgba(107, 114, 128, 0.3);
}

.node-orange {
    box-shadow: 0 0 0 1px rgba(249, 115, 22, 0.1);
}
.node-orange:hover {
    box-shadow: 0 25px 50px -12px rgba(249, 115, 22, 0.1);
    border-color: rgba(249, 115, 22, 0.3);
}

/* Comment indicator hover effects */
.comment-indicator {
    transition: all 0.2s ease-in-out;
}

.comment-indicator:hover {
    transform: scale(1.1);
}

/* Tooltip animation */
.comment-indicator div[class*="absolute"] {
    animation: tooltipFadeIn 0.2s ease-out;
}

@keyframes tooltipFadeIn {
    from {
        opacity: 0;
        transform: translateX(-50%) translateY(4px);
    }
    to {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
}

/* Better tooltip positioning on smaller screens */
@media (max-width: 640px) {
    .comment-indicator div[class*="absolute"] {
        left: auto;
        right: 0;
        transform: translateX(0) translateY(0);
    }

    .comment-indicator div[class*="absolute"] .absolute {
        left: auto;
        right: 12px;
        transform: translateX(0);
    }
}
</style>
