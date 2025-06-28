<template>
    <div :class="`bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-xl p-5 min-w-[320px] relative shadow-2xl transition-all duration-300 node-${data.colorTheme || 'blue'}`">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center border transition-all duration-300"
                     :class="getHeaderIconClass()">
                    <CalendarDays class="w-5 h-5 transition-colors duration-300" :class="getIconColorClass()" />
                </div>
                <div class="flex-1 min-w-0">
                    <div v-if="!isEditingTitle"
                         @click="startEditingTitle"
                         class="font-semibold text-white text-sm cursor-pointer hover:text-blue-400 transition-colors truncate"
                         :title="data.customTitle || 'Date Condition'">
                        {{ data.customTitle || 'Date Condition' }}
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
                            {{ data.customDescription || 'Compare dates and time periods' }}
                        </p>
                        <div v-if="data.comment && data.comment.trim()"
                             class="relative comment-indicator"
                             @mouseenter="showTooltip = true"
                             @mouseleave="showTooltip = false">
                            <div class="w-3 h-3 bg-yellow-500/30 border border-yellow-500/50 rounded-full flex items-center justify-center">
                                <MessageSquareIcon class="w-2 h-2 text-yellow-400" />
                            </div>

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

            <div class="flex items-center gap-1 ml-2">
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

        <div class="space-y-4">
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Comparison</label>
                <select v-model="data.comparisonType" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white focus:bg-white/10 focus:border-blue-500/50 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 outline-none">
                    <option value="equals">Equals (==)</option>
                    <option value="notEquals">Not Equals (!=)</option>
                    <option value="greaterThan">After (>)</option>
                    <option value="greaterThanOrEqual">After or Equal (>=)</option>
                    <option value="lessThan">Before (<)</option>
                    <option value="lessThanOrEqual">Before or Equal (<=)</option>
                    <option value="between">Between</option>
                    <option value="isToday">Is Today</option>
                    <option value="isWeekend">Is Weekend</option>
                    <option value="isWeekday">Is Weekday</option>
                </select>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Compare By</label>
                <select v-model="data.dateUnit" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white focus:bg-white/10 focus:border-blue-500/50 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 outline-none">
                    <option value="exact">Exact DateTime</option>
                    <option value="date">Date Only</option>
                    <option value="time">Time Only</option>
                    <option value="year">Year</option>
                    <option value="month">Month</option>
                    <option value="week">Week of Year</option>
                    <option value="day">Day of Month</option>
                    <option value="hour">Hour</option>
                    <option value="minute">Minute</option>
                </select>
            </div>

            <div class="space-y-2" v-if="!['isToday', 'isWeekend', 'isWeekday'].includes(data.comparisonType)">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">
                    {{ data.comparisonType === 'between' ? 'Start Date' : 'Compare Date' }}
                </label>
                <input v-model="computedExpectedValue"
                       :type="getInputType()"
                       :placeholder="getPlaceholder()"
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-blue-500/50 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 outline-none" />
                <p class="text-xs text-gray-500 mt-1">{{ getFormatHint() }}</p>
            </div>

            <div class="space-y-2" v-if="data.comparisonType === 'between'">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">End Date</label>
                <input v-model="computedEndValue"
                       :type="getInputType()"
                       :placeholder="getPlaceholder()"
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-blue-500/50 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 outline-none" />
                <p class="text-xs text-gray-500 mt-1">{{ getFormatHint() }}</p>
            </div>

            <div class="flex items-center gap-2" v-if="!['isToday', 'isWeekend', 'isWeekday', 'between'].includes(data.comparisonType)">
                <input v-model="data.useCurrentDate"
                       type="checkbox"
                       id="useCurrentDate"
                       class="w-4 h-4 text-blue-500 bg-white/5 border border-white/10 rounded focus:ring-blue-500/20 focus:ring-2" />
                <label for="useCurrentDate" class="text-xs text-gray-300">Compare with current date/time</label>
            </div>

            <div class="bg-white/5 border border-white/10 rounded-lg p-3" v-if="getPreviewText()">
                <div class="text-xs text-gray-400 mb-1">Preview:</div>
                <div class="text-xs text-blue-300 font-mono">{{ getPreviewText() }}</div>
            </div>

            <div class="flex items-center justify-between pt-2">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full shadow-lg"></div>
                    <span class="text-xs text-gray-400">True path</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-red-500 rounded-full shadow-lg"></div>
                    <span class="text-xs text-gray-400">False path</span>
                </div>
            </div>
        </div>

        <div class="absolute top-1/2 -left-2 transform -translate-y-1/2">
            <Handle type="target" position="left" class="!w-4 !h-4 !bg-gray-600 !border-2 !border-gray-800 hover:!bg-blue-500 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
        <div class="absolute top-1/2 -right-2 transform -translate-y-1/2">
            <Handle type="source" position="right" id="true" class="!w-4 !h-4 !bg-green-500 !border-2 !border-gray-800 hover:!bg-green-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
        <div class="absolute bottom-0 left-1/2 transform translate-y-2 -translate-x-1/2">
            <Handle type="source" position="bottom" id="false" class="!w-4 !h-4 !bg-red-500 !border-2 !border-gray-800 hover:!bg-red-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
    </div>
