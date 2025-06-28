<template>
    <div :class="`bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-xl p-5 min-w-[320px] relative shadow-2xl transition-all duration-300 node-${data.colorTheme || 'green'}`">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center border transition-all duration-300"
                     :class="getHeaderIconClass()">
                    <VariableIcon :class="`w-5 h-5 transition-colors duration-300 ${getIconColorClass()}`" />
                </div>
                <div class="flex-1 min-w-0">
                    <!-- Editable Title -->
                    <div v-if="!isEditingTitle"
                         @click="startEditingTitle"
                         class="font-semibold text-white text-sm cursor-pointer hover:text-green-400 transition-colors truncate"
                         :title="data.customTitle || 'Set Variable'">
                        {{ data.customTitle || 'Set Variable' }}
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
                            {{ data.customDescription || 'Store and manage data' }}
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
                <!-- ✨ NEW: Variable browser button -->
                <button @click="toggleVariableBrowser"
                        :class="`w-8 h-8 rounded-lg transition-all duration-200 flex items-center justify-center group ${
                            showVariableBrowser
                                ? 'bg-green-500/20 text-green-400 hover:bg-green-500/30'
                                : 'hover:bg-green-500/10 text-gray-400 hover:text-green-400'
                        }`"
                        title="Browse Variables">
                    <SearchIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
                </button>

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
                <BreakpointToggle
                    :node-id="id"
                    variant="default"
                />

                <!-- Delete button -->
                <button @click="$emit('delete')"
                        class="w-8 h-8 rounded-lg hover:bg-red-500/10 text-gray-400 hover:text-red-400 transition-all duration-200 flex items-center justify-center group">
                    <XIcon class="w-4 h-4 group-hover:scale-110 transition-transform" />
                </button>
            </div>
        </div>

        <!-- ✨ NEW: Variable Browser Panel -->
        <div v-if="showVariableBrowser" class="mb-4 p-3 bg-green-500/5 border border-green-500/20 rounded-lg space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <SearchIcon class="w-4 h-4 text-green-400" />
                    <span class="text-sm font-medium text-green-400">Available Variables</span>
                </div>
                <span class="text-xs text-gray-500 bg-gray-800 px-2 py-1 rounded">
                    {{ Object.keys(variables || {}).length }} variables
                </span>
            </div>

            <!-- Search filter -->
            <div class="relative">
                <input v-model="variableSearchQuery"
                       placeholder="Search variables..."
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 pl-9 text-white placeholder-gray-500 focus:bg-white/10 focus:border-green-500/50 focus:ring-2 focus:ring-green-500/20 transition-all duration-200 outline-none" />
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
                         data.sourceVariable === key
                             ? 'bg-green-500/20 border border-green-500/30'
                             : 'bg-white/5 hover:bg-white/10 border border-white/10'
                     }`">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <code class="text-green-300 text-sm font-mono">{{ key }}</code>
                                <span class="text-xs text-gray-400 bg-gray-800 px-1.5 py-0.5 rounded">
                                    {{ getValueType(value) }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-300 mt-1 truncate">
                                {{ formatPreviewValue(value) }}
                            </div>
                            <!-- ✨ Show nested properties for complex objects -->
                            <div v-if="isComplexObject(value)" class="text-xs text-blue-300 mt-1">
                                Click to explore: {{ getNestedPaths(value).slice(0, 3).join(', ') }}
                                <span v-if="getNestedPaths(value).length > 3">...</span>
                            </div>
                        </div>
                        <CopyIcon v-if="data.sourceVariable === key"
                                  class="w-4 h-4 text-green-400 flex-shrink-0" />
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

        <!-- Content -->
        <div class="space-y-4">
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Variable Name</label>
                <input v-model="data.variableName" placeholder="myVariable" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-green-500/50 focus:ring-2 focus:ring-green-500/20 transition-all duration-200 outline-none" />
            </div>

            <!-- Use Extracted Value Option -->
            <div class="pt-2">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative">
                        <input type="checkbox" v-model="data.useExtractedValue" class="sr-only" />
                        <div class="w-4 h-4 border-2 border-white/20 rounded group-hover:border-green-400/50 transition-colors flex items-center justify-center" :class="data.useExtractedValue ? 'bg-green-500 border-green-500' : ''">
                            <CheckIcon v-if="data.useExtractedValue" class="w-3 h-3 text-white" />
                        </div>
                    </div>
                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">Use Extracted Value</span>
                </label>
                <div class="text-xs text-green-400/70 mt-1 ml-7">Use value from previous node (extractedValue) instead of manual input</div>
            </div>

            <!-- Variable Value Input (hidden when using extracted value) -->
            <div v-if="!data.useExtractedValue" class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Variable Value</label>
                <textarea v-model="data.variableValue" placeholder="Enter value or use {{otherVariable}} for dynamic values" rows="3" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 resize-none focus:bg-white/10 focus:border-green-500/50 focus:ring-2 focus:ring-green-500/20 transition-all duration-200 outline-none"></textarea>
                <div class="text-xs text-green-400/70">Use {{variableName}} to reference other variables</div>
            </div>

            <!-- ✨ ENHANCED: Extracted Value Source with path support -->
            <div v-if="data.useExtractedValue" class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">
                    Source Variable
                    <span v-if="Object.keys(variables || {}).length > 0" class="text-gray-500">
                        ({{ Object.keys(variables || {}).length }} available)
                    </span>
                </label>
                <div class="relative">
                    <input v-model="data.sourceVariable"
                           placeholder="extractedValue"
                           class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-green-500/50 focus:ring-2 focus:ring-green-500/20 transition-all duration-200 outline-none"
                           @focus="showVariableBrowser = true" />

                    <!-- Variable exists indicator -->
                    <div v-if="data.sourceVariable && sourceVariableExists"
                         class="absolute right-3 top-1/2 transform -translate-y-1/2">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    </div>
                    <div v-else-if="data.sourceVariable && !sourceVariableExists"
                         class="absolute right-3 top-1/2 transform -translate-y-1/2">
                        <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                    </div>
                </div>

                <!-- ✨ NEW: Path extraction for nested properties -->
                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-300 tracking-wide">
                        Path (Optional)
                        <span class="text-gray-500">- Extract specific property</span>
                    </label>
                    <input v-model="data.extractPath"
                           placeholder="e.g., report_select.reported_temp_data.active_installations"
                           class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-green-500/50 focus:ring-2 focus:ring-green-500/20 transition-all duration-200 outline-none" />
                    <div class="text-xs text-green-400/70">Use dot notation to extract nested values (object.property.subproperty)</div>
                </div>

                <div class="text-xs text-green-400/70">Variable name to extract value from (defaults to 'extractedValue')</div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-300 tracking-wide">Type</label>
                    <select v-model="data.valueType" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white focus:bg-white/10 focus:border-green-500/50 focus:ring-2 focus:ring-green-500/20 transition-all duration-200 outline-none">
                        <option value="string">String</option>
                        <option value="number">Number</option>
                        <option value="boolean">Boolean</option>
                        <option value="json">JSON Object</option>
                        <option value="array">Array</option>
                    </select>
                </div>
                <div v-if="data.persistent" class="space-y-2">
                    <label class="block text-xs font-medium text-gray-300 tracking-wide">Cache (min)</label>
                    <input v-model="data.cacheExpiry" type="number" placeholder="60" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-green-500/50 focus:ring-2 focus:ring-green-500/20 transition-all duration-200 outline-none" />
                </div>
            </div>

            <div class="pt-2">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative">
                        <input type="checkbox" v-model="data.persistent" class="sr-only" />
                        <div class="w-4 h-4 border-2 border-white/20 rounded group-hover:border-green-400/50 transition-colors flex items-center justify-center" :class="data.persistent ? 'bg-green-500 border-green-500' : ''">
                            <CheckIcon v-if="data.persistent" class="w-3 h-3 text-white" />
                        </div>
                    </div>
                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">Persistent (Cache)</span>
                </label>
                <div class="text-xs text-green-400/70 mt-1 ml-7">Persistent variables survive workflow restarts</div>
            </div>

            <!-- ✨ NEW: Live Preview with Simulation Data -->
            <div class="bg-white/5 border border-white/10 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-2">
                    <span class="text-gray-300 font-medium">Live Preview:</span>
                    <span v-if="isCurrentlyExecuting" class="ml-2 text-blue-400">● Currently Executing</span>
                </div>

                <div v-if="!data.variableName" class="text-xs text-gray-500">
                    Enter a variable name to see preview
                </div>

                <div v-else class="space-y-2">
                    <div class="text-xs">
                        <span class="text-green-400 font-medium">Will set variable:</span>
                        <span class="text-yellow-300 ml-2">{{ data.variableName }}</span>
                        <span class="text-green-400 ml-2">{{ data.valueType }}</span>
                    </div>

                    <!-- Show current source value if using extracted value -->
                    <div v-if="data.useExtractedValue && sourceVariableExists" class="space-y-2">
                        <div class="text-xs text-cyan-400">Source value ({{ data.sourceVariable }}):</div>
                        <div class="bg-gray-800 p-2 rounded text-xs">
                            <pre class="text-cyan-300 whitespace-pre-wrap">{{ formatDisplayValue(currentSourceValue) }}</pre>
                        </div>

                        <!-- Show extracted value if path is specified -->
                        <div v-if="data.extractPath && extractedSourceValue !== currentSourceValue" class="space-y-1">
                            <div class="text-xs text-green-400">Extracted value ({{ data.extractPath }}):</div>
                            <div class="bg-gray-800 p-2 rounded text-xs">
                                <pre class="text-green-300 whitespace-pre-wrap">{{ formatDisplayValue(extractedSourceValue) }}</pre>
                            </div>
                        </div>

                        <!-- Show final converted value -->
                        <div class="space-y-1">
                            <div class="text-xs text-yellow-400">Final value (as {{ data.valueType }}):</div>
                            <div class="bg-gray-800 p-2 rounded text-xs">
                                <pre class="text-yellow-300 whitespace-pre-wrap">{{ formatDisplayValue(finalPreviewValue) }}</pre>
                            </div>
                        </div>
                    </div>

                    <!-- Show manual input value preview -->
                    <div v-else-if="!data.useExtractedValue && data.variableValue" class="space-y-2">
                        <div class="text-xs text-green-400">Manual input value:</div>
                        <div class="bg-gray-800 p-2 rounded text-xs">
                            <pre class="text-green-300 whitespace-pre-wrap">{{ data.variableValue }}</pre>
                        </div>

                        <!-- Show final converted value if different -->
                        <div v-if="finalPreviewValue !== data.variableValue" class="space-y-1">
                            <div class="text-xs text-yellow-400">Final value (as {{ data.valueType }}):</div>
                            <div class="bg-gray-800 p-2 rounded text-xs">
                                <pre class="text-yellow-300 whitespace-pre-wrap">{{ formatDisplayValue(finalPreviewValue) }}</pre>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-xs text-gray-400">
                        No preview available - configure source value
                    </div>
                </div>

                <!-- ✨ NEW: Recent Activity Logs -->
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
            <Handle type="target" position="left" class="!w-4 !h-4 !bg-gray-600 !border-2 !border-gray-800 hover:!bg-green-500 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
        <div class="absolute top-1/2 -right-2 transform -translate-y-1/2">
            <Handle type="source" position="right" class="!w-4 !h-4 !bg-green-500 !border-2 !border-gray-800 hover:!bg-green-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
    </div>
</template>

<script setup>
import { ref, nextTick, computed } from 'vue'
import { Handle } from '@vue-flow/core'
import { XIcon, VariableIcon, CheckIcon, MessageSquareIcon, SettingsIcon, SearchIcon, CopyIcon } from 'lucide-vue-next'
import BreakpointToggle from '../nodeComponents/BreakpointToggle.vue'

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
    { name: 'purple', label: 'Purple', gradient: 'linear-gradient(145deg, #8b5cf6, #7c3aed)' },
    { name: 'blue', label: 'Blue', gradient: 'linear-gradient(145deg, #3b82f6, #1d4ed8)' },
    { name: 'green', label: 'Green', gradient: 'linear-gradient(145deg, #10b981, #059669)' },
    { name: 'red', label: 'Red', gradient: 'linear-gradient(145deg, #ef4444, #dc2626)' },
    { name: 'yellow', label: 'Yellow', gradient: 'linear-gradient(145deg, #f59e0b, #d97706)' },
    { name: 'pink', label: 'Pink', gradient: 'linear-gradient(145deg, #ec4899, #db2777)' },
    { name: 'gray', label: 'Gray', gradient: 'linear-gradient(145deg, #6b7280, #4b5563)' },
    { name: 'orange', label: 'Orange', gradient: 'linear-gradient(145deg, #f97316, #ea580c)' }
]

