<template>
    <div :class="`bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-xl p-5 min-w-[320px] relative shadow-2xl transition-all duration-300 node-${data.colorTheme || 'green'}`">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center border transition-all duration-300"
                     :class="getHeaderIconClass()">
                    <DatabaseIcon :class="`w-5 h-5 transition-colors duration-300 ${getIconColorClass()}`" />
                </div>
                <div class="flex-1 min-w-0">
                    <!-- Editable Title -->
                    <div v-if="!isEditingTitle"
                         @click="startEditingTitle"
                         class="font-semibold text-white text-sm cursor-pointer hover:text-green-400 transition-colors truncate"
                         :title="data.customTitle || 'Trigger'">
                        {{ data.customTitle || 'Trigger' }}
                    </div>
                    <input v-else
                           v-model="editingTitle"
                           @blur="finishEditingTitle"
                           @keydown.enter="finishEditingTitle"
                           @keydown.escape="cancelEditingTitle"
                           ref="titleInput"
                           class="font-semibold text-white text-sm bg-transparent border-b border-green-500 outline-none w-full"
                           placeholder="Enter custom title" />

                    <div class="flex items-center gap-2 mt-0.5">
                        <p class="text-xs text-gray-400 leading-none truncate">
                            {{ data.customDescription || `Trigger with ${data.variables?.length || 0} variables` }}
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

                        <!-- ✨ NEW: Execution Status Indicator -->
                        <div v-if="isCurrentlyExecuting" class="w-3 h-3 bg-blue-500 rounded-full animate-pulse" title="Currently Executing"></div>
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
                        class="w-8 h-8 rounded-lg hover:bg-green-500/10 text-gray-400 hover:text-green-400 transition-all duration-200 flex items-center justify-center group"
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
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:bg-white/10 focus:border-green-500/50 focus:ring-2 focus:ring-green-500/20 transition-all duration-200 outline-none" />
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Custom Description</label>
                <input v-model="data.customDescription"
                       placeholder="Enter custom description"
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:bg-white/10 focus:border-green-500/50 focus:ring-2 focus:ring-green-500/20 transition-all duration-200 outline-none" />
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

        <!-- ✨ NEW: Multiple Variables Section -->
        <div class="space-y-4">
            <!-- Header with Add Button -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <label class="block text-xs font-medium text-gray-300 tracking-wide">Variables</label>
                    <span class="text-xs text-gray-500 bg-gray-800 px-2 py-0.5 rounded">
                        {{ data.variables?.length || 0 }} items
                    </span>
                </div>
                <button @click="addVariable"
                        class="flex items-center gap-1 px-2 py-1 text-xs bg-green-500/20 text-green-400 hover:bg-green-500/30 rounded transition-colors">
                    <PlusIcon class="w-3 h-3" />
                    Add Variable
                </button>
            </div>

            <!-- Variables List -->
            <div class="space-y-3 max-h-64 overflow-y-auto">
                <!-- Empty State -->
                <div v-if="!data.variables || data.variables.length === 0"
                     class="text-center py-8 text-gray-400 border border-dashed border-gray-600 rounded-lg">
                    <DatabaseIcon class="w-8 h-8 mx-auto mb-2 text-gray-500" />
                    <div class="text-sm">No variables defined</div>
                    <div class="text-xs mt-1">Click "Add Variable" to get started</div>
                </div>

                <!-- Variable Items -->
                <div v-for="(variable, index) in data.variables"
                     :key="variable.id"
                     class="bg-white/5 border border-white/10 rounded-lg p-3 space-y-3 group hover:bg-white/10 transition-all duration-200">

                    <!-- Variable Header -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 bg-green-500/20 border border-green-500/30 rounded flex items-center justify-center">
                                <span class="text-xs text-green-400 font-mono">{{ index + 1 }}</span>
                            </div>
                            <span class="text-sm text-gray-300">Variable {{ index + 1 }}</span>
                        </div>

                        <div class="flex items-center gap-1">
                            <!-- Collapse/Expand Button -->
                            <button @click="toggleVariableCollapse(index)"
                                    class="w-6 h-6 rounded hover:bg-white/10 flex items-center justify-center transition-colors"
                                    :title="variable.collapsed ? 'Expand' : 'Collapse'">
                                <ChevronDownIcon :class="`w-4 h-4 text-gray-400 transition-transform ${variable.collapsed ? '-rotate-90' : ''}`" />
                            </button>

                            <!-- Delete Button -->
                            <button @click="removeVariable(index)"
                                    class="w-6 h-6 rounded hover:bg-red-500/20 flex items-center justify-center transition-colors group opacity-0 group-hover:opacity-100">
                                <XIcon class="w-4 h-4 text-red-400" />
                            </button>
                        </div>
                    </div>

                    <!-- Variable Fields (Collapsible) -->
                    <div v-if="!variable.collapsed" class="space-y-3">
                        <!-- Variable Name -->
                        <div class="space-y-1">
                            <label class="block text-xs text-gray-400">Variable Name</label>
                            <input v-model="variable.name"
                                   placeholder="e.g., userStatus, temperature, count"
                                   class="w-full text-sm bg-white/5 border border-white/10 rounded px-2 py-1.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-green-500/50 transition-all duration-200 outline-none" />
                        </div>

                        <!-- Initial Value -->
                        <div class="space-y-1">
                            <label class="block text-xs text-gray-400">Initial Value</label>
                            <input v-model="variable.initialValue"
                                   placeholder="e.g., active, 25, hello world"
                                   class="w-full text-sm bg-white/5 border border-white/10 rounded px-2 py-1.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-green-500/50 transition-all duration-200 outline-none" />
                        </div>

                        <!-- Data Type -->
                        <div class="space-y-1">
                            <label class="block text-xs text-gray-400">Data Type</label>
                            <select v-model="variable.type"
                                    class="w-full text-sm bg-white/5 border border-white/10 rounded px-2 py-1.5 text-white focus:bg-white/10 focus:border-green-500/50 transition-all duration-200 outline-none">
                                <option value="string">String</option>
                                <option value="number">Number</option>
                                <option value="boolean">Boolean</option>
                                <option value="json">JSON Object</option>
                                <option value="array">Array</option>
                            </select>
                        </div>

                        <!-- Description (Optional) -->
                        <div class="space-y-1">
                            <label class="block text-xs text-gray-400">Description (Optional)</label>
                            <input v-model="variable.description"
                                   placeholder="Brief description of this variable's purpose"
                                   class="w-full text-sm bg-white/5 border border-white/10 rounded px-2 py-1.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-green-500/50 transition-all duration-200 outline-none" />
                        </div>

                        <!-- Preview -->
                        <div v-if="variable.name" class="bg-gray-800/50 rounded p-2">
                            <div class="text-xs text-gray-400 mb-1">Preview:</div>
                            <div class="text-xs font-mono">
                                <span class="text-green-300">{{ variable.name }}</span>:
                                <span class="text-yellow-300">{{ getFormattedValue(variable) }}</span>
                                <span class="text-gray-500 ml-2">({{ variable.type }})</span>
                            </div>
                        </div>
                    </div>

                    <!-- Collapsed Preview -->
                    <div v-else class="text-xs text-gray-400">
                        <span class="text-green-300">{{ variable.name || 'Unnamed' }}</span>:
                        <span class="text-yellow-300">{{ getFormattedValue(variable) }}</span>
                        <span class="text-gray-500 ml-1">({{ variable.type }})</span>
                    </div>
                </div>
            </div>

            <!-- ✨ NEW: Output Summary -->
            <div v-if="data.variables && data.variables.length > 0" class="bg-white/5 border border-white/10 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-2">
                    <span class="text-gray-300 font-medium">Output Preview:</span>
                    <span v-if="isCurrentlyExecuting" class="ml-2 text-blue-400">● Currently Executing</span>
                </div>

                <div class="bg-gray-800 p-2 rounded text-xs">
                    <pre class="text-green-300 whitespace-pre-wrap">{{ getOutputPreview() }}</pre>
                </div>

                <!-- ✨ NEW: Recent Activity Logs -->
                <div v-if="triggerLogs.length > 0" class="mt-3 pt-2 border-t border-gray-700">
                    <div class="text-xs text-gray-400 mb-1">Recent Activity:</div>
                    <div class="space-y-1 max-h-16 overflow-y-auto">
                        <div v-for="log in triggerLogs.slice(-2)"
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

            <!-- Quick Actions -->
            <div v-if="data.variables && data.variables.length > 0" class="flex gap-2">
                <button @click="collapseAll"
                        class="flex-1 text-xs py-2 bg-gray-700/50 hover:bg-gray-700 text-gray-300 rounded transition-colors">
                    Collapse All
                </button>
                <button @click="expandAll"
                        class="flex-1 text-xs py-2 bg-gray-700/50 hover:bg-gray-700 text-gray-300 rounded transition-colors">
                    Expand All
                </button>
                <button @click="clearAll"
                        class="flex-1 text-xs py-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded transition-colors">
                    Clear All
                </button>
            </div>
        </div>

        <!-- Connection Point -->
        <div class="absolute top-1/2 -right-2 transform -translate-y-1/2">
            <Handle type="source" position="right" class="!w-4 !h-4 !bg-green-500 !border-2 !border-gray-800 hover:!bg-green-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
    </div>