</template>

<script setup>
import { ref, nextTick, computed } from 'vue' // Import 'computed'
import { Handle } from "@vue-flow/core"
import { XIcon, CalendarDays, MessageSquareIcon, SettingsIcon } from "lucide-vue-next"
import { defineOptions } from "vue"
import BreakpointToggle from '../nodeComponents/BreakpointToggle.vue'

defineOptions({
    nodeMetadata: {
        category: "Logic",
        icon: "CalendarDays",
        label: "Date Condition",
        description: "Compare dates and time periods with flexible conditions",
        initialData: {
            comparisonType: "equals",
            dateUnit: "exact",
            expectedValue: "",
            endValue: "",
            useCurrentDate: false,
            customTitle: "",
            customDescription: "",
            comment: "",
            colorTheme: "blue"
        },
    },
})

const props = defineProps(["data", "id"])
defineEmits(["delete"])

// UI state - no changes here, they are good
const showComment = ref(false)
const showSettings = ref(false)
const isEditingTitle = ref(false)
const editingTitle = ref("")
const titleInput = ref(null)
const showTooltip = ref(false)

// --- NEW: Computed properties for expectedValue and endValue ---
const computedExpectedValue = computed({
    get() {
        // When the input displays, use the raw value
        // If it's a date-only type, ensure it's just the date part for display
        if (props.data.dateUnit === 'date' && props.data.expectedValue && props.data.expectedValue.includes('T')) {
            return props.data.expectedValue.split('T')[0];
        }
        return props.data.expectedValue;
    },
    set(newValue) {
        // When the input changes, normalize the value
        if (newValue) {
            if (props.data.dateUnit === 'date') {
                // If it's a date type, ensure no time part
                props.data.expectedValue = newValue.split('T')[0];
            } else if (props.data.dateUnit === 'exact') {
                // If exact, and only date is provided, append T00:00
                if (newValue.length === 10 && !newValue.includes('T')) { // YYYY-MM-DD
                    props.data.expectedValue = newValue + 'T00:00';
                } else if (newValue.length > 10 && newValue.includes('T') && newValue.endsWith('T')) {
                     // Handle cases where user might clear time leaving trailing 'T'
                    props.data.expectedValue = newValue + '00:00';
                }
                else {
                    props.data.expectedValue = newValue;
                }
            } else {
                props.data.expectedValue = newValue;
            }
        } else {
            props.data.expectedValue = ''; // Clear if input is empty
        }
    }
});

const computedEndValue = computed({
    get() {
        // When the input displays, use the raw value
        if (props.data.dateUnit === 'date' && props.data.endValue && props.data.endValue.includes('T')) {
            return props.data.endValue.split('T')[0];
        }
        return props.data.endValue;
    },
    set(newValue) {
        // When the input changes, normalize the value
        if (newValue) {
            if (props.data.dateUnit === 'date') {
                props.data.endValue = newValue.split('T')[0];
            } else if (props.data.dateUnit === 'exact') {
                if (newValue.length === 10 && !newValue.includes('T')) {
                    props.data.endValue = newValue + 'T00:00';
                } else if (newValue.length > 10 && newValue.includes('T') && newValue.endsWith('T')) {
                    props.data.endValue = newValue + '00:00';
                }
                else {
                    props.data.endValue = newValue;
                }
            } else {
                props.data.endValue = newValue;
            }
        } else {
            props.data.endValue = '';
        }
    }
});
// --- END NEW COMPUTED PROPERTIES ---


// Color theme functions - no changes needed
function getHeaderIconClass() {
    const theme = props.data.colorTheme || 'blue'
    const classes = {
        blue: 'bg-gradient-to-br from-blue-500/20 to-blue-600/20 border-blue-500/20',
        purple: 'bg-gradient-to-br from-purple-500/20 to-purple-600/20 border-purple-500/20',
        green: 'bg-gradient-to-br from-green-500/20 to-green-600/20 border-green-500/20',
        red: 'bg-gradient-to-br from-red-500/20 to-red-600/20 border-red-500/20',
        yellow: 'bg-gradient-to-br from-yellow-500/20 to-yellow-600/20 border-yellow-500/20',
        pink: 'bg-gradient-to-br from-pink-500/20 to-pink-600/20 border-pink-500/20',
        gray: 'bg-gradient-to-br from-gray-500/20 to-gray-600/20 border-gray-500/20'
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
        gray: 'text-gray-400'
    }
    return classes[theme] || classes.blue
}

