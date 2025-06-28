<template>
    <div :class="`bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-xl p-5 min-w-[320px] relative shadow-2xl transition-all duration-300 node-${data.colorTheme || 'blue'}`">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center border transition-all duration-300"
                     :class="getHeaderIconClass()">
                    <CalculatorIcon class="w-5 h-5 transition-colors duration-300" :class="getIconColorClass()" />
                </div>
                <div class="flex-1 min-w-0">
                    <!-- Editable Title -->
                    <div v-if="!isEditingTitle"
                         @click="startEditingTitle"
                         class="font-semibold text-white text-sm cursor-pointer hover:text-blue-400 transition-colors truncate"
                         :title="data.customTitle || 'Calculation'">
                        {{ data.customTitle || 'Calculation' }}
                    </div>
                    <input v-else
                           v-model="editingTitle"
                           @blur="finishEditingTitle"
                           @keydown.enter="finishEditingTitle"
                           @keydown.escape="cancelEditingTitle"
                           ref="titleInput"
                           class="font-semibold text-white text-sm bg-transparent border-b border-blue-500 outline-none w-full"
                           placeholder="Enter custom title" />

                    <div class="flex items-center gap-2 mt-0.5">
                        <p class="text-xs text-gray-400 leading-none truncate">
                            {{ data.customDescription || getDescription() }}
                        </p>
                        <!-- Comment indicator -->
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
                                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gray-900 border-r border-b border-gray-700 rotate-45"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-1 ml-2">
                <button @click="showVariableBrowser = !showVariableBrowser"
                        :class="`w-8 h-8 rounded-lg transition-all duration-200 flex items-center justify-center group ${
                            showVariableBrowser ? 'bg-blue-500/20 text-blue-400 hover:bg-blue-500/30' : 'hover:bg-blue-500/10 text-gray-400 hover:text-blue-400'
                        }`"
                        title="Browse Variables">
                    <SearchIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
                </button>

                <button @click="toggleComment"
                        :class="`w-8 h-8 rounded-lg transition-all duration-200 flex items-center justify-center group ${
                            data.comment && data.comment.trim()
                                ? 'bg-yellow-500/20 text-yellow-400 hover:bg-yellow-500/30'
                                : 'hover:bg-yellow-500/10 text-gray-400 hover:text-yellow-400'
                        }`"
                        title="Add/Edit Comment">
                    <MessageSquareIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
                </button>

                <button @click="toggleSettings"
                        class="w-8 h-8 rounded-lg hover:bg-blue-500/10 text-gray-400 hover:text-blue-400 transition-all duration-200 flex items-center justify-center group"
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

        <!-- Settings Panel -->
        <div v-if="showSettings" class="mb-4 p-3 bg-white/5 border border-white/10 rounded-lg space-y-3">
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Custom Title</label>
                <input v-model="data.customTitle"
                       placeholder="Enter custom node title"
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:bg-white/10 focus:border-blue-500/50 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 outline-none" />
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Custom Description</label>
                <input v-model="data.customDescription"
                       placeholder="Enter custom description"
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:bg-white/10 focus:border-blue-500/50 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 outline-none" />
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
                      placeholder="Add a comment to help document this calculation..."
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

        <!-- Variable Browser -->
        <div v-if="showVariableBrowser" class="mb-4 p-3 bg-blue-500/5 border border-blue-500/20 rounded-lg">
            <div class="text-sm font-medium text-blue-400 mb-2">Available Variables</div>
            <div class="max-h-32 overflow-y-auto space-y-1">
                <!-- Show message if no variables available -->
                <div v-if="!variables || Object.keys(variables).length === 0"
                     class="text-center py-4 text-gray-400 text-xs">
                    No variables available yet. Connect from previous nodes.
                </div>

                <!-- Recursive variable display for nested objects -->
                <template v-else>
                    <div v-for="(value, key) in flattenVariables(variables)" :key="key"
                         @click="insertVariable(key)"
                         class="p-2 rounded cursor-pointer bg-white/5 hover:bg-white/10 border border-white/10">
                        <div class="flex items-center justify-between">
                            <code class="text-blue-300 text-sm">{{ key }}</code>
                            <span class="text-xs text-gray-400 bg-gray-800 px-1.5 py-0.5 rounded">
                                {{ getValueType(getVariableValue(variables, key)) }}
                            </span>
                        </div>
                        <div v-if="typeof getVariableValue(variables, key) !== 'object'"
                             class="text-xs text-gray-500 mt-1 truncate">
                            Value: {{ formatPreviewValue(getVariableValue(variables, key)) }}
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Content -->
        <div class="space-y-4">
            <!-- Expression Input -->
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300">Mathematical Expression</label>
                <textarea v-model="data.expression"
                          ref="expressionInput"
                          @focus="showVariableBrowser = true"
                          placeholder="Example: {{product.vdp.is_new}} ? 25 : 15) / 100"
                          rows="3"
                          class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-500 font-mono resize-none focus:border-blue-500 outline-none">
                </textarea>
                <div class="text-xs text-blue-400/70">
                    Use {{variableName}} for variables. Supports +, -, *, /, (), and ternary operator (condition ? true : false)
                </div>
            </div>

            <!-- Output Variable Name -->
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300">Output Variable Name</label>
                <input v-model="data.outputVariable"
                       placeholder="calculatedValue"
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:border-blue-500 outline-none" />
                <div class="text-xs text-gray-400">Name for the calculated result variable</div>
            </div>

            <!-- Expression Builder Helper -->
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300">Expression Builder</label>
                <div class="grid grid-cols-4 gap-2 text-xs">
                    <button v-for="operator in mathOperators" :key="operator.symbol"
                            @click="insertOperator(operator.symbol)"
                            :title="operator.description"
                            class="p-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded text-gray-300 hover:text-white transition-colors">
                        {{ operator.symbol }}
                    </button>
                </div>
            </div>

            <!-- Preview -->
            <div v-if="getPreview()" class="bg-white/5 border border-white/10 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">Preview:</div>
                <div class="text-xs text-blue-300 font-mono break-all">{{ getPreview() }}</div>
            </div>

            <!-- Examples -->
            <div class="bg-blue-500/5 border border-blue-500/20 rounded-lg p-3">
                <div class="text-xs text-blue-300 mb-2">Examples:</div>
                <div class="space-y-1 text-xs text-gray-300 font-mono">
                    <div>{{price}} * {{quantity}}</div>
                    <div>{{is_new}} ? 25 : 15</div>
                    <div>({{base_price}} + {{tax}}) * 1.1</div>
                    <div>{{age}} >= 18 ? 1 : 0</div>
                </div>
                <div class="mt-2 text-xs text-gray-400">
                    💡 Click variables above to insert them automatically
                </div>
            </div>
        </div>

        <!-- Connection Points -->
        <div class="absolute top-1/2 -left-2 transform -translate-y-1/2">
            <Handle type="target" position="left" class="!w-4 !h-4 !bg-gray-600 !border-2 !border-gray-800 hover:!bg-blue-500 transition-all duration-200 !rounded-full" />
        </div>
        <div class="absolute top-1/2 -right-2 transform -translate-y-1/2">
            <Handle type="source" position="right" class="!w-4 !h-4 !bg-blue-500 !border-2 !border-gray-800 hover:!bg-blue-400 transition-all duration-200 !rounded-full" />
        </div>
    </div>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import { Handle } from "@vue-flow/core"
import { XIcon, SearchIcon, MessageSquareIcon, SettingsIcon } from "lucide-vue-next"
import { Calculator as CalculatorIcon } from "lucide-vue-next"
import BreakpointToggle from '../nodeComponents/BreakpointToggle.vue'

const props = defineProps(["data", "variables", "id"])
defineEmits(["delete"])

// UI State
const showVariableBrowser = ref(false)
const showComment = ref(false)
const showSettings = ref(false)
const isEditingTitle = ref(false)
const editingTitle = ref("")
const titleInput = ref(null)
const expressionInput = ref(null)
const showTooltip = ref(false)

// Color themes
const colorThemes = [
    { name: 'blue', label: 'Blue', gradient: 'linear-gradient(145deg, #3b82f6, #1d4ed8)' },
    { name: 'purple', label: 'Purple', gradient: 'linear-gradient(145deg, #8b5cf6, #7c3aed)' },
    { name: 'green', label: 'Green', gradient: 'linear-gradient(145deg, #10b981, #059669)' },
    { name: 'red', label: 'Red', gradient: 'linear-gradient(145deg, #ef4444, #dc2626)' },
    { name: 'yellow', label: 'Yellow', gradient: 'linear-gradient(145deg, #f59e0b, #d97706)' },
    { name: 'pink', label: 'Pink', gradient: 'linear-gradient(145deg, #ec4899, #db2777)' },
    { name: 'gray', label: 'Gray', gradient: 'linear-gradient(145deg, #6b7280, #4b5563)' },
    { name: 'orange', label: 'Orange', gradient: 'linear-gradient(145deg, #f97316, #ea580c)' }
]

// Math operators for the builder
const mathOperators = [
    { symbol: '+', description: 'Addition' },
    { symbol: '-', description: 'Subtraction' },
    { symbol: '*', description: 'Multiplication' },
    { symbol: '/', description: 'Division' },
    { symbol: '(', description: 'Open parenthesis' },
    { symbol: ')', description: 'Close parenthesis' },
    { symbol: '?', description: 'Ternary condition' },
    { symbol: ':', description: 'Ternary separator' }
]

// Initialize data
if (!props.data.expression) props.data.expression = ''
if (!props.data.outputVariable) props.data.outputVariable = 'calculatedValue'

// Functions
function insertVariable(variablePath) {
    const variable = `{{${variablePath}}}`
    if (expressionInput.value) {
        const textarea = expressionInput.value
        const start = textarea.selectionStart
        const end = textarea.selectionEnd
        const before = props.data.expression.substring(0, start)
        const after = props.data.expression.substring(end)

        props.data.expression = before + variable + after

        nextTick(() => {
            const newPosition = start + variable.length
            textarea.setSelectionRange(newPosition, newPosition)
            textarea.focus()
        })
    } else {
        props.data.expression += variable
    }
    showVariableBrowser.value = false
}

function insertOperator(operator) {
    if (expressionInput.value) {
        const textarea = expressionInput.value
        const start = textarea.selectionStart
        const end = textarea.selectionEnd
        const before = props.data.expression.substring(0, start)
        const after = props.data.expression.substring(end)

        props.data.expression = before + operator + after

        nextTick(() => {
            const newPosition = start + operator.length
            textarea.setSelectionRange(newPosition, newPosition)
            textarea.focus()
        })
    } else {
        props.data.expression += operator
    }
}

function getDescription() {
    if (!props.data.expression) {
        return 'Mathematical calculation'
    }
    return `Calculate: ${props.data.expression.substring(0, 30)}${props.data.expression.length > 30 ? '...' : ''}`
}

function getValueType(value) {
    if (value === null || value === undefined) return 'null'
    if (Array.isArray(value)) return `array[${value.length}]`
    if (typeof value === 'object') return 'object'
    return typeof value
}

// Add function to flatten nested variables for display
function flattenVariables(obj, prefix = '') {
    const flattened = {};

    if (!obj || typeof obj !== 'object') {
        return {};
    }

    for (const [key, value] of Object.entries(obj)) {
        const newKey = prefix ? `${prefix}.${key}` : key;

        if (value && typeof value === 'object' && !Array.isArray(value)) {
            // Recursively flatten nested objects
            Object.assign(flattened, flattenVariables(value, newKey));
        }

        // Always include the current level (for both primitives and objects)
        flattened[newKey] = value;
    }

    return flattened;
}

function formatPreviewValue(value) {
    if (value === null || value === undefined) return 'null'
    if (typeof value === 'boolean') return value ? 'true' : 'false'
    if (typeof value === 'number') return value.toString()
    if (typeof value === 'string') return value.length > 20 ? value.substring(0, 20) + '...' : value
    if (typeof value === 'object') return Array.isArray(value) ? `[Array ${value.length}]` : '[Object]'
    return String(value)
}

function getPreview() {
    if (!props.data.expression) return ''

    // Show the expression as-is for preview
    let preview = props.data.expression

    // Only replace variables if they actually exist in the current variables
    if (props.variables && Object.keys(props.variables).length > 0) {
        // Find all {{variable}} patterns in the expression
        const variablePattern = /\{\{([^}]+)\}\}/g;
        const matches = [...props.data.expression.matchAll(variablePattern)];

        matches.forEach(match => {
            const variablePath = match[1].trim();
            const value = getVariableValue(props.variables, variablePath);

            if (value !== null && value !== undefined) {
                const displayValue = typeof value === 'number' ? value :
                                  typeof value === 'boolean' ? (value ? '1' : '0') :
                                  typeof value === 'string' && !isNaN(value) ? value : '0';

                preview = preview.replace(match[0], displayValue);
            }
        });
    }

    return `${props.data.outputVariable} = ${preview}`
}

// Add helper function to get variable values with dot notation
function getVariableValue(variables, path) {
    if (!variables || !path) return null;

    if (path.includes('.')) {
        const parts = path.split('.');
        let current = variables;

        for (const part of parts) {
            if (current && typeof current === 'object' && part in current) {
                current = current[part];
            } else {
                return null;
            }
        }

        return current;
    }

    return variables[path] ?? null;
}

// Color theme functions
function getHeaderIconClass() {
    const theme = props.data.colorTheme || 'blue'
    const classes = {
        blue: 'bg-gradient-to-br from-blue-500/20 to-blue-600/20 border-blue-500/20',
        purple: 'bg-gradient-to-br from-purple-500/20 to-purple-600/20 border-purple-500/20',
        green: 'bg-gradient-to-br from-green-500/20 to-green-600/20 border-green-500/20',
        red: 'bg-gradient-to-br from-red-500/20 to-red-600/20 border-red-500/20',
        yellow: 'bg-gradient-to-br from-yellow-500/20 to-yellow-600/20 border-yellow-500/20',
        pink: 'bg-gradient-to-br from-pink-500/20 to-pink-600/20 border-pink-500/20',
        gray: 'bg-gradient-to-br from-gray-500/20 to-gray-600/20 border-gray-500/20',
        orange: 'bg-gradient-to-br from-orange-500/20 to-orange-600/20 border-orange-500/20'
    }
    return classes[theme] || classes.blue
}

function getIconColorClass() {
    const theme = props.data.colorTheme || 'blue'
    const classes = {
        blue: 'text-blue-400',
        purple: 'text-purple-400',
        green: 'text-green-400',
        red: 'text-red-400',
        yellow: 'text-yellow-400',
        pink: 'text-pink-400',
        gray: 'text-gray-400',
        orange: 'text-orange-400'
    }
    return classes[theme] || classes.blue
}

// UI Functions
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

function startEditingTitle() {
    isEditingTitle.value = true
    editingTitle.value = props.data.customTitle || 'Calculation'
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
// Export node metadata for the node library
export const nodeMetadata = {
    category: 'Data Processing',
    icon: 'CalculatorIcon',
    label: 'Calculation',
    description: 'Perform mathematical calculations with variables',
    initialData: {
        label: 'Calculation',
        expression: '',
        outputVariable: 'calculatedValue'
    }
}
</script>

<style scoped>
/* Dynamic color theming */
.node-blue {
    box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.1);
}
.node-blue:hover {
    box-shadow: 0 25px 50px -12px rgba(59, 130, 246, 0.1);
    border-color: rgba(59, 130, 246, 0.3);
}

.node-purple {
    box-shadow: 0 0 0 1px rgba(139, 92, 246, 0.1);
}
.node-purple:hover {
    box-shadow: 0 25px 50px -12px rgba(139, 92, 246, 0.1);
    border-color: rgba(139, 92, 246, 0.3);
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

/* Expression textarea styling */
textarea {
    font-family: 'JetBrains Mono', 'Fira Code', 'Monaco', 'Cascadia Code', 'Roboto Mono', monospace;
}
</style>
