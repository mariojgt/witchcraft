<template>
    <div :class="`bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-xl p-5 min-w-[320px] relative shadow-2xl transition-all duration-300 node-${data.colorTheme || 'cyan'}`">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center border transition-all duration-300"
                     :class="getHeaderIconClass()">
                    <VariableIcon class="w-5 h-5 transition-colors duration-300" :class="getIconColorClass()" />
                </div>
                <div class="flex-1 min-w-0">
                    <!-- Editable Title -->
                    <div v-if="!isEditingTitle"
                         @click="startEditingTitle"
                         class="font-semibold text-white text-sm cursor-pointer hover:text-cyan-400 transition-colors truncate"
                         :title="data.customTitle || 'Flow Variable'">
                        {{ data.customTitle || 'Flow Variable' }}
                    </div>
                    <input v-else
                           v-model="editingTitle"
                           @blur="finishEditingTitle"
                           @keydown.enter="finishEditingTitle"
                           @keydown.escape="cancelEditingTitle"
                           ref="titleInput"
                           class="font-semibold text-white text-sm bg-transparent border-b border-cyan-500 outline-none w-full"
                           placeholder="Enter custom title" />

                    <div class="flex items-center gap-2 mt-0.5">
                        <p class="text-xs text-gray-400 leading-none truncate">
                            {{ data.customDescription || 'Access workflow variables' }}
                        </p>
                        <!-- Comment indicator -->
                        <div v-if="data.comment && data.comment.trim()"
                             class="relative comment-indicator"
                             @mouseenter="showTooltip = true"
                             @mouseleave="showTooltip = false">
                            <div class="w-3 h-3 bg-cyan-500/30 border border-cyan-500/50 rounded-full flex items-center justify-center">
                                <MessageSquareIcon class="w-2 h-2 text-cyan-400" />
                            </div>

                            <!-- Hover Tooltip -->
                            <div v-if="showTooltip"
                                 class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 z-50">
                                <div class="bg-gray-900/95 backdrop-blur-sm border border-gray-700 rounded-lg p-3 shadow-xl max-w-xs">
                                    <div class="text-xs font-medium text-cyan-400 mb-1">Comment:</div>
                                    <div class="text-xs text-gray-200 whitespace-pre-wrap">{{ data.comment }}</div>
                                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gray-900 border-r border-b border-gray-700 rotate-45"></div>
                                </div>
                            </div>
                        </div>

                        <!-- ✨ NEW: Execution Status Indicator -->
                        <div v-if="isCurrentlyExecuting" class="w-3 h-3 bg-blue-500 rounded-full animate-pulse" title="Currently Executing"></div>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-1 ml-2">
                <!-- Variable browser button -->
                <button @click="toggleVariableBrowser"
                        :class="`w-8 h-8 rounded-lg transition-all duration-200 flex items-center justify-center group ${
                            showVariableBrowser
                                ? 'bg-cyan-500/20 text-cyan-400 hover:bg-cyan-500/30'
                                : 'hover:bg-cyan-500/10 text-gray-400 hover:text-cyan-400'
                        }`"
                        title="Browse Variables">
                    <SearchIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
                </button>

                <!-- Comment button -->
                <button @click="toggleComment"
                        :class="`w-8 h-8 rounded-lg transition-all duration-200 flex items-center justify-center group ${
                            data.comment && data.comment.trim()
                                ? 'bg-cyan-500/20 text-cyan-400 hover:bg-cyan-500/30'
                                : 'hover:bg-cyan-500/10 text-gray-400 hover:text-cyan-400'
                        }`"
                        title="Add/Edit Comment">
                    <MessageSquareIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
                </button>

                <!-- Settings button -->
                <button @click="toggleSettings"
                        class="w-8 h-8 rounded-lg hover:bg-cyan-500/10 text-gray-400 hover:text-cyan-400 transition-all duration-200 flex items-center justify-center group"
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

        <!-- Variable Browser Panel -->
        <div v-if="showVariableBrowser" class="mb-4 p-3 bg-cyan-500/5 border border-cyan-500/20 rounded-lg space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <SearchIcon class="w-4 h-4 text-cyan-400" />
                    <span class="text-sm font-medium text-cyan-400">Available Variables</span>
                </div>
                <span class="text-xs text-gray-500 bg-gray-800 px-2 py-1 rounded">
                    {{ Object.keys(variables || {}).length }} variables
                </span>
            </div>

            <!-- Search filter -->
            <div class="relative">
                <input v-model="variableSearchQuery"
                       placeholder="Search variables..."
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 pl-9 text-white placeholder-gray-500 focus:bg-white/10 focus:border-cyan-500/50 focus:ring-2 focus:ring-cyan-500/20 transition-all duration-200 outline-none" />
                <SearchIcon class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" />
            </div>

            <!-- Variables list -->
            <div class="max-h-48 overflow-y-auto space-y-1">
                <div v-if="filteredVariables.length === 0" class="text-center py-4 text-gray-400">
                    <div class="text-sm">No variables found</div>
                    <div class="text-xs">Variables will appear during workflow execution</div>
                </div>
                <div v-for="(value, key) in filteredVariables"
                     :key="key"
                     @click="selectVariable(key)"
                     :class="`p-2 rounded cursor-pointer transition-all duration-200 ${
                         data.variableName === key
                             ? 'bg-cyan-500/20 border border-cyan-500/30'
                             : 'bg-white/5 hover:bg-white/10 border border-white/10'
                     }`">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <code class="text-cyan-300 text-sm font-mono">{{ key }}</code>
                                <span class="text-xs text-gray-400 bg-gray-800 px-1.5 py-0.5 rounded">
                                    {{ getValueType(value) }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-300 mt-1 truncate">
                                {{ formatPreviewValue(value) }}
                            </div>
                        </div>
                        <CopyIcon v-if="data.variableName === key"
                                  class="w-4 h-4 text-cyan-400 flex-shrink-0" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Panel -->
        <div v-if="showSettings" class="mb-4 p-3 bg-white/5 border border-white/10 rounded-lg space-y-3">
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Custom Title</label>
                <input v-model="data.customTitle"
                       placeholder="Enter custom node title"
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:bg-white/10 focus:border-cyan-500/50 focus:ring-2 focus:ring-cyan-500/20 transition-all duration-200 outline-none" />
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Custom Description</label>
                <input v-model="data.customDescription"
                       placeholder="Enter custom description"
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:bg-white/10 focus:border-cyan-500/50 focus:ring-2 focus:ring-cyan-500/20 transition-all duration-200 outline-none" />
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
        <div v-if="showComment" class="mb-4 p-3 bg-cyan-500/5 border border-cyan-500/20 rounded-lg">
            <div class="flex items-center gap-2 mb-2">
                <MessageSquareIcon class="w-4 h-4 text-cyan-400" />
                <span class="text-sm font-medium text-cyan-400">Comment</span>
            </div>
            <textarea v-model="data.comment"
                      placeholder="Add a comment to help document this node's purpose..."
                      rows="3"
                      class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-500 resize-none focus:bg-white/10 focus:border-cyan-500/50 focus:ring-2 focus:ring-cyan-500/20 transition-all duration-200 outline-none">
            </textarea>
            <div class="flex justify-end gap-2 mt-2">
                <button @click="showComment = false"
                        class="px-3 py-1 text-xs text-gray-400 hover:text-white transition-colors">
                    Cancel
                </button>
                <button @click="saveComment"
                        class="px-3 py-1 text-xs bg-cyan-500/20 text-cyan-400 hover:bg-cyan-500/30 rounded transition-colors">
                    Save
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="space-y-4">
            <!-- Variable Selection -->
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">
                    Variable Name
                    <span v-if="Object.keys(variables || {}).length > 0" class="text-gray-500">
                        ({{ Object.keys(variables || {}).length }} available)
                    </span>
                </label>
                <div class="relative">
                    <input v-model="data.variableName"
                           placeholder="Select or type variable name"
                           class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-cyan-500/50 focus:ring-2 focus:ring-cyan-500/20 transition-all duration-200 outline-none"
                           @focus="showVariableBrowser = true" />

                    <!-- Variable exists indicator -->
                    <div v-if="data.variableName && variableExists"
                         class="absolute right-3 top-1/2 transform -translate-y-1/2">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    </div>
                    <div v-else-if="data.variableName && !variableExists"
                         class="absolute right-3 top-1/2 transform -translate-y-1/2">
                        <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                    </div>
                </div>
            </div>

            <!-- Path extraction (optional) -->
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">
                    Path (Optional)
                    <span class="text-gray-500">- Extract specific property</span>
                </label>
                <input v-model="data.extractPath"
                       placeholder="e.g., user.email or data.0.name"
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-cyan-500/50 focus:ring-2 focus:ring-cyan-500/20 transition-all duration-200 outline-none" />
            </div>

            <!-- Default value -->
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Default Value</label>
                <input v-model="data.defaultValue"
                       placeholder="Value if variable not found"
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-cyan-500/50 focus:ring-2 focus:ring-cyan-500/20 transition-all duration-200 outline-none" />
            </div>

            <!-- Options -->
            <div class="grid grid-cols-1 gap-3 pt-2">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative">
                        <input type="checkbox" v-model="data.failIfNotFound" class="sr-only" />
                        <div class="w-4 h-4 border-2 border-white/20 rounded group-hover:border-cyan-400/50 transition-colors flex items-center justify-center"
                             :class="data.failIfNotFound ? 'bg-cyan-500 border-cyan-500' : ''">
                            <CheckIcon v-if="data.failIfNotFound" class="w-3 h-3 text-white" />
                        </div>
                    </div>
                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">Fail if variable not found</span>
                </label>
            </div>

            <!-- ✨ ENHANCED Live Preview with Simulation Data -->
            <div class="bg-white/5 border border-white/10 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-2">
                    <span class="text-gray-300 font-medium">Live Preview:</span>
                    <span v-if="isCurrentlyExecuting" class="ml-2 text-blue-400">● Currently Executing</span>
                </div>

                <div v-if="!data.variableName" class="text-xs text-gray-500">
                    Select a variable to see preview
                </div>

                <div v-else-if="!variableExists" class="text-xs text-yellow-400">
                    Variable "{{ data.variableName }}" not found
                    <div v-if="data.defaultValue" class="text-gray-400 mt-1">
                        Will use default: <span class="text-yellow-300">{{ data.defaultValue }}</span>
                    </div>
                </div>

                <div v-else class="space-y-2">
                    <div class="text-xs">
                        <span class="text-cyan-400 font-medium">{{ data.variableName }}:</span>
                        <span class="text-green-400 ml-2">{{ getValueType(currentValue) }}</span>
                    </div>

                    <div class="bg-gray-800 p-2 rounded text-xs">
                        <pre class="text-green-300 whitespace-pre-wrap">{{ formatDisplayValue(currentValue) }}</pre>
                    </div>

                    <div v-if="data.extractPath && extractedValue !== currentValue" class="space-y-1">
                        <div class="text-xs text-cyan-400">Extracted value ({{ data.extractPath }}):</div>
                        <div class="bg-gray-800 p-2 rounded text-xs">
                            <pre class="text-yellow-300 whitespace-pre-wrap">{{ formatDisplayValue(extractedValue) }}</pre>
                        </div>
                    </div>
                </div>

                <!-- ✨ NEW: Recent Activity Logs for this Variable -->
                <div v-if="variableLogs.length > 0" class="mt-3 pt-2 border-t border-gray-700">
                    <div class="text-xs text-gray-400 mb-1">Recent Activity:</div>
                    <div class="space-y-1 max-h-20 overflow-y-auto">
                        <div v-for="log in variableLogs.slice(-3)"
                             :key="log.timestamp"
                             class="text-xs p-1 rounded"
                             :class="{
                                 'text-green-300': log.type === 'success',
                                 'text-red-300': log.type === 'error',
                                 'text-yellow-300': log.type === 'warning',
                                 'text-blue-300': log.type === 'info',
                                 'text-gray-300': !log.type
                             }">
                            {{ log.message }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connection Points -->
        <div class="absolute top-1/2 -left-2 transform -translate-y-1/2">
            <Handle type="target" position="left" class="!w-4 !h-4 !bg-gray-600 !border-2 !border-gray-800 hover:!bg-cyan-500 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
        <div class="absolute top-1/2 -right-2 transform -translate-y-1/2">
            <Handle type="source" position="right" class="!w-4 !h-4 !bg-cyan-500 !border-2 !border-gray-800 hover:!bg-cyan-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
    </div>
</template>

<script setup>
import { computed, ref, nextTick } from 'vue'
import { Handle } from '@vue-flow/core'
import {
    XIcon, VariableIcon, CheckIcon, MessageSquareIcon, SettingsIcon,
    SearchIcon, CopyIcon
} from 'lucide-vue-next'

// ✨ ENHANCED Props to include simulation data
const props = defineProps([
    'data',
    'id',
    'variables',          // ✨ Live simulation variables
    'simulationLogs',     // ✨ All simulation logs
    'currentNodeId'       // ✨ Currently executing node
])
defineEmits(['delete'])

// UI state
const showComment = ref(false)
const showSettings = ref(false)
const showVariableBrowser = ref(false)
const isEditingTitle = ref(false)
const editingTitle = ref("")
const titleInput = ref(null)
const showTooltip = ref(false)
const variableSearchQuery = ref("")

// Color themes
const colorThemes = [
    { name: 'cyan', label: 'Cyan', gradient: 'linear-gradient(145deg, #06b6d4, #0891b2)' },
    { name: 'blue', label: 'Blue', gradient: 'linear-gradient(145deg, #3b82f6, #1d4ed8)' },
    { name: 'purple', label: 'Purple', gradient: 'linear-gradient(145deg, #8b5cf6, #7c3aed)' },
    { name: 'green', label: 'Green', gradient: 'linear-gradient(145deg, #10b981, #059669)' },
    { name: 'yellow', label: 'Yellow', gradient: 'linear-gradient(145deg, #f59e0b, #d97706)' },
    { name: 'red', label: 'Red', gradient: 'linear-gradient(145deg, #ef4444, #dc2626)' },
    { name: 'pink', label: 'Pink', gradient: 'linear-gradient(145deg, #ec4899, #db2777)' },
    { name: 'gray', label: 'Gray', gradient: 'linear-gradient(145deg, #6b7280, #4b5563)' }
]

// ✨ ENHANCED Computed properties using simulation data
const variableExists = computed(() => {
    return props.data.variableName && props.variables && props.variables.hasOwnProperty(props.data.variableName)
})

const currentValue = computed(() => {
    if (!variableExists.value) return null
    return props.variables[props.data.variableName]
})

const extractedValue = computed(() => {
    if (!currentValue.value || !props.data.extractPath) return currentValue.value

    try {
        const path = props.data.extractPath.split('.')
        let value = currentValue.value

        for (const key of path) {
            if (value === null || value === undefined) return null

            // Handle array indices
            if (/^\d+$/.test(key)) {
                const index = parseInt(key)
                value = Array.isArray(value) ? value[index] : null
            } else {
                value = typeof value === 'object' ? value[key] : null
            }
        }

        return value
    } catch (error) {
        return null
    }
})

// ✨ NEW: Check if this node is currently executing
const isCurrentlyExecuting = computed(() => {
    return props.currentNodeId === props.id
})

// ✨ NEW: Get logs related to this variable
const variableLogs = computed(() => {
    if (!props.data.variableName || !props.simulationLogs) return []

    return props.simulationLogs.filter(log =>
        log.message && log.message.includes(props.data.variableName)
    )
})

const filteredVariables = computed(() => {
    if (!props.variables) return {}

    if (!variableSearchQuery.value) return props.variables

    const query = variableSearchQuery.value.toLowerCase()
    const filtered = {}

    Object.entries(props.variables).forEach(([key, value]) => {
        if (key.toLowerCase().includes(query) ||
            String(value).toLowerCase().includes(query)) {
            filtered[key] = value
        }
    })

    return filtered
})

// Utility functions
function getValueType(value) {
    if (value === null) return 'null'
    if (value === undefined) return 'undefined'
    if (Array.isArray(value)) return `array[${value.length}]`
    if (typeof value === 'object') return 'object'
    return typeof value
}

function formatPreviewValue(value) {
    if (value === null) return 'null'
    if (value === undefined) return 'undefined'

    const str = typeof value === 'object' ? JSON.stringify(value) : String(value)
    return str.length > 50 ? str.substring(0, 50) + '...' : str
}

function formatDisplayValue(value) {
    if (value === null) return 'null'
    if (value === undefined) return 'undefined'

    if (typeof value === 'object') {
        return JSON.stringify(value, null, 2)
    }

    return String(value)
}

function selectVariable(variableName) {
    props.data.variableName = variableName
    showVariableBrowser.value = false
}

// Color theme functions
function getHeaderIconClass() {
    const theme = props.data.colorTheme || 'cyan'
    const classes = {
        cyan: 'bg-gradient-to-br from-cyan-500/20 to-cyan-600/20 border-cyan-500/20',
        blue: 'bg-gradient-to-br from-blue-500/20 to-blue-600/20 border-blue-500/20',
        purple: 'bg-gradient-to-br from-purple-500/20 to-purple-600/20 border-purple-500/20',
        green: 'bg-gradient-to-br from-green-500/20 to-green-600/20 border-green-500/20',
        yellow: 'bg-gradient-to-br from-yellow-500/20 to-yellow-600/20 border-yellow-500/20',
        red: 'bg-gradient-to-br from-red-500/20 to-red-600/20 border-red-500/20',
        pink: 'bg-gradient-to-br from-pink-500/20 to-pink-600/20 border-pink-500/20',
        gray: 'bg-gradient-to-br from-gray-500/20 to-gray-600/20 border-gray-500/20'
    }
    return classes[theme] || classes.cyan
}

function getIconColorClass() {
    const theme = props.data.colorTheme || 'cyan'
    const classes = {
        cyan: 'text-cyan-400',
        blue: 'text-blue-400',
        purple: 'text-purple-400',
        green: 'text-green-400',
        yellow: 'text-yellow-400',
        red: 'text-red-400',
        pink: 'text-pink-400',
        gray: 'text-gray-400'
    }
    return classes[theme] || classes.cyan
}

// UI functions
function toggleComment() {
    showComment.value = !showComment.value
    showSettings.value = false
    showVariableBrowser.value = false
}

function saveComment() {
    showComment.value = false
}

function toggleSettings() {
    showSettings.value = !showSettings.value
    showComment.value = false
    showVariableBrowser.value = false
}

function toggleVariableBrowser() {
    showVariableBrowser.value = !showVariableBrowser.value
    showComment.value = false
    showSettings.value = false
}

function startEditingTitle() {
    isEditingTitle.value = true
    editingTitle.value = props.data.customTitle || 'Flow Variable'
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
</script>

<script>
// ✨ Node metadata for auto-registration
export default {
    nodeMetadata: {
        category: 'Data',
        icon: 'VariableIcon',
        label: 'Flow Variable',
        description: 'Access workflow variables with live preview',
        initialData: {
            variableName: '',
            extractPath: '',
            defaultValue: '',
            failIfNotFound: false,
            customTitle: '',
            customDescription: '',
            comment: '',
            colorTheme: 'cyan'
        }
    }
}
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
