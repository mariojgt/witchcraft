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
            <!-- ✨ NEW: Trigger Code Input (Preferred Method) -->
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">
                    Trigger Code (Recommended)
                    <span class="text-pink-400 ml-1">●</span>
                </label>
                <input v-model="data.triggerCode"
                       placeholder="e.g., FLOW_XP_CALCULATION"
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-pink-500/50 focus:ring-2 focus:ring-pink-500/20 transition-all duration-200 outline-none" />
                <div class="text-xs text-pink-400/70">
                    ✨ Automatically uses the latest version of the flow
                </div>
            </div>

            <!-- Flow Selection -->
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">
                    Or Search Flows
                </label>
                <div class="relative flow-search-container">
                    <input
                        v-model="flowSearchQuery"
                        @input="searchFlows"
                        @focus="focusSearch"
                        @keydown="handleKeydown"
                        :placeholder="data.selectedFlow ? data.selectedFlow.name : 'Search flows by name, description, or ID...'"
                        class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 pr-20 text-white placeholder-gray-500 focus:bg-white/10 focus:border-pink-500/50 focus:ring-2 focus:ring-pink-500/20 transition-all duration-200 outline-none"
                    />

                    <!-- Loading indicator -->
                    <div v-if="isLoadingFlows" class="absolute right-3 top-3">
                        <div class="w-4 h-4 border-2 border-gray-400 border-t-pink-500 rounded-full animate-spin"></div>
                    </div>
                    <!-- Search icon -->
                    <SearchIcon v-else class="absolute right-3 top-3 w-4 h-4 text-gray-400" />

                    <!-- Clear button -->
                    <button
                        v-if="flowSearchQuery && !isLoadingFlows"
                        @click="clearSelection"
                        class="absolute right-8 top-3 w-4 h-4 text-gray-400 hover:text-white transition-colors"
                    >
                        <XIcon class="w-4 h-4" />
                    </button>

                    <!-- Enhanced Dropdown with search results -->
                    <div
                        v-if="showFlowDropdown"
                        ref="dropdownRef"
                        class="absolute top-full left-0 right-0 mt-1 bg-gray-900/95 backdrop-blur-xl border border-white/20 rounded-lg shadow-xl z-50 max-h-64 overflow-y-auto"
                    >
                        <!-- Search results -->
                        <div v-if="filteredFlows.length > 0">
                            <!-- Search hint -->
                            <div v-if="!flowSearchQuery.trim()" class="px-3 py-2 text-xs text-gray-400 border-b border-white/10">
                                Recently used and popular flows
                            </div>
                            <div v-else class="px-3 py-2 text-xs text-gray-400 border-b border-white/10">
                                {{ filteredFlows.length }} result{{ filteredFlows.length !== 1 ? 's' : '' }} found
                            </div>

                            <div
                                v-for="(flow, index) in filteredFlows"
                                :key="flow.id"
                                :data-index="index"
                                @click="selectFlow(flow)"
                                @mouseenter="selectedIndex = index"
                                :class="{
                                    'p-3 cursor-pointer border-b border-white/10 last:border-b-0 transition-all duration-200': true,
                                    'selected-item': selectedIndex === index
                                }"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-white truncate">{{ flow.name }}</div>
                                        <div class="text-xs text-gray-400 mt-0.5">
                                            <span v-if="flow.trigger_code" class="text-pink-400 font-mono">{{ flow.trigger_code }}</span>
                                            <span v-if="flow.trigger_code && flow.id" class="mx-1">•</span>
                                            <span v-if="flow.id">ID: {{ flow.id }}</span>
                                            <span v-if="flow.category" class="mx-1">•</span>
                                            <span v-if="flow.category" class="text-pink-400">{{ flow.category }}</span>
                                        </div>
                                        <div v-if="flow.description" class="text-xs text-gray-500 mt-1 line-clamp-2">
                                            {{ flow.description }}
                                        </div>
                                    </div>

                                    <!-- Flow status indicators -->
                                    <div class="flex items-center gap-1 ml-2">
                                        <div v-if="flow.is_active === false" class="w-2 h-2 bg-red-500 rounded-full" title="Inactive"></div>
                                        <div v-if="flow.usage_count > 10" class="w-2 h-2 bg-green-500 rounded-full" title="Popular"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- No results -->
                        <div v-else-if="!isLoadingFlows && flowSearchQuery.trim()" class="p-4 text-center text-gray-400">
                            <div class="text-sm">No flows found</div>
                            <div class="text-xs mt-1">Try searching by name, description, or ID</div>
                        </div>

                        <!-- Error state -->
                        <div v-else-if="searchError" class="p-4 text-center text-red-400">
                            <div class="text-sm">{{ searchError }}</div>
                            <button @click="loadFlows()" class="text-xs mt-1 text-pink-400 hover:text-pink-300">
                                Try again
                            </button>
                        </div>

                        <!-- Loading state -->
                        <div v-else-if="isLoadingFlows" class="p-4 text-center text-gray-400">
                            <div class="text-sm">Loading flows...</div>
                        </div>

                        <!-- Empty state -->
                        <div v-else class="p-4 text-center text-gray-400">
                            <div class="text-sm">No flows available</div>
                            <div class="text-xs mt-1">Create some flows to see them here</div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Selected flow display -->
                <div v-if="data.selectedFlow" class="bg-white/5 border border-white/10 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-pink-400 truncate">{{ data.selectedFlow.name }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                <span v-if="data.selectedFlow.trigger_code" class="text-pink-400 font-mono">{{ data.selectedFlow.trigger_code }}</span>
                                <span v-if="data.selectedFlow.trigger_code && data.selectedFlow.id" class="mx-1">•</span>
                                <span v-if="data.selectedFlow.id">ID: {{ data.selectedFlow.id }}</span>
                                <span v-if="data.selectedFlow.category" class="mx-1">•</span>
                                <span v-if="data.selectedFlow.category">{{ data.selectedFlow.category }}</span>
                            </div>
                            <div v-if="data.selectedFlow.description" class="text-xs text-gray-500 mt-1 line-clamp-2">
                                {{ data.selectedFlow.description }}
                            </div>
                        </div>
                        <button
                            @click="clearSelection"
                            class="ml-2 w-6 h-6 rounded hover:bg-white/10 text-gray-400 hover:text-red-400 transition-colors flex items-center justify-center"
                            title="Clear selection"
                        >
                            <XIcon class="w-4 h-4" />
                        </button>
                    </div>
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

            <!-- ✨ NEW: Flow Configuration Status -->
            <div v-if="flowIdentificationInfo.method !== 'none'" class="bg-white/5 border border-white/10 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-2">
                    <span class="text-gray-300 font-medium">Configuration Status:</span>
                </div>

                <div class="text-sm text-pink-400 mb-1">{{ getFlowDescription }}</div>

                <div class="text-xs text-gray-500">
                    Method: {{ flowIdentificationInfo.description }}
                </div>

                <!-- Priority indicator -->
                <div class="flex items-center gap-2 mt-2">
                    <div class="text-xs">Priority:</div>
                    <div class="flex gap-1">
                        <div v-for="i in 4" :key="i"
                             :class="`w-2 h-2 rounded-full transition-colors ${
                                 i <= flowIdentificationInfo.priority
                                     ? (flowIdentificationInfo.isLatestVersion ? 'bg-green-500' : 'bg-yellow-500')
                                     : 'bg-gray-600'
                             }`"></div>
                    </div>
                    <div class="text-xs text-gray-400">
                        {{ flowIdentificationInfo.isLatestVersion ? '(Latest Version ✨)' : '(Specific Version)' }}
                    </div>
                </div>
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
import BreakpointToggle from '../nodeComponents/BreakpointToggle.vue'

const props = defineProps(['data', 'variables', 'id'])
const emit = defineEmits(['delete'])

// Flow search state
const flowSearchQuery = ref('')
const showFlowDropdown = ref(false)
const availableFlows = ref([])
const isLoadingFlows = ref(false)
const searchError = ref('')
const selectedIndex = ref(-1)
const dropdownRef = ref(null)

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

// ✨ NEW: Flow identification status
const flowIdentificationInfo = computed(() => {
    const info = {
        method: 'none',
        value: '',
        description: 'No flow specified',
        isLatestVersion: false,
        priority: 0
    }

    if (props.data.triggerCode) {
        info.method = 'triggerCode'
        info.value = props.data.triggerCode
        info.description = 'Using trigger code (always latest version)'
        info.isLatestVersion = true
        info.priority = 1
    } else if (props.data.selectedFlow?.trigger_code) {
        info.method = 'selectedFlow.triggerCode'
        info.value = props.data.selectedFlow.trigger_code
        info.description = 'Using selected flow trigger code (latest version)'
        info.isLatestVersion = true
        info.priority = 2
    } else if (props.data.selectedFlow?.id) {
        info.method = 'selectedFlow.id'
        info.value = props.data.selectedFlow.id
        info.description = 'Using selected flow ID (specific version)'
        info.isLatestVersion = false
        info.priority = 3
    } else if (props.data.flowId) {
        info.method = 'flowId'
        info.value = props.data.flowId
        info.description = 'Using flow ID (specific version, legacy)'
        info.isLatestVersion = false
        info.priority = 4
    }

    return info
})

// ✨ NEW: Get description for UI display
const getFlowDescription = computed(() => {
    const info = flowIdentificationInfo.value

    if (info.method === 'none') {
        return 'No flow configured'
    }

    let description = ''
    if (props.data.selectedFlow?.name) {
        description = `Will execute: ${props.data.selectedFlow.name}`
    } else if (info.method === 'triggerCode') {
        description = `Will execute flow with code: ${info.value}`
    } else {
        description = `Will execute flow ID: ${info.value}`
    }

    if (info.isLatestVersion) {
        description += ' (latest version ✨)'
    } else {
        description += ' (specific version)'
    }

    return description
})

// Load available flows on mount
onMounted(async () => {
    await loadFlows()
    document.addEventListener('click', handleClickOutside)

    // Initialize search field if flow is already selected
    if (props.data.selectedFlow && props.data.selectedFlow.name) {
        flowSearchQuery.value = props.data.selectedFlow.name
    } else if (props.data.triggerCode) {
        // ✨ NEW: Try to find flow by trigger code first
        const flow = availableFlows.value.find(f => f.trigger_code === props.data.triggerCode)
        if (flow) {
            props.data.selectedFlow = flow
            flowSearchQuery.value = flow.name
        }
    } else if (props.data.flowId && !props.data.selectedFlow) {
        // Try to find the flow by ID if we have an ID but no selected flow object
        const flow = availableFlows.value.find(f => f.id == props.data.flowId)
        if (flow) {
            props.data.selectedFlow = flow
            flowSearchQuery.value = flow.name
        }
    }
})

// Load flows from API
const loadFlows = async () => {
    try {
        isLoadingFlows.value = true
        searchError.value = ''

        // Try the enhanced endpoint first, fallback to regular diagrams
        let response
        try {
            response = await fetch('/api/witchcraft/diagrams-for-selection')
        } catch (error) {
            console.debug('Enhanced endpoint not available, using fallback')
            response = await fetch('/api/witchcraft/diagrams')
        }

        if (response.ok) {
            const data = await response.json()
            availableFlows.value = Array.isArray(data) ? data : (data.data || [])

            // Enrich data if basic endpoint was used
            if (!availableFlows.value[0]?.usage_count) {
                availableFlows.value = availableFlows.value.map(flow => ({
                    ...flow,
                    usage_count: 0,
                    last_used_at: null
                }))
            }
        } else {
            searchError.value = `Failed to load flows (${response.status})`
            availableFlows.value = []
        }
    } catch (error) {
        console.error('Failed to load flows:', error)
        searchError.value = 'Network error while loading flows'
        availableFlows.value = []
    } finally {
        isLoadingFlows.value = false
    }
}

// Enhanced search scoring function
const getSearchScore = (flow, query) => {
    const searchTerms = query.toLowerCase().split(' ').filter(term => term.length > 0)
    let score = 0

    searchTerms.forEach(term => {
        // Exact match in name gets highest score
        if (flow.name.toLowerCase() === term) score += 100
        // Name starts with term gets high score
        else if (flow.name.toLowerCase().startsWith(term)) score += 50
        // Name contains term gets medium score
        else if (flow.name.toLowerCase().includes(term)) score += 20

        // Description matches get lower scores
        if (flow.description && flow.description.toLowerCase().includes(term)) score += 10

        // Category matches
        if (flow.category && flow.category.toLowerCase().includes(term)) score += 15

        // ID exact match gets high score
        if (flow.id.toString() === term) score += 80
        // ID starts with term
        else if (flow.id.toString().startsWith(term)) score += 40
    })

    return score
}

// Computed filtered flows with enhanced search
const filteredFlows = computed(() => {
    if (!flowSearchQuery.value.trim()) {
        // When no search query, show recent or popular flows first
        return availableFlows.value
            .sort((a, b) => {
                // Prioritize flows with recent activity or higher usage
                const aScore = (a.last_used_at ? 10 : 0) + (a.usage_count || 0)
                const bScore = (b.last_used_at ? 10 : 0) + (b.usage_count || 0)
                return bScore - aScore
            })
            .slice(0, 8)
    }

    const query = flowSearchQuery.value.trim()

    // Score and filter flows
    const scoredFlows = availableFlows.value
        .map(flow => ({
            ...flow,
            searchScore: getSearchScore(flow, query)
        }))
        .filter(flow => flow.searchScore > 0)
        .sort((a, b) => b.searchScore - a.searchScore)
        .slice(0, 10)

    return scoredFlows
})

// Search flows (debounced with better UX)
let searchTimeout
const searchFlows = () => {
    clearTimeout(searchTimeout)
    selectedIndex.value = -1 // Reset selection

    searchTimeout = setTimeout(() => {
        // Show dropdown for any input, even empty (to show popular flows)
        showFlowDropdown.value = true
    }, 150) // Reduced debounce time for better responsiveness
}

// Handle keyboard navigation
const handleKeydown = (event) => {
    if (!showFlowDropdown.value || filteredFlows.value.length === 0) return

    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault()
            selectedIndex.value = Math.min(selectedIndex.value + 1, filteredFlows.value.length - 1)
            scrollToSelected()
            break
        case 'ArrowUp':
            event.preventDefault()
            selectedIndex.value = Math.max(selectedIndex.value - 1, -1)
            scrollToSelected()
            break
        case 'Enter':
            event.preventDefault()
            if (selectedIndex.value >= 0 && selectedIndex.value < filteredFlows.value.length) {
                selectFlow(filteredFlows.value[selectedIndex.value])
            }
            break
        case 'Escape':
            event.preventDefault()
            closeDropdown()
            break
    }
}

// Scroll to selected item in dropdown
const scrollToSelected = () => {
    nextTick(() => {
        const dropdown = dropdownRef.value
        if (!dropdown) return

        const selectedElement = dropdown.querySelector(`[data-index="${selectedIndex.value}"]`)
        if (selectedElement) {
            selectedElement.scrollIntoView({ block: 'nearest' })
        }
    })
}

// Close dropdown
const closeDropdown = () => {
    showFlowDropdown.value = false
    selectedIndex.value = -1
}

// Focus search input
const focusSearch = () => {
    showFlowDropdown.value = true
    if (flowSearchQuery.value.trim().length === 0 && availableFlows.value.length > 0) {
        // Show popular flows when focusing empty search
        showFlowDropdown.value = true
    }
}

// Select flow from dropdown
const selectFlow = (flow) => {
    props.data.selectedFlow = flow
    // ✨ NEW: Set trigger_code if available, otherwise fallback to id
    if (flow.trigger_code) {
        props.data.triggerCode = flow.trigger_code
        props.data.flowId = flow.id // Keep for display purposes
    } else {
        props.data.flowId = flow.id
        props.data.triggerCode = '' // Clear trigger code if not available
    }
    flowSearchQuery.value = flow.name
    closeDropdown()

    // Update usage tracking (if available)
    updateFlowUsage(flow.id)
}

// Update flow usage for better recommendations
const updateFlowUsage = async (flowId) => {
    try {
        await fetch(`/api/witchcraft/diagrams/${flowId}/usage`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
    } catch (error) {
        // Silent fail - this is just for analytics
        console.debug('Failed to update flow usage:', error)
    }
}

// Clear selection
const clearSelection = () => {
    props.data.selectedFlow = null
    props.data.flowId = ''
    props.data.triggerCode = '' // ✨ NEW: Clear trigger code too
    flowSearchQuery.value = ''
    closeDropdown()
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    const searchContainer = event.target.closest('.flow-search-container')
    if (!searchContainer) {
        closeDropdown()
    }
}

defineOptions({
    nodeMetadata: {
        category: 'Flow Control',
        icon: WorkflowIcon,
        label: 'Trigger Flow',
        initialData: {
            triggerCode: '', // ✨ NEW: Trigger code (preferred)
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

/* Enhanced search dropdown styles */
.flow-search-container .overflow-y-auto {
    scrollbar-width: thin;
    scrollbar-color: rgba(236, 72, 153, 0.3) transparent;
}

.flow-search-container .overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.flow-search-container .overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.flow-search-container .overflow-y-auto::-webkit-scrollbar-thumb {
    background-color: rgba(236, 72, 153, 0.3);
    border-radius: 3px;
}

.flow-search-container .overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background-color: rgba(236, 72, 153, 0.5);
}

/* Line clamp utility for text truncation */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Search result highlighting */
.flow-search-container [data-index] {
    position: relative;
}

.flow-search-container [data-index].selected-item {
    background-color: rgba(236, 72, 153, 0.15) !important;
    border-left: 3px solid #ec4899;
}

.flow-search-container [data-index].selected-item .font-medium {
    color: #fbbf24 !important; /* Yellow highlight for selected text */
}

.flow-search-container [data-index]:hover:not(.selected-item) {
    background-color: rgba(255, 255, 255, 0.08) !important;
}

/* Better visibility for dropdown items */
.flow-search-container [data-index] {
    backdrop-filter: blur(8px);
}

.flow-search-container [data-index] .font-medium {
    font-weight: 600;
    transition: color 0.2s ease;
}

/* Improved loading spinner */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Better focus states for accessibility */
.flow-search-container input:focus {
    box-shadow: 0 0 0 2px rgba(236, 72, 153, 0.2);
}

.flow-search-container button:focus {
    outline: 2px solid rgba(236, 72, 153, 0.5);
    outline-offset: 2px;
}

/* Smooth transitions for interactive elements */
.flow-search-container button,
.flow-search-container [data-index] {
    transition: all 0.15s ease-in-out;
}

/* Status indicator styles */
.flow-search-container .w-2.h-2 {
    box-shadow: 0 0 4px currentColor;
}
</style>