</template>

<script setup>
import { ref, nextTick, computed } from 'vue'
import { Handle } from '@vue-flow/core'
import {
    XIcon, DatabaseIcon, MessageSquareIcon, SettingsIcon,
    PlusIcon, ChevronDownIcon
} from 'lucide-vue-next'
import { defineOptions } from 'vue'

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

// ✨ NEW: Simulation data computed properties
const isCurrentlyExecuting = computed(() => {
    return props.currentNodeId === props.id
})

const triggerLogs = computed(() => {
    if (!props.simulationLogs) return []
    return props.simulationLogs.filter(log =>
        log.message && (log.message.includes('Trigger') || log.message.includes('trigger'))
    )
})

// ✨ NEW: Variable management functions
function addVariable() {
    if (!props.data.variables) {
        props.data.variables = []
    }

    const newVariable = {
        id: Date.now() + Math.random(), // Simple unique ID
        name: '',
        initialValue: '',
        type: 'string',
        description: '',
        collapsed: false
    }

    props.data.variables.push(newVariable)
}

function removeVariable(index) {
    if (props.data.variables && props.data.variables.length > index) {
        props.data.variables.splice(index, 1)
    }
}

function toggleVariableCollapse(index) {
    if (props.data.variables && props.data.variables[index]) {
        props.data.variables[index].collapsed = !props.data.variables[index].collapsed
    }
}

