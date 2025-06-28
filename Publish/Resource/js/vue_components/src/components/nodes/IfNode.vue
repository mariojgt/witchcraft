<template>
    <div :class="`bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-xl p-5 min-w-[320px] relative shadow-2xl transition-all duration-300 node-${data.colorTheme || 'purple'}`">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center border transition-all duration-300"
                     :class="getHeaderIconClass()">
                    <ScanEye class="w-5 h-5 transition-colors duration-300" :class="getIconColorClass()" />
                </div>
                <div class="flex-1 min-w-0">
                    <!-- Editable Title -->
                    <div v-if="!isEditingTitle"
                         @click="startEditingTitle"
                         class="font-semibold text-white text-sm cursor-pointer hover:text-purple-400 transition-colors truncate"
                         :title="data.customTitle || 'Condition'">
                        {{ data.customTitle || 'Condition' }}
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
                            showVariableBrowser ? 'bg-purple-500/20 text-purple-400 hover:bg-purple-500/30' : 'hover:bg-purple-500/10 text-gray-400 hover:text-purple-400'
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

        <!-- Variable Browser -->
        <div v-if="showVariableBrowser" class="mb-4 p-3 bg-purple-500/5 border border-purple-500/20 rounded-lg">
            <div class="text-sm font-medium text-purple-400 mb-2">Available Variables</div>
            <div class="max-h-32 overflow-y-auto space-y-1">
                <div v-for="(value, key) in variables" :key="key"
                     @click="selectVariable(key)"
                     class="p-2 rounded cursor-pointer bg-white/5 hover:bg-white/10 border border-white/10">
                    <div class="flex items-center justify-between">
                        <code class="text-purple-300 text-sm">{{ key }}</code>
                        <span class="text-xs text-gray-400 bg-gray-800 px-1.5 py-0.5 rounded">
                            {{ getValueType(value) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="space-y-4">
            <!-- Mode Toggle -->
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300">Mode</label>
                <div class="flex gap-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" v-model="data.useMultipleConditions" :value="false" class="sr-only" />
                        <div class="w-4 h-4 border-2 border-white/20 rounded-full flex items-center justify-center"
                             :class="!data.useMultipleConditions ? 'bg-purple-500 border-purple-500' : ''">
                            <div v-if="!data.useMultipleConditions" class="w-2 h-2 bg-white rounded-full"></div>
                        </div>
                        <span class="text-sm text-gray-300">Simple</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" v-model="data.useMultipleConditions" :value="true" class="sr-only" />
                        <div class="w-4 h-4 border-2 border-white/20 rounded-full flex items-center justify-center"
                             :class="data.useMultipleConditions ? 'bg-purple-500 border-purple-500' : ''">
                            <div v-if="data.useMultipleConditions" class="w-2 h-2 bg-white rounded-full"></div>
                        </div>
                        <span class="text-sm text-gray-300">Multiple</span>
                    </label>
                </div>
            </div>

            <!-- Simple Mode -->
            <div v-if="!data.useMultipleConditions" class="space-y-4">
                <!-- Variable -->
                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-300">Compare Variable</label>
                    <input v-model="data.compareVariable"
                           placeholder="extractedValue, user.email, etc."
                           @focus="showVariableBrowser = true"
                           class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:border-purple-500 outline-none" />
                </div>

                <!-- Condition Type -->
                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-300">Condition</label>
                    <select v-model="data.conditionType" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white focus:border-purple-500 outline-none">
                        <!-- Basic Conditions -->
                        <optgroup label="Basic Conditions">
                            <option value="equals">Equals (=)</option>
                            <option value="notEquals">Not Equals (≠)</option>
                            <option value="contains">Contains</option>
                            <option value="isEmpty">Is Empty</option>
                            <option value="isNotEmpty">Is Not Empty</option>
                            <option value="inArray">In List</option>
                            <option value="changed">Changed</option>
                        </optgroup>

                        <!-- Numeric Comparisons -->
                        <optgroup label="Numeric Comparisons">
                            <option value="greaterThan">Greater Than (>)</option>
                            <option value="greaterThanOrEqual">Greater Than or Equal (>=)</option>
                            <option value="lessThan">Less Than (<)</option>
                            <option value="lessThanOrEqual">Less Than or Equal (<=)</option>
                        </optgroup>

                        <!-- String Pattern Conditions -->
                        <optgroup label="Pattern Matching">
                            <option value="stringContainsPattern">String Contains Pattern</option>
                            <option value="multipleStringContains">Contains Multiple Patterns</option>
                        </optgroup>
                    </select>
                </div>

                <!-- Expected Value -->
                <div v-if="!['isEmpty', 'isNotEmpty', 'changed'].includes(data.conditionType)" class="space-y-2">
                    <label class="block text-xs font-medium text-gray-300">Expected Value</label>
                    <input v-model="data.expectedValue"
                           :placeholder="getPlaceholder(data.conditionType)"
                           class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:border-purple-500 outline-none" />
                    <div class="text-xs text-purple-400/70">{{ getConditionHelp(data.conditionType) }}</div>
                </div>
            </div>

            <!-- Multiple Mode -->
            <div v-else class="space-y-4">
                <!-- Logic Operator -->
                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-300">Logic</label>
                    <div class="flex gap-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" v-model="data.logicalOperator" value="AND" class="sr-only" />
                            <div class="w-4 h-4 border-2 border-white/20 rounded-full flex items-center justify-center"
                                 :class="data.logicalOperator === 'AND' ? 'bg-purple-500 border-purple-500' : ''">
                                <div v-if="data.logicalOperator === 'AND'" class="w-2 h-2 bg-white rounded-full"></div>
                            </div>
                            <span class="text-sm text-gray-300">AND (all)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" v-model="data.logicalOperator" value="OR" class="sr-only" />
                            <div class="w-4 h-4 border-2 border-white/20 rounded-full flex items-center justify-center"
                                 :class="data.logicalOperator === 'OR' ? 'bg-purple-500 border-purple-500' : ''">
                                <div v-if="data.logicalOperator === 'OR'" class="w-2 h-2 bg-white rounded-full"></div>
                            </div>
                            <span class="text-sm text-gray-300">OR (any)</span>
                        </label>
                    </div>
                </div>

                <!-- Conditions -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-medium text-gray-300">Conditions</label>
                        <button @click="addCondition" class="text-xs bg-purple-500/20 text-purple-400 hover:bg-purple-500/30 px-2 py-1 rounded">
                            + Add
                        </button>
                    </div>

                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        <div v-for="(condition, index) in data.conditions" :key="index"
                             class="bg-white/5 border border-white/10 rounded-lg p-3 space-y-2">

                            <!-- Header -->
                            <div class="flex items-center justify-between">
                                <span class="text-xs bg-gray-700 text-gray-300 px-2 py-1 rounded">{{ index + 1 }}</span>
                                <button @click="removeCondition(index)" class="text-red-400 hover:text-red-300">
                                    <XIcon class="w-4 h-4" />
                                </button>
                            </div>

                            <!-- Variable -->
                            <input v-model="condition.compareVariable"
                                   placeholder="Variable name"
                                   @focus="showVariableBrowser = true"
                                   class="w-full text-xs bg-white/5 border border-white/10 rounded px-2 py-1 text-white placeholder-gray-500 focus:border-purple-500 outline-none" />

                            <!-- Condition -->
                            <select v-model="condition.conditionType" class="w-full text-xs bg-white/5 border border-white/10 rounded px-2 py-1 text-white focus:border-purple-500 outline-none">
                                <optgroup label="Basic">
                                    <option value="equals">Equals (=)</option>
                                    <option value="notEquals">Not Equals (≠)</option>
                                    <option value="contains">Contains</option>
                                    <option value="isEmpty">Is Empty</option>
                                    <option value="isNotEmpty">Is Not Empty</option>
                                    <option value="inArray">In List</option>
                                </optgroup>
                                <optgroup label="Numeric">
                                    <option value="greaterThan">Greater Than (>)</option>
                                    <option value="greaterThanOrEqual">Greater Than or Equal (>=)</option>
                                    <option value="lessThan">Less Than (<)</option>
                                    <option value="lessThanOrEqual">Less Than or Equal (<=)</option>
                                </optgroup>
                            </select>

                            <!-- Expected Value -->
                            <input v-if="!['isEmpty', 'isNotEmpty'].includes(condition.conditionType)"
                                   v-model="condition.expectedValue"
                                   placeholder="Expected value"
                                   class="w-full text-xs bg-white/5 border border-white/10 rounded px-2 py-1 text-white placeholder-gray-500 focus:border-purple-500 outline-none" />
                        </div>

                        <div v-if="data.conditions.length === 0" class="text-center py-4 text-gray-400 border border-dashed border-gray-600 rounded-lg">
                            <p class="text-sm">No conditions</p>
                            <p class="text-xs">Click "Add" to create conditions</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview -->
            <div v-if="getPreview()" class="bg-white/5 border border-white/10 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">Preview:</div>
                <div class="text-xs text-purple-300 font-mono">{{ getPreview() }}</div>
            </div>

            <!-- Output indicators -->
            <div class="flex items-center justify-between pt-2">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-xs text-gray-400">True</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                    <span class="text-xs text-gray-400">False</span>
                </div>
            </div>
        </div>

        <!-- Connection Points -->
        <div class="absolute top-1/2 -left-2 transform -translate-y-1/2">
            <Handle type="target" position="left" class="!w-4 !h-4 !bg-gray-600 !border-2 !border-gray-800 hover:!bg-purple-500 transition-all duration-200 !rounded-full" />
        </div>
        <div class="absolute top-1/2 -right-2 transform -translate-y-1/2">
            <Handle type="source" position="right" id="true" class="!w-4 !h-4 !bg-green-500 !border-2 !border-gray-800 hover:!bg-green-400 transition-all duration-200 !rounded-full" />
        </div>
        <div class="absolute bottom-0 left-1/2 transform translate-y-2 -translate-x-1/2">
            <Handle type="source" position="bottom" id="false" class="!w-4 !h-4 !bg-red-500 !border-2 !border-gray-800 hover:!bg-red-400 transition-all duration-200 !rounded-full" />
        </div>
    </div>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import { Handle } from "@vue-flow/core"
import { XIcon, ScanEye, SearchIcon, MessageSquareIcon, SettingsIcon } from "lucide-vue-next"
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
if (!props.data.useMultipleConditions) props.data.useMultipleConditions = false
if (!props.data.conditions) props.data.conditions = []
if (!props.data.logicalOperator) props.data.logicalOperator = 'AND'
if (!props.data.compareVariable) props.data.compareVariable = 'extractedValue'
if (!props.data.conditionType) props.data.conditionType = 'equals'

// Functions
function selectVariable(variablePath) {
    if (props.data.useMultipleConditions && props.data.conditions.length > 0) {
        // Set on last condition
        props.data.conditions[props.data.conditions.length - 1].compareVariable = variablePath
    } else {
        props.data.compareVariable = variablePath
    }
    showVariableBrowser.value = false
}

function addCondition() {
    props.data.conditions.push({
        compareVariable: '',
        conditionType: 'equals',
        expectedValue: ''
    })
}

function removeCondition(index) {
    props.data.conditions.splice(index, 1)
}

function getDescription() {
    if (props.data.useMultipleConditions) {
        const count = props.data.conditions.length
        return count === 0 ? 'Multiple conditions' : `${count} conditions with ${props.data.logicalOperator}`
    }
    return 'Simple condition check'
}

function getValueType(value) {
    if (Array.isArray(value)) return `array[${value.length}]`
    if (typeof value === 'object') return 'object'
    return typeof value
}

function getPlaceholder(type) {
    const placeholders = {
        equals: 'active',
        contains: 'text to find',
        greaterThan: '100',
        greaterThanOrEqual: '100',
        lessThan: '50',
        lessThanOrEqual: '50',
        inArray: 'value1,value2,value3',
        stringContainsPattern: 'pattern to search',
        multipleStringContains: 'pattern1|pattern2|pattern3'
    }
    return placeholders[type] || 'value'
}

function getConditionHelp(type) {
    const help = {
        greaterThan: 'Value must be strictly greater than the specified number',
        greaterThanOrEqual: 'Value must be greater than or equal to the specified number',
        lessThan: 'Value must be strictly less than the specified number',
        lessThanOrEqual: 'Value must be less than or equal to the specified number',
        stringContainsPattern: 'Case-insensitive pattern search (e.g., C:H/I:H/A:H)',
        multipleStringContains: 'All patterns must be present. Separate with | (e.g., AV:N|PR:N|C:H)',
        inArray: 'Comma-separated values',
        default: 'Use {{variableName}} to reference variables'
    }
    return help[type] || help.default
}

function getPreview() {
    if (!props.data.useMultipleConditions) {
        if (!props.data.compareVariable) return ''

        const variable = props.data.compareVariable
        const condition = props.data.conditionType
        const expected = props.data.expectedValue || 'value'

        if (['isEmpty', 'isNotEmpty', 'changed'].includes(condition)) {
            return `${variable} ${condition}`
        }

        // Show symbolic representation for numeric comparisons
        const symbols = {
            equals: '==',
            notEquals: '!=',
            greaterThan: '>',
            greaterThanOrEqual: '>=',
            lessThan: '<',
            lessThanOrEqual: '<=',
            contains: 'contains'
        }

        const symbol = symbols[condition] || condition
        return `${variable} ${symbol} ${expected}`
    } else {
        if (props.data.conditions.length === 0) return ''

        const previews = props.data.conditions.map((cond, i) => {
            const variable = cond.compareVariable || `var${i + 1}`
            const condition = cond.conditionType
            const expected = cond.expectedValue || 'value'

            if (['isEmpty', 'isNotEmpty'].includes(condition)) {
                return `(${variable} ${condition})`
            }

            const symbols = {
                equals: '==',
                notEquals: '!=',
                greaterThan: '>',
                greaterThanOrEqual: '>=',
                lessThan: '<',
                lessThanOrEqual: '<=',
                contains: 'contains'
            }

            const symbol = symbols[condition] || condition
            return `(${variable} ${symbol} ${expected})`
        })

        return previews.join(` ${props.data.logicalOperator} `)
    }
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
    editingTitle.value = props.data.customTitle || 'Condition'
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
</style>