const colorThemes = [
    { name: 'blue', label: 'Blue', gradient: 'linear-gradient(145deg, #3b82f6, #1d4ed8)' },
    { name: 'purple', label: 'Purple', gradient: 'linear-gradient(145deg, #8b5cf6, #7c3aed)' },
    { name: 'green', label: 'Green', gradient: 'linear-gradient(145deg, #10b981, #059669)' },
    { name: 'red', label: 'Red', gradient: 'linear-gradient(145deg, #ef4444, #dc2626)' },
    { name: 'yellow', label: 'Yellow', gradient: 'linear-gradient(145deg, #f59e0b, #d97706)' },
    { name: 'pink', label: 'Pink', gradient: 'linear-gradient(145deg, #ec4899, #db2777)' },
    { name: 'gray', label: 'Gray', gradient: 'linear-gradient(145deg, #6b7280, #4b5563)' }
]

// Comment functions - no changes needed
function toggleComment() {
    showComment.value = !showComment.value
    showSettings.value = false
}

function saveComment() {
    showComment.value = false
}

// Settings functions - no changes needed
function toggleSettings() {
    showSettings.value = !showSettings.value
    showComment.value = false
}

// Title editing functions - no changes needed
function startEditingTitle() {
    isEditingTitle.value = true
    editingTitle.value = props.data.customTitle || 'Date Condition'
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

// Input type and placeholder logic - crucial for getting correct formats
function getInputType() {
    switch (props.data.dateUnit) {
        case 'exact': return 'datetime-local'
        case 'date': return 'date'
        case 'time': return 'time'
        case 'year':
        case 'month':
        case 'week':
        case 'day':
        case 'hour':
        case 'minute': return 'number'
        default: return 'datetime-local'
    }
}

function getPlaceholder() {
    switch (props.data.dateUnit) {
        case 'exact': return 'e.g., 2024-12-25T14:30' // HTML datetime-local format
        case 'date': return 'e.g., 2024-12-25' // HTML date format
        case 'time': return 'e.g., 14:30' // HTML time format
        case 'year': return 'e.g., 2024'
        case 'month': return 'e.g., 12 (1-12)'
        case 'week': return 'e.g., 52 (week of year)'
        case 'day': return 'e.g., 25 (day of month)'
        case 'hour': return 'e.g., 14 (0-23)'
        case 'minute': return 'e.g., 30 (0-59)'
        default: return 'Enter value...'
    }
}

function getFormatHint() {
    if (getInputType() === 'number') {
        return `Enter a numeric value for the ${props.data.dateUnit}.`
    }
    if (props.data.dateUnit === 'exact') {
        return 'Format: YYYY-MM-DDTHH:MM (e.g., 2024-12-25T14:30). Time defaults to 00:00 if not specified.'
    }
    if (props.data.dateUnit === 'date') {
        return 'Format: YYYY-MM-DD (e.g., 2024-12-25). Time is ignored.'
    }
    if (props.data.dateUnit === 'time') {
        return 'Format: HH:MM (e.g., 14:30)'
    }
    return 'The input type will automatically guide the format.'
}

function getPreviewText() {
    if (['isToday', 'isWeekend', 'isWeekday'].includes(props.data.comparisonType)) {
        return `Check if the input date is ${props.data.comparisonType.replace('is', '').toLowerCase().trim()}.`
    }

    // Only show preview if there's enough data for non-special comparisons
    // Use the *actual* prop data for preview, not the computed refs
    if (!props.data.expectedValue && props.data.comparisonType !== 'between') return ''
    if (props.data.comparisonType === 'between' && (!props.data.expectedValue || !props.data.endValue)) return ''

    const comparisonText = {
        'equals': 'is equal to',
        'notEquals': 'is not equal to',
        'greaterThan': 'is after',
        'greaterThanOrEqual': 'is after or equal to',
        'lessThan': 'is before',
        'lessThanOrEqual': 'is before or equal to',
        'between': 'is between'
    }[props.data.comparisonType]

    const unitText = props.data.dateUnit === 'exact' ? 'exact datetime' : props.data.dateUnit

    let preview = `Input's ${unitText} ${comparisonText} `

    if (props.data.comparisonType === 'between') {
        preview += `${props.data.expectedValue} and ${props.data.endValue || '?'}`
    } else {
        preview += props.data.expectedValue
    }

    if (props.data.useCurrentDate && !['isToday', 'isWeekend', 'isWeekday', 'between'].includes(props.data.comparisonType)) {
        preview += ' (using current date/time for comparison)'
    }

    return preview
}
</script>

<style scoped>
/* Your existing styles are good, no changes needed here */
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