function collapseAll() {
    if (props.data.variables) {
        props.data.variables.forEach(variable => {
            variable.collapsed = true
        })
    }
}

function expandAll() {
    if (props.data.variables) {
        props.data.variables.forEach(variable => {
            variable.collapsed = false
        })
    }
}

function clearAll() {
    if (confirm('Are you sure you want to clear all variables? This action cannot be undone.')) {
        props.data.variables = []
    }
}

function getFormattedValue(variable) {
    if (!variable.initialValue) return '""'

    switch (variable.type) {
        case 'number':
            return isNaN(Number(variable.initialValue)) ? '0' : Number(variable.initialValue)
        case 'boolean':
            return variable.initialValue.toLowerCase() === 'true' || variable.initialValue === '1'
        case 'json':
        case 'array':
            try {
                return JSON.stringify(JSON.parse(variable.initialValue))
            } catch (e) {
                return `"${variable.initialValue}"`
            }
        default:
            return `"${variable.initialValue}"`
    }
}

function getOutputPreview() {
    if (!props.data.variables || props.data.variables.length === 0) {
        return '{}'
    }

    const output = {}

    props.data.variables.forEach(variable => {
        if (variable.name) {
            let value = variable.initialValue || ''

            // Convert based on type
            switch (variable.type) {
                case 'number':
                    value = isNaN(Number(value)) ? 0 : Number(value)
                    break
                case 'boolean':
                    value = value.toLowerCase() === 'true' || value === '1'
                    break
                case 'json':
                case 'array':
                    try {
                        value = JSON.parse(value)
                    } catch (e) {
                        value = value
                    }
                    break
            }

            output[variable.name] = value
        }
    })

    // Always include extractedValue as the first variable's value or null
    const firstVariable = props.data.variables.find(v => v.name)
    if (firstVariable) {
        output.extractedValue = output[firstVariable.name]
    } else {
        output.extractedValue = null
    }

    return JSON.stringify(output, null, 2)
}

// Color theme functions
function getHeaderIconClass() {
    const theme = props.data.colorTheme || 'green'
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
    return classes[theme] || classes.green
}

function getIconColorClass() {
    const theme = props.data.colorTheme || 'green'
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
    return classes[theme] || classes.green
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
    editingTitle.value = props.data.customTitle || 'Trigger'
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

defineOptions({
    nodeMetadata: {
        category: 'Trigger',
        icon: DatabaseIcon,
        label: 'Trigger',
        description: 'Define multiple variables to trigger workflow',
        initialData: {
            variables: [
                {
                    id: Date.now(),
                    name: 'userStatus',
                    initialValue: 'active',
                    type: 'string',
                    description: 'Current user status',
                    collapsed: false
                }
            ],
            customTitle: "",
            customDescription: "",
            comment: "",
            colorTheme: "green"
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

/* Custom scrollbar for variables list */
.max-h-64::-webkit-scrollbar {
    width: 4px;
}

.max-h-64::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
}

.max-h-64::-webkit-scrollbar-thumb {
    background: rgba(16, 185, 129, 0.3);
    border-radius: 2px;
}

.max-h-64::-webkit-scrollbar-thumb:hover {
    background: rgba(16, 185, 129, 0.5);
}

/* Smooth animations for variable items */
.group {
    transition: all 0.2s ease-in-out;
}

.group:hover {
    transform: translateY(-1px);
}

</style>
