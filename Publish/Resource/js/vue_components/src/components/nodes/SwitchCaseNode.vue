<template>
    <div :class="`bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-xl p-5 min-w-[380px] relative shadow-2xl transition-all duration-300 node-${data.colorTheme || 'purple'}`">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center border transition-all duration-300"
                     :class="getHeaderIconClass()">
                    <GitBranch :class="`w-5 h-5 transition-colors duration-300 ${getIconColorClass()}`" />
                </div>
                <div class="flex-1 min-w-0">
                    <!-- Editable Title -->
                    <div v-if="!isEditingTitle"
                         @click="startEditingTitle"
                         class="font-semibold text-white text-sm cursor-pointer hover:text-purple-400 transition-colors truncate"
                         :title="data.customTitle || 'Switch Case'">
                        {{ data.customTitle || 'Switch Case' }}
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
                            {{ data.customDescription || `${data.cases?.length || 0} cases with smart routing` }}
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
                                ? 'bg-purple-500/20 text-purple-400 hover:bg-purple-500/30'
                                : 'hover:bg-purple-500/10 text-gray-400 hover:text-purple-400'
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
                        class="w-8 h-8 rounded-lg hover:bg-purple-500/10 text-gray-400 hover:text-purple-400 transition-all duration-200 flex items-center justify-center group"
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

            <!-- Search filter -->
            <div class="relative">
                <input v-model="variableSearchQuery"
                       placeholder="Search variables..."
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 pl-9 text-white placeholder-gray-500 focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none" />
                <SearchIcon class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" />
            </div>

            <!-- Variables list -->
            <div class="max-h-32 overflow-y-auto space-y-1">
                <div v-if="filteredVariables.length === 0" class="text-center py-2 text-gray-400">
                    <div class="text-xs">No variables found</div>
                </div>
                <div v-for="(value, key) in filteredVariables"
                     :key="key"
                     @click="insertVariable(key)"
                     class="p-2 rounded cursor-pointer transition-all duration-200 bg-white/5 hover:bg-white/10 border border-white/10">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <code class="text-purple-300 text-sm font-mono">{{ key }}</code>
                            <span class="text-xs text-gray-400 bg-gray-800 px-1.5 py-0.5 rounded">
                                {{ getValueType(value) }}
                            </span>
                        </div>
                        <CopyIcon class="w-3 h-3 text-gray-400" />
                    </div>
                    <div class="text-xs text-gray-300 mt-1 truncate">
                        {{ formatPreviewValue(value) }}
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

            <!-- ✨ NEW: Comparison Mode -->
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Comparison Mode</label>
                <select v-model="data.comparisonMode"
                        class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none">
                    <option value="exact">Exact Match</option>
                    <option value="loose">Loose Match (Type Coercion)</option>
                    <option value="regex">Regular Expression</option>
                    <option value="range">Numeric Range</option>
                    <option value="contains">Contains (String)</option>
                </select>
            </div>

            <!-- ✨ NEW: Case Sensitivity -->
            <div v-if="data.comparisonMode === 'exact' || data.comparisonMode === 'contains'" class="pt-2">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative">
                        <input type="checkbox" v-model="data.caseSensitive" class="sr-only" />
                        <div class="w-4 h-4 border-2 border-white/20 rounded group-hover:border-purple-400/50 transition-colors flex items-center justify-center" :class="data.caseSensitive ? 'bg-purple-500 border-purple-500' : ''">
                            <CheckIcon v-if="data.caseSensitive" class="w-3 h-3 text-white" />
                        </div>
                    </div>
                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">Case Sensitive</span>
                </label>
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
            <!-- Switch Expression Input -->
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">
                    Switch Expression
                    <span v-if="data.useExtractedValue" class="text-purple-400">(using extracted value)</span>
                </label>

                <!-- ✨ NEW: Source toggle -->
                <div class="flex items-center gap-3 mb-2">
                    <label class="flex items-center gap-2 cursor-pointer group text-sm">
                        <div class="relative">
                            <input type="checkbox" v-model="data.useExtractedValue" class="sr-only" />
                            <div class="w-4 h-4 border-2 border-white/20 rounded group-hover:border-purple-400/50 transition-colors flex items-center justify-center" :class="data.useExtractedValue ? 'bg-purple-500 border-purple-500' : ''">
                                <CheckIcon v-if="data.useExtractedValue" class="w-3 h-3 text-white" />
                            </div>
                        </div>
                        <span class="text-gray-300 group-hover:text-white transition-colors">Use extracted value</span>
                    </label>
                </div>

                <input v-if="!data.useExtractedValue"
                       v-model="data.switchExpression"
                       placeholder="Enter expression or {{variableName}}"
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none" />

                <div v-else class="w-full text-sm bg-purple-500/10 border border-purple-500/20 rounded-lg px-3 py-2.5 text-purple-200">
                    Will use extractedValue from previous node
                </div>

                <div class="text-xs text-purple-400/70">
                    <span v-if="data.useExtractedValue">Automatically uses the output from the previous node</span>
                    <span v-else>Use {{variableName}} to reference variables</span>
                </div>
            </div>

            <!-- ✨ NEW: Live Preview -->
            <div v-if="currentEvaluatedValue !== null" class="bg-white/5 border border-white/10 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-2">
                    <span class="text-gray-300 font-medium">Live Preview:</span>
                    <span v-if="isCurrentlyExecuting" class="ml-2 text-blue-400">● Executing</span>
                </div>
                <div class="space-y-2">
                    <div class="text-xs">
                        <span class="text-purple-400 font-medium">Current value:</span>
                        <span class="text-yellow-300 ml-2">{{ formatDisplayValue(currentEvaluatedValue) }}</span>
                    </div>
                    <div v-if="predictedMatch !== null" class="text-xs">
                        <span class="text-green-400 font-medium">Will match:</span>
                        <span class="text-green-300 ml-2">{{ getCaseLabel(predictedMatch) }}</span>
                    </div>
                    <div v-else class="text-xs">
                        <span class="text-orange-400 font-medium">Will match:</span>
                        <span class="text-orange-300 ml-2">Default case</span>
                    </div>
                </div>
            </div>

            <!-- Cases Section -->
            <div class="space-y-2">
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-xs font-medium text-gray-300 tracking-wide">
                        Cases
                        <span class="text-gray-500">({{ data.cases?.length || 0 }} total)</span>
                    </label>
                    <div class="flex gap-2">
                        <!-- ✨ NEW: Bulk actions -->
                        <button @click="addMultipleCases"
                                class="text-purple-400 hover:text-purple-300 text-xs px-2 py-1 rounded hover:bg-purple-500/20 flex items-center gap-1 transition-all duration-200">
                            <LayersIcon class="w-3 h-3" />
                            Bulk Add
                        </button>
                        <button @click="addCase"
                                class="text-purple-400 hover:text-purple-300 text-sm px-2 py-1 rounded hover:bg-purple-500/20 flex items-center gap-1 transition-all duration-200">
                            <PlusCircle class="w-4 h-4" />
                            Add Case
                        </button>
                    </div>
                </div>

                <!-- Cases Container -->
                <div class="space-y-2">
                    <div v-for="(caseItem, index) in data.cases" :key="caseItem.id || index"
                         class="relative group"
                         :class="{ 'order-last': caseItem.isDefault }">
                        <!-- Case Input Container -->
                        <div class="flex items-center p-3 bg-white/5 border border-white/10 rounded-lg transition-all duration-200 hover:bg-white/10"
                             :class="{
                                'border-orange-500/30 bg-orange-500/5': caseItem.isDefault,
                                'border-green-500/30 bg-green-500/5': predictedMatch === index && !caseItem.isDefault,
                                'border-yellow-500/30 bg-yellow-500/5': caseItem.hasError
                             }">

                            <!-- Case Label -->
                            <div class="w-20 text-xs font-medium flex items-center gap-1"
                                 :class="{
                                    'text-orange-400': caseItem.isDefault,
                                    'text-green-400': predictedMatch === index && !caseItem.isDefault,
                                    'text-purple-400': predictedMatch !== index && !caseItem.isDefault,
                                    'text-red-400': caseItem.hasError
                                 }">
                                <component :is="caseItem.isDefault ? ShieldIcon : (predictedMatch === index ? CheckCircleIcon : CircleIcon)" class="w-3 h-3" />
                                {{ getCaseLabel(index) }}
                            </div>

                            <!-- Case Input -->
                            <div class="flex-1 relative">
                                <input v-model="caseItem.value"
                                       :placeholder="getCasePlaceholder(caseItem, index)"
                                       :disabled="caseItem.isDefault"
                                       @input="validateCase(caseItem, index)"
                                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none disabled:opacity-50"
                                       :class="{ 'border-red-500/50': caseItem.hasError }" />

                                <!-- Error indicator -->
                                <div v-if="caseItem.hasError" class="absolute right-8 top-1/2 -translate-y-1/2 text-red-400" :title="caseItem.errorMessage">
                                    <AlertCircleIcon class="w-4 h-4" />
                                </div>

                                <!-- Actions -->
                                <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                                    <!-- Move up/down -->
                                    <button v-if="!caseItem.isDefault && index > 0"
                                            @click="moveCase(index, index - 1)"
                                            class="opacity-0 group-hover:opacity-100 transition-opacity text-gray-400 hover:text-gray-300 p-1 rounded hover:bg-white/10">
                                        <ChevronUpIcon class="w-3 h-3" />
                                    </button>
                                    <button v-if="!caseItem.isDefault && index < data.cases.length - 2"
                                            @click="moveCase(index, index + 1)"
                                            class="opacity-0 group-hover:opacity-100 transition-opacity text-gray-400 hover:text-gray-300 p-1 rounded hover:bg-white/10">
                                        <ChevronDownIcon class="w-3 h-3" />
                                    </button>

                                    <!-- Delete Button -->
                                    <button v-if="!caseItem.isDefault"
                                            @click="removeCase(index)"
                                            class="opacity-0 group-hover:opacity-100 transition-opacity text-red-400 hover:text-red-300 p-1 rounded hover:bg-red-500/20">
                                        <XIcon class="w-3 h-3" />
                                    </button>
                                </div>
                            </div>

                            <!-- Output Handle for this case -->
                            <div class="absolute top-1/2 -right-2 -translate-y-1/2">
                                <Handle type="source"
                                        position="right"
                                        :id="String(index)"
                                        :class="caseItem.isDefault
                                            ? '!w-4 !h-4 !bg-orange-500 !border-2 !border-gray-800 hover:!bg-orange-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg'
                                            : '!w-4 !h-4 !bg-purple-500 !border-2 !border-gray-800 hover:!bg-purple-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg'" />
                            </div>
                        </div>

                        <!-- Case description -->
                        <div v-if="caseItem.description" class="ml-24 text-xs text-gray-400 mt-1">
                            {{ caseItem.description }}
                        </div>
                    </div>
                </div>

                <!-- ✨ NEW: Quick case templates -->
                <div class="mt-3 p-2 bg-white/5 rounded-lg">
                    <div class="text-xs text-gray-400 mb-2">Quick Templates:</div>
                    <div class="flex gap-2 flex-wrap">
                        <button v-for="template in caseTemplates"
                                :key="template.name"
                                @click="addCaseTemplate(template)"
                                class="text-xs px-2 py-1 bg-purple-500/20 text-purple-300 rounded hover:bg-purple-500/30 transition-colors">
                            {{ template.name }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- ✨ NEW: Activity Logs -->
            <div v-if="switchLogs.length > 0" class="bg-white/5 border border-white/10 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-2">Recent Activity:</div>
                <div class="space-y-1 max-h-20 overflow-y-auto">
                    <div v-for="log in switchLogs.slice(-3)"
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

        <!-- Input Handle -->
        <div class="absolute top-1/2 -left-2 transform -translate-y-1/2">
            <Handle type="target" position="left" class="!w-4 !h-4 !bg-gray-600 !border-2 !border-gray-800 hover:!bg-purple-500 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
    </div>
</template>

<script setup>
import { ref, nextTick, computed, watch } from 'vue'
import { Handle } from "@vue-flow/core"
import {
    XIcon, GitBranch, PlusCircle, MessageSquareIcon, SettingsIcon, SearchIcon,
    CopyIcon, CheckIcon, LayersIcon, CircleIcon, CheckCircleIcon, ShieldIcon,
    AlertCircleIcon, ChevronUpIcon, ChevronDownIcon
} from "lucide-vue-next"
import { defineOptions } from "vue"
import BreakpointToggle from '../nodeComponents/BreakpointToggle.vue'

// ✨ ENHANCED Props to include simulation data
const props = defineProps([
    'data',
    'id',
    'variables',          // ✨ Live simulation variables
    'simulationLogs',     // ✨ All simulation logs
    'currentNodeId'       // ✨ Currently executing node
])
const emit = defineEmits(["delete"])

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

// ✨ NEW: Case templates for quick setup
const caseTemplates = [
    {
        name: 'Boolean',
        cases: [
            { value: 'true', description: 'When condition is true' },
            { value: 'false', description: 'When condition is false' }
        ]
    },
    {
        name: 'Status',
        cases: [
            { value: 'active', description: 'Active status' },
            { value: 'inactive', description: 'Inactive status' },
            { value: 'pending', description: 'Pending status' }
        ]
    },
    {
        name: 'Priority',
        cases: [
            { value: 'low', description: 'Low priority' },
            { value: 'medium', description: 'Medium priority' },
            { value: 'high', description: 'High priority' },
            { value: 'critical', description: 'Critical priority' }
        ]
    },
    {
        name: 'Numbers 1-5',
        cases: [
            { value: '1', description: 'Case one' },
            { value: '2', description: 'Case two' },
            { value: '3', description: 'Case three' },
            { value: '4', description: 'Case four' },
            { value: '5', description: 'Case five' }
        ]
    }
]

// ✨ NEW: Computed properties for simulation data
const isCurrentlyExecuting = computed(() => {
    return props.currentNodeId === props.id
})

const switchLogs = computed(() => {
    if (!props.simulationLogs) return []
    return props.simulationLogs.filter(log =>
        log.message && (log.message.includes('Switch') || log.message.includes('switch'))
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

const currentEvaluatedValue = computed(() => {
    if (props.data.useExtractedValue) {
        return props.variables?.extractedValue || null
    } else if (props.data.switchExpression) {
        // Simple variable replacement for preview
        let expression = props.data.switchExpression
        if (props.variables) {
            Object.entries(props.variables).forEach(([key, value]) => {
                expression = expression.replace(new RegExp(`{{${key}}}`, 'g'), String(value))
            })
        }
        return expression
    }
    return null
})

const predictedMatch = computed(() => {
    if (currentEvaluatedValue.value === null || !props.data.cases) return null

    const value = String(currentEvaluatedValue.value)

    for (let i = 0; i < props.data.cases.length; i++) {
        const caseItem = props.data.cases[i]
        if (caseItem.isDefault) continue

        if (matchesCase(value, caseItem.value, props.data.comparisonMode, props.data.caseSensitive)) {
            return i
        }
    }
    return null
})

// ✨ Utility functions
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

function formatDisplayValue(value) {
    if (value === null) return 'null'
    if (value === undefined) return 'undefined'
    if (typeof value === 'object') return JSON.stringify(value)
    return String(value)
}

function insertVariable(variableName) {
    if (props.data.useExtractedValue) return

    const currentExpression = props.data.switchExpression || ''
    props.data.switchExpression = currentExpression + `{{${variableName}}}`
    showVariableBrowser.value = false
}

function matchesCase(value, caseValue, mode, caseSensitive) {
    if (!caseValue) return false

    const val = caseSensitive ? value : value.toLowerCase()
    const caseVal = caseSensitive ? caseValue : caseValue.toLowerCase()

    switch (mode) {
        case 'exact':
            return val === caseVal
        case 'loose':
            return val == caseVal
        case 'contains':
            return val.includes(caseVal)
        case 'regex':
            try {
                return new RegExp(caseValue).test(value)
            } catch (e) {
                return false
            }
        case 'range':
            try {
                const [min, max] = caseValue.split('-').map(Number)
                const numValue = Number(value)
                return numValue >= min && numValue <= max
            } catch (e) {
                return false
            }
        default:
            return val === caseVal
    }
}

function getCaseLabel(index) {
    if (!props.data.cases || !props.data.cases[index]) return `Case ${index + 1}`
    return props.data.cases[index].isDefault ? 'Default' : `Case ${index + 1}`
}

function getCasePlaceholder(caseItem, index) {
    if (caseItem.isDefault) return 'default (catches all unmatched values)'

    const mode = props.data.comparisonMode || 'exact'
    const examples = {
        exact: 'exact value',
        loose: 'value (with type coercion)',
        contains: 'substring to find',
        regex: '^pattern.*',
        range: '1-10 (min-max)'
    }

    return `Enter ${examples[mode] || 'case value'}`
}

function validateCase(caseItem, index) {
    caseItem.hasError = false
    caseItem.errorMessage = ''

    if (caseItem.isDefault) return

    const mode = props.data.comparisonMode || 'exact'

    if (mode === 'regex') {
        try {
            new RegExp(caseItem.value)
        } catch (e) {
            caseItem.hasError = true
            caseItem.errorMessage = 'Invalid regular expression'
        }
    } else if (mode === 'range') {
        if (caseItem.value && !caseItem.value.match(/^\d+-\d+$/)) {
            caseItem.hasError = true
            caseItem.errorMessage = 'Range format should be: min-max (e.g., 1-10)'
        }
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
    editingTitle.value = props.data.customTitle || 'Switch Case'
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

// Switch case functions
function addCase() {
    const newCase = {
        id: Date.now() + Math.random(),
        value: "",
        description: "",
        hasError: false,
        errorMessage: ""
    }

    // Insert before default case
    const defaultIndex = props.data.cases.findIndex(c => c.isDefault)
    if (defaultIndex !== -1) {
        props.data.cases.splice(defaultIndex, 0, newCase)
    } else {
        props.data.cases.push(newCase)
    }
}

function removeCase(index) {
    if (props.data.cases[index] && !props.data.cases[index].isDefault) {
        props.data.cases.splice(index, 1)
    }
}

function moveCase(fromIndex, toIndex) {
    if (fromIndex === toIndex) return
    if (props.data.cases[fromIndex]?.isDefault || props.data.cases[toIndex]?.isDefault) return

    const item = props.data.cases.splice(fromIndex, 1)[0]
    props.data.cases.splice(toIndex, 0, item)
}

function addMultipleCases() {
    const count = prompt('How many cases would you like to add?', '3')
    const num = parseInt(count)
    if (num > 0 && num <= 20) {
        for (let i = 0; i < num; i++) {
            addCase()
        }
    }
}

function addCaseTemplate(template) {
    // Remove existing non-default cases
    props.data.cases = props.data.cases.filter(c => c.isDefault)

    // Add template cases
    template.cases.forEach(caseData => {
        const newCase = {
            id: Date.now() + Math.random(),
            value: caseData.value,
            description: caseData.description,
            hasError: false,
            errorMessage: ""
        }

        const defaultIndex = props.data.cases.findIndex(c => c.isDefault)
        if (defaultIndex !== -1) {
            props.data.cases.splice(defaultIndex, 0, newCase)
        } else {
            props.data.cases.push(newCase)
        }
    })
}

// Initialize default values
if (!props.data.cases) {
    props.data.cases = []
}

// Ensure we always have a default case
if (!props.data.cases.some(c => c.isDefault)) {
    props.data.cases.push({
        id: 'default',
        value: "default",
        isDefault: true,
        description: "Catches all unmatched values"
    })
}

// Initialize other defaults
if (props.data.comparisonMode === undefined) {
    props.data.comparisonMode = 'exact'
}
if (props.data.caseSensitive === undefined) {
    props.data.caseSensitive = true
}
if (props.data.useExtractedValue === undefined) {
    props.data.useExtractedValue = true
}

// Watch for comparison mode changes and validate cases
watch(() => props.data.comparisonMode, () => {
    props.data.cases.forEach((caseItem, index) => {
        validateCase(caseItem, index)
    })
})

defineOptions({
    nodeMetadata: {
        category: "Logic",
        icon: GitBranch,
        label: "Enhanced Switch",
        description: "Advanced switch case with multiple comparison modes and live preview",
        initialData: {
            switchExpression: "",
            useExtractedValue: true,
            comparisonMode: "exact",
            caseSensitive: true,
            cases: [
                { id: 1, value: "", description: "", hasError: false, errorMessage: "" },
                { id: "default", value: "default", isDefault: true, description: "Catches all unmatched values" }
            ],
            customTitle: "",
            customDescription: "",
            comment: "",
            colorTheme: "purple"
        },
    },
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

/* Custom scrollbar for cases list */
.max-h-64::-webkit-scrollbar {
    width: 4px;
}

.max-h-64::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
}

.max-h-64::-webkit-scrollbar-thumb {
    background: rgba(139, 92, 246, 0.3);
    border-radius: 2px;
}

.max-h-64::-webkit-scrollbar-thumb:hover {
    background: rgba(139, 92, 246, 0.5);
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