// ✨ NEW: Enhanced computed properties for simulation data
const isCurrentlyExecuting = computed(() => {
    return props.currentNodeId === props.id
})

const sourceVariableExists = computed(() => {
    return props.data.sourceVariable && props.variables && props.variables.hasOwnProperty(props.data.sourceVariable)
})

const currentSourceValue = computed(() => {
    if (!sourceVariableExists.value) return null
    return props.variables[props.data.sourceVariable]
})

const extractedSourceValue = computed(() => {
    if (!currentSourceValue.value || !props.data.extractPath) return currentSourceValue.value

    try {
        const path = props.data.extractPath.split('.')
        let value = currentSourceValue.value

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

const finalPreviewValue = computed(() => {
    if (props.data.useExtractedValue) {
        const sourceValue = extractedSourceValue.value
        return convertValueType(sourceValue, props.data.valueType)
    } else {
        return convertValueType(props.data.variableValue, props.data.valueType)
    }
})

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

// ✨ Utility functions for variable browser and preview
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

function isComplexObject(value) {
    return value && typeof value === 'object' && !Array.isArray(value) && Object.keys(value).length > 0
}

function getNestedPaths(obj, prefix = '', maxDepth = 2, currentDepth = 0) {
    const paths = []

    if (currentDepth >= maxDepth || !obj || typeof obj !== 'object') {
        return paths
    }

    Object.keys(obj).forEach(key => {
        const newPath = prefix ? `${prefix}.${key}` : key
        paths.push(newPath)

        if (typeof obj[key] === 'object' && obj[key] !== null) {
            const subPaths = getNestedPaths(obj[key], newPath, maxDepth, currentDepth + 1)
            paths.push(...subPaths)
        }
    })

    return paths
}

function selectVariable(variableName) {
    props.data.sourceVariable = variableName
    showVariableBrowser.value = false
}

// ✨ Value conversion functions (matching PHP backend)
function convertValueType(value, type) {
    switch (type) {
        case 'number':
            return convertToNumber(value)
        case 'boolean':
            return convertToBoolean(value)
        case 'json':
            return convertToJson(value)
        case 'array':
            return convertToArray(value)
        case 'object':
            return convertToObject(value)
        default:
            return convertToString(value)
    }
}

function convertToNumber(value) {
    if (Array.isArray(value) || (typeof value === 'object' && value !== null)) {
        return 0
    }
    return isNaN(Number(value)) ? 0 : Number(value)
}

function convertToBoolean(value) {
    if (Array.isArray(value)) {
        return value.length > 0
    }
    if (typeof value === 'object' && value !== null) {
        return Object.keys(value).length > 0
    }
    if (typeof value === 'string') {
        return value.toLowerCase() === 'true' || value === '1'
    }
    return Boolean(value)
}

function convertToJson(value) {
    if (typeof value === 'string') {
        try {
            JSON.parse(value)
            return value // Already valid JSON
        } catch (e) {
            // Not valid JSON, continue to convert
        }
    }

    try {
        return JSON.stringify(value, null, 2)
    } catch (e) {
        return JSON.stringify({ error: 'Failed to encode to JSON' })
    }
}

function convertToArray(value) {
    if (Array.isArray(value)) {
        return value
    }

    if (typeof value === 'object' && value !== null) {
        return Object.values(value)
    }

    if (typeof value === 'string') {
        try {
            const parsed = JSON.parse(value)
            if (Array.isArray(parsed)) {
                return parsed
            }
        } catch (e) {
            // Not JSON, split by comma
        }
        return value.split(',').map(item => item.trim())
    }

    return [value]
}

function convertToObject(value) {
    if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
        return value
    }

    if (Array.isArray(value)) {
        return Object.fromEntries(value.map((item, index) => [index, item]))
    }

    if (typeof value === 'string') {
        try {
            const parsed = JSON.parse(value)
            if (typeof parsed === 'object' && parsed !== null) {
                return parsed
            }
        } catch (e) {
            // Not JSON
        }
    }

    return { value: value }
}

function convertToString(value) {
    if (typeof value === 'string') {
        return value
    }

    if (Array.isArray(value) || (typeof value === 'object' && value !== null)) {
        return JSON.stringify(value)
    }

    if (typeof value === 'boolean') {
        return value ? 'true' : 'false'
    }

    if (value === null || value === undefined) {
        return ''
    }

    return String(value)
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
    showVariableBrowser.value = false
}

function saveComment() {
    showComment.value = false
}

// Settings functions
function toggleSettings() {
    showSettings.value = !showSettings.value
    showComment.value = false
    showVariableBrowser.value = false
}

// ✨ NEW: Variable browser functions
function toggleVariableBrowser() {
    showVariableBrowser.value = !showVariableBrowser.value
    showComment.value = false
    showSettings.value = false
}

// Title editing functions
function startEditingTitle() {
    isEditingTitle.value = true
    editingTitle.value = props.data.customTitle || 'Set Variable'
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
export default {
    nodeMetadata: {
        category: 'Data',
        icon: 'VariableIcon',
        label: 'Set Variable',
        description: 'Store and manage data with live preview',
        initialData: {
            variableName: 'myVariable',
            variableValue: 'Hello World',
            valueType: 'string',
            persistent: false,
            cacheExpiry: '',
            useExtractedValue: true,
            sourceVariable: 'extractedValue',
            extractPath: '', // ✨ NEW: Support for nested path extraction
            customTitle: "",
            customDescription: "",
            comment: "",
            colorTheme: "green"
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
