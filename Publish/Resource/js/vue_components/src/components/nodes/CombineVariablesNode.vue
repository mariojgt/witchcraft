<template>
    <div :class="`bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-xl p-5 min-w-[320px] relative shadow-2xl transition-all duration-300 node-${data.colorTheme || 'purple'}`">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center border transition-all duration-300"
                     :class="getHeaderIconClass()">
                    <LayersIcon class="w-5 h-5 transition-colors duration-300" :class="getIconColorClass()" />
                </div>
                <div class="flex-1 min-w-0">
                    <div v-if="!isEditingTitle"
                         @click="startEditingTitle"
                         class="font-semibold text-white text-sm cursor-pointer hover:text-purple-400 transition-colors truncate"
                         :title="data.customTitle || 'Combine Variables'">
                        {{ data.customTitle || 'Combine Variables' }}
                    </div>
                    <input v-else
                           v-model="editingTitle"
                           @blur="finishEditingTitle"
                           @keydown.enter="finishEditingTitle"
                           @keydown.escape="cancelEditingTitle"
                           ref="titleInput"
                           class="font-semibold text-white text-sm bg-transparent border-b border-purple-500 outline-none w-full"
                           placeholder="Enter custom title" />

                    <div class="flex items-center gap-2 mt-0.5">
                        <p class="text-xs text-gray-400 leading-none truncate">
                            {{ data.customDescription || 'Combine multiple variables into array or object' }}
                        </p>
                        <div v-if="data.comment && data.comment.trim()"
                             class="relative comment-indicator"
                             @mouseenter="showTooltip = true"
                             @mouseleave="showTooltip = false">
                            <div class="w-3 h-3 bg-purple-500/30 border border-purple-500/50 rounded-full flex items-center justify-center">
                                <MessageSquareIcon class="w-2 h-2 text-purple-400" />
                            </div>

                            <div v-if="showTooltip"
                                 class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 z-50">
                                <div class="bg-gray-900/95 backdrop-blur-sm border border-gray-700 rounded-lg p-3 shadow-xl max-w-xs">
                                    <div class="text-xs font-medium text-purple-400 mb-1">Comment:</div>
                                    <div class="text-xs text-gray-200 whitespace-pre-wrap">{{ data.comment }}</div>
                                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gray-900 border-r border-b border-gray-700 rotate-45"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-1 ml-2">
                <!-- Variable browser button -->
                <button @click="toggleVariableBrowser"
                        :class="`w-8 h-8 rounded-lg transition-all duration-200 flex items-center justify-center group ${
                            showVariableBrowser
                                ? 'bg-purple-500/20 text-purple-400 hover:bg-purple-500/30'
                                : 'hover:bg-purple-500/10 text-gray-400 hover:text-purple-400'
                        }`"
                        title="Browse Variables">
                    <SearchIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
                </button>

                <button @click="toggleComment"
                        :class="`w-8 h-8 rounded-lg transition-all duration-200 flex items-center justify-center group ${
                            data.comment && data.comment.trim()
                                ? 'bg-purple-500/20 text-purple-400 hover:bg-purple-500/30'
                                : 'hover:bg-purple-500/10 text-gray-400 hover:text-purple-400'
                        }`"
                        title="Add/Edit Comment">
                    <MessageSquareIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
                </button>

                <button @click="toggleSettings"
                        class="w-8 h-8 rounded-lg hover:bg-purple-500/10 text-gray-400 hover:text-purple-400 transition-all duration-200 flex items-center justify-center group"
                        title="Node Settings">
                    <SettingsIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
                </button>
                <BreakpointToggle
                    :node-id="id"
                    variant="default"
                />
                <button @click="$emit('delete')"
                        class="w-8 h-8 rounded-lg hover:bg-red-500/10 text-gray-400 hover:text-red-400 transition-all duration-200 flex items-center justify-center group">
                    <XIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
                </button>
            </div>
        </div>

        <!-- Variable Browser Panel -->
        <div v-if="showVariableBrowser" class="mb-4 p-3 bg-purple-500/5 border border-purple-500/20 rounded-lg space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <SearchIcon class="w-4 h-4 text-purple-400" />
                    <span class="text-sm font-medium text-purple-400">Available Variables</span>
                </div>
                <span class="text-xs text-gray-500 bg-gray-800 px-2 py-1 rounded">
                    {{ Object.keys(variables || {}).length }} variables
                </span>
            </div>

            <div class="max-h-32 overflow-y-auto space-y-1">
                <div v-if="Object.keys(variables || {}).length === 0" class="text-center py-4 text-gray-400">
                    <div class="text-sm">No variables available</div>
                </div>
                <div v-for="(value, key) in variables"
                     :key="key"
                     @click="addVariableToList(key)"
                     class="p-2 rounded cursor-pointer transition-all duration-200 bg-white/5 hover:bg-white/10 border border-white/10">
                    <div class="flex items-center justify-between">
                        <code class="text-purple-300 text-sm font-mono">{{ key }}</code>
                        <span class="text-xs text-gray-400 bg-gray-800 px-1.5 py-0.5 rounded">
                            {{ getValueType(value) }}
                        </span>
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
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none" />
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Custom Description</label>
                <input v-model="data.customDescription"
                       placeholder="Enter custom description"
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none" />
            </div>

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
        <div v-if="showComment" class="mb-4 p-3 bg-purple-500/5 border border-purple-500/20 rounded-lg">
            <div class="flex items-center gap-2 mb-2">
                <MessageSquareIcon class="w-4 h-4 text-purple-400" />
                <span class="text-sm font-medium text-purple-400">Comment</span>
            </div>
            <textarea v-model="data.comment"
                      placeholder="Add a comment to help document this node's purpose..."
                      rows="3"
                      class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-500 resize-none focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none">
            </textarea>
            <div class="flex justify-end gap-2 mt-2">
                <button @click="showComment = false"
                        class="px-3 py-1 text-xs text-gray-400 hover:text-white transition-colors">
                    Cancel
                </button>
                <button @click="saveComment"
                        class="px-3 py-1 text-xs bg-purple-500/20 text-purple-400 hover:bg-purple-500/30 rounded transition-colors">
                    Save
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="space-y-4">
            <!-- Output Configuration -->
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Return Variable Name</label>
                <input v-model="data.returnVariableName"
                       placeholder="combinedResult"
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none" />
                <div class="text-xs text-purple-400/70">
                    Name for the combined result variable
                </div>
            </div>

            <!-- Output Type -->
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Output Type</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="radio" v-model="data.outputType" value="array" class="sr-only" />
                        <div class="w-4 h-4 border-2 border-white/20 rounded-full group-hover:border-purple-400/50 transition-colors flex items-center justify-center"
                             :class="data.outputType === 'array' ? 'bg-purple-500 border-purple-500' : ''">
                            <div v-if="data.outputType === 'array'" class="w-2 h-2 bg-white rounded-full"></div>
                        </div>
                        <span class="text-sm text-gray-300 group-hover:text-white transition-colors">Array</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="radio" v-model="data.outputType" value="object" class="sr-only" />
                        <div class="w-4 h-4 border-2 border-white/20 rounded-full group-hover:border-purple-400/50 transition-colors flex items-center justify-center"
                             :class="data.outputType === 'object' ? 'bg-purple-500 border-purple-500' : ''">
                            <div v-if="data.outputType === 'object'" class="w-2 h-2 bg-white rounded-full"></div>
                        </div>
                        <span class="text-sm text-gray-300 group-hover:text-white transition-colors">Object</span>
                    </label>
                </div>
            </div>

            <!-- Variables to Combine -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-medium text-gray-300 tracking-wide">Variables to Combine</label>
                    <button @click="addVariableEntry"
                            class="text-xs bg-purple-500/20 text-purple-400 hover:bg-purple-500/30 px-2 py-1 rounded transition-colors">
                        <PlusIcon class="w-3 h-3 inline mr-1" />
                        Add
                    </button>
                </div>

                <div class="space-y-2 max-h-48 overflow-y-auto">
                    <div v-for="(variable, index) in data.variablesToCombine"
                         :key="index"
                         class="bg-white/5 border border-white/10 rounded-lg p-3 space-y-2">

                        <div class="flex items-center gap-2">
                            <span class="text-xs bg-gray-700 text-gray-300 px-2 py-1 rounded font-mono">
                                {{ index + 1 }}
                            </span>
                            <input v-if="data.outputType === 'object'"
                                   v-model="variable.key"
                                   placeholder="key"
                                   class="flex-1 text-sm bg-white/5 border border-white/10 rounded px-2 py-1 text-white placeholder-gray-500 focus:border-purple-500 outline-none" />
                            <button @click="removeVariableEntry(index)"
                                    class="text-red-400 hover:text-red-300 transition-colors">
                                <XIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="relative">
                            <input v-model="variable.source"
                                   placeholder="Variable name or path (e.g., user.email)"
                                   @focus="showVariableBrowser = true"
                                   class="w-full text-sm bg-white/5 border border-white/10 rounded px-3 py-2 text-white placeholder-gray-500 focus:border-purple-500 outline-none" />

                            <!-- Variable exists indicator -->
                            <div v-if="variable.source && getVariableExists(variable.source)"
                                 class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            </div>
                            <div v-else-if="variable.source"
                                 class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                            </div>
                        </div>

                        <!-- Live preview for this variable -->
                        <div v-if="variable.source && getVariableValue(variable.source)"
                             class="text-xs bg-gray-800 p-2 rounded">
                            <span class="text-gray-400">Preview:</span>
                            <span class="text-green-300 ml-2">{{ formatPreviewValue(getVariableValue(variable.source)) }}</span>
                        </div>
                    </div>

                    <div v-if="data.variablesToCombine.length === 0"
                         class="text-center py-4 text-gray-400 border border-dashed border-gray-600 rounded-lg">
                        <LayersIcon class="w-6 h-6 mx-auto mb-2 opacity-50" />
                        <p class="text-sm">No variables added</p>
                        <p class="text-xs">Click "Add" to combine variables</p>
                    </div>
                </div>
            </div>

            <!-- Live Preview -->
            <div v-if="getCombinedPreview()" class="bg-white/5 border border-white/10 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-2">
                    <span class="font-medium">Combined Preview:</span>
                    <span class="ml-2 text-purple-400">{{ data.outputType }} with {{ data.variablesToCombine.length }} items</span>
                </div>
                <div class="bg-gray-800 p-2 rounded text-xs">
                    <pre class="text-purple-300 whitespace-pre-wrap">{{ getCombinedPreview() }}</pre>
                </div>
            </div>
        </div>

        <!-- Connection Points -->
        <div class="absolute top-1/2 -left-2 transform -translate-y-1/2">
            <Handle type="target" position="left" class="!w-4 !h-4 !bg-gray-600 !border-2 !border-gray-800 hover:!bg-purple-500 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
        <div class="absolute top-1/2 -right-2 transform -translate-y-1/2">
            <Handle type="source" position="right" class="!w-4 !h-4 !bg-purple-500 !border-2 !border-gray-800 hover:!bg-purple-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
    </div>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import { Handle } from '@vue-flow/core'
import {
    XIcon, MessageSquareIcon, SettingsIcon, LayersIcon,
    SearchIcon, PlusIcon
} from 'lucide-vue-next'
import { defineOptions } from 'vue'
import BreakpointToggle from '../nodeComponents/BreakpointToggle.vue'

const props = defineProps([
    'data',
    'variables',
    'simulationLogs',
    'currentNodeId',
    'id'
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

// Initialize data
function initializeData() {
    if (!props.data.variablesToCombine) props.data.variablesToCombine = []
    if (!props.data.outputType) props.data.outputType = 'array'
    if (!props.data.returnVariableName) props.data.returnVariableName = 'combinedResult'
}

// Variable management functions
function addVariableEntry() {
    const entry = {
        source: '',
        key: props.data.outputType === 'object' ? '' : undefined
    }
    props.data.variablesToCombine.push(entry)
}

function removeVariableEntry(index) {
    props.data.variablesToCombine.splice(index, 1)
}

function addVariableToList(variableName) {
    const entry = {
        source: variableName,
        key: props.data.outputType === 'object' ? variableName : undefined
    }
    props.data.variablesToCombine.push(entry)
    showVariableBrowser.value = false
}

function getVariableExists(source) {
    if (!source || !props.variables) return false
    return getVariableValue(source) !== undefined
}

function getVariableValue(source) {
    if (!source || !props.variables) return undefined

    // Handle dot notation (e.g., user.email)
    if (source.includes('.')) {
        const parts = source.split('.')
        let value = props.variables[parts[0]]

        for (let i = 1; i < parts.length && value != null; i++) {
            if (typeof value === 'object') {
                value = value[parts[i]]
            } else {
                return undefined
            }
        }

        return value
    }

    return props.variables[source]
}

function getCombinedPreview() {
    if (props.data.variablesToCombine.length === 0) return null

    if (props.data.outputType === 'array') {
        const result = []
        props.data.variablesToCombine.forEach(variable => {
            const value = getVariableValue(variable.source)
            result.push(value !== undefined ? value : null)
        })
        return JSON.stringify(result, null, 2)
    } else {
        const result = {}
        props.data.variablesToCombine.forEach(variable => {
            const key = variable.key || variable.source
            const value = getVariableValue(variable.source)
            result[key] = value !== undefined ? value : null
        })
        return JSON.stringify(result, null, 2)
    }
}

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
    return str.length > 30 ? str.substring(0, 30) + '...' : str
}

// Color theme functions
function getHeaderIconClass() {
    const theme = props.data.colorTheme || 'purple'
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
    return classes[theme] || classes.purple
}

function getIconColorClass() {
    const theme = props.data.colorTheme || 'purple'
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
    return classes[theme] || classes.purple
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
    editingTitle.value = props.data.customTitle || 'Combine Variables'
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

// Initialize on mount
initializeData()

defineOptions({
    nodeMetadata: {
        category: 'Data',
        icon: LayersIcon,
        label: 'Combine Variables',
        description: 'Combine multiple variables into array or object',
        initialData: {
            variablesToCombine: [],
            outputType: 'array',
            returnVariableName: 'combinedResult',
            customTitle: '',
            customDescription: '',
            comment: '',
            colorTheme: 'purple'
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

/* Comment indicator hover effects */
.comment-indicator {
    transition: all 0.2s ease-in-out;
}

.comment-indicator:hover {
    transform: scale(1.1);
}

/* Scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #374151;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #4b5563;
}
</style>
