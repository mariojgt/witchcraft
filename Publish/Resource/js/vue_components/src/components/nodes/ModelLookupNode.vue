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
}<template>
    <div :class="`bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-xl p-5 min-w-[320px] relative shadow-2xl transition-all duration-300 node-${data.colorTheme || 'purple'}`">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center border transition-all duration-300"
                     :class="getHeaderIconClass()">
                    <!-- DatabaseIcon for the node header -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="`w-5 h-5 transition-colors duration-300 ${getIconColorClass()}`">
                        <ellipse cx="12" cy="5" rx="9" ry="3"/>
                        <path d="M3 5V19A9 3 0 0 0 21 19V5"/>
                        <path d="M3 12A9 3 0 0 0 21 12"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <!-- Editable Title -->
                    <div v-if="!isEditingTitle"
                         @click="startEditingTitle"
                         class="font-semibold text-white text-sm cursor-pointer hover:text-purple-400 transition-colors truncate"
                         :title="data.customTitle || 'Model Lookup'">
                        {{ data.customTitle || 'Model Lookup' }}
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
                            {{ data.customDescription || 'Fetch a record by field from a database model' }}
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
                     class="space-y-1">
                    <!-- Main variable -->
                    <div @click="selectVariable(key)"
                         class="p-2 rounded cursor-pointer transition-all duration-200 bg-white/5 hover:bg-white/10 border border-white/10">
                        <div class="flex items-center justify-between">
                            <code class="text-purple-300 text-sm font-mono">{{ key }}</code>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-400 bg-gray-800 px-1.5 py-0.5 rounded">
                                    {{ getValueType(value) }}
                                </span>
                                <button v-if="canExpand(value)"
                                        @click.stop="toggleExpanded(key)"
                                        class="text-xs text-purple-400 hover:text-purple-300">
                                    {{ expandedVariables[key] ? '−' : '+' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Nested properties -->
                    <div v-if="expandedVariables[key] && canExpand(value)" class="ml-4 space-y-1">
                        <div v-for="(nestedValue, nestedKey) in getNestedProperties(value)"
                             :key="`${key}.${nestedKey}`"
                             @click="selectVariable(`${key}.${nestedKey}`)"
                             class="p-2 rounded cursor-pointer transition-all duration-200 bg-white/3 hover:bg-white/8 border border-white/5">
                            <div class="flex items-center justify-between">
                                <code class="text-purple-200 text-xs font-mono">{{ key }}.{{ nestedKey }}</code>
                                <span class="text-xs text-gray-500 bg-gray-800 px-1.5 py-0.5 rounded">
                                    {{ getValueType(nestedValue) }}
                                </span>
                            </div>
                            <div v-if="nestedValue !== null && nestedValue !== undefined" class="text-xs text-gray-400 mt-1 truncate">
                                {{ formatPreviewValue(nestedValue) }}
                            </div>
                        </div>
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
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Model</label>
                <select v-model="data.modelName" @change="clearInputs" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none">
                    <option value="">Select a model</option>
                    <option v-for="model in availableModels" :key="model" :value="model">{{ model }}</option>
                </select>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Search Field</label>
                <input v-model="data.searchField"
                       type="text"
                       placeholder="e.g., id, slug, product_id, email"
                       class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none" />
                <div class="text-xs text-purple-400/70">Field to search by (defaults to 'id' if empty)</div>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Source Variable (Optional)</label>
                <div class="relative">
                    <input v-model="data.sourceVariable"
                           type="text"
                           placeholder="e.g., extractedId, userId, report_data.product.id"
                           @focus="showVariableBrowser = true"
                           class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none"
                           @input="data.searchValue = '';" />

                    <!-- Variable exists indicator -->
                    <div v-if="data.sourceVariable && getVariableExists(data.sourceVariable)"
                         class="absolute right-3 top-1/2 transform -translate-y-1/2">
                        <div class="w-2 h-2 bg-green-500 rounded-full" title="Variable found"></div>
                    </div>
                    <div v-else-if="data.sourceVariable"
                         class="absolute right-3 top-1/2 transform -translate-y-1/2">
                        <div class="w-2 h-2 bg-yellow-500 rounded-full" title="Variable not found"></div>
                    </div>
                </div>
                <div class="text-xs text-purple-400/70">Use {{variableName}} to reference other variables (supports dot notation)</div>

                <!-- Live preview for source variable -->
                <div v-if="data.sourceVariable && getVariableValue(data.sourceVariable) !== undefined"
                     class="text-xs bg-gray-800 p-2 rounded">
                    <span class="text-gray-400">Current value:</span>
                    <span class="text-green-300 ml-2">{{ formatPreviewValue(getVariableValue(data.sourceVariable)) }}</span>
                </div>
            </div>

            <div class="space-y-2" v-if="!data.sourceVariable">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Search Value (Direct Input)</label>
                <input v-model="data.searchValue" type="text" placeholder="e.g., 123, my-product-slug, user@email.com" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none" />
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Output Key</label>
                <input v-model="data.outputKey" placeholder="Variable output key (e.g., fetchedUser)" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none" />
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Load Relationships (Optional)</label>
                <input v-model="data.withRelations" placeholder="e.g., profile, posts.comments, category.parent" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none" />
                <div class="text-xs text-purple-400/70">Comma-separated list of relationships to eager load (uses Eloquent models)</div>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-300 tracking-wide">Field to Extract (Optional)</label>
                <input v-model="data.fieldToExtract" placeholder="e.g., name, email, profile.avatar, posts.0.title" class="w-full text-sm bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-white placeholder-gray-500 focus:bg-white/10 focus:border-purple-500/50 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200 outline-none" />
                <div class="text-xs text-purple-400/70">Field name to extract (supports dot notation for nested fields) or leave empty for full record</div>
            </div>

            <div class="space-y-2">
                <label class="flex items-center gap-2 text-xs font-medium text-gray-300 tracking-wide">
                    <input type="checkbox" v-model="data.useEloquent" class="w-4 h-4 text-purple-600 bg-white/5 border border-white/10 rounded focus:ring-purple-500 focus:ring-2" />
                    Force Eloquent Usage
                </label>
                <div class="text-xs text-purple-400/70">Enable to use Eloquent models even without relationships (provides better data handling)</div>
            </div>

            <!-- Search Example Display -->
            <div v-if="data.modelName && (data.searchField || data.sourceVariable || data.searchValue)" class="p-3 bg-blue-500/5 border border-blue-500/20 rounded-lg">
                <div class="text-xs font-medium text-blue-400 mb-1">Query Preview:</div>
                <div class="text-xs text-gray-300 font-mono mb-2">
                    {{ getQueryPreview() }}
                </div>
                <div v-if="data.withRelations" class="text-xs text-green-400">
                    <span class="font-medium">With relationships:</span> {{ data.withRelations }}
                </div>
                <div v-if="data.fieldToExtract" class="text-xs text-yellow-400">
                    <span class="font-medium">Extract field:</span> {{ data.fieldToExtract }}
                </div>
            </div>

            <!-- Output indicators for true/false paths -->
            <div class="flex items-center justify-between pt-2">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full shadow-lg"></div>
                    <span class="text-xs text-gray-400">Record Found</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-red-500 rounded-full shadow-lg"></div>
                    <span class="text-xs text-gray-400">Not Found</span>
                </div>
            </div>
        </div>

        <!-- Connection Points -->
        <!-- Input pin (Target) -->
        <div class="absolute top-1/2 -left-2 transform -translate-y-1/2">
            <Handle type="target" position="left" class="!w-4 !h-4 !bg-gray-600 !border-2 !border-gray-800 hover:!bg-purple-500 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>

        <!-- Output pins (Source) -->
        <!-- True path: Record Found -->
        <div class="absolute top-1/2 -right-2 transform -translate-y-1/2">
            <Handle type="source" position="right" id="true" class="!w-4 !h-4 !bg-green-500 !border-2 !border-gray-800 hover:!bg-green-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
        <!-- False path: Not Found -->
        <div class="absolute bottom-0 left-1/2 transform translate-y-2 -translate-x-1/2">
            <Handle type="source" position="bottom" id="false" class="!w-4 !h-4 !bg-red-500 !border-2 !border-gray-800 hover:!bg-red-400 hover:!scale-110 transition-all duration-200 !rounded-full shadow-lg" />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { Handle } from '@vue-flow/core';
import { XIcon, MessageSquareIcon, SettingsIcon, SearchIcon } from "lucide-vue-next"
import BreakpointToggle from '../nodeComponents/BreakpointToggle.vue'

const props = defineProps(['data', 'variables']);
const emit = defineEmits(['delete']);

const availableModels = ref([]);

// UI state
const showComment = ref(false)
const showSettings = ref(false)
const showVariableBrowser = ref(false)
const isEditingTitle = ref(false)
const editingTitle = ref("")
const titleInput = ref(null)
const showTooltip = ref(false)
const expandedVariables = ref({})

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

// Helper functions
function clearInputs() {
    props.data.searchValue = '';
    props.data.sourceVariable = '';
    props.data.searchField = '';
    props.data.withRelations = '';
}

function getSearchValuePreview() {
    if (props.data.sourceVariable) {
        return `{{${props.data.sourceVariable}}}`;
    }
    return props.data.searchValue || 'search_value';
}

function getQueryPreview() {
    const useEloquent = props.data.useEloquent || props.data.withRelations;
    const modelName = props.data.modelName;
    const searchField = props.data.searchField || 'id';
    const searchValue = getSearchValuePreview();

    if (useEloquent) {
        let preview = `${modelName}::where('${searchField}', '${searchValue}')`;
        if (props.data.withRelations) {
            preview += `.with([${props.data.withRelations.split(',').map(r => `'${r.trim()}'`).join(', ')}])`;
        }
        preview += '.first()';
        return preview;
    } else {
        return `SELECT * FROM ${modelName} WHERE ${searchField} = '${searchValue}'`;
    }
}

// Variable browser functions
function selectVariable(variablePath) {
    props.data.sourceVariable = variablePath;
    props.data.searchValue = ''; // Clear direct input when using variable
    showVariableBrowser.value = false;
}

function toggleVariableBrowser() {
    showVariableBrowser.value = !showVariableBrowser.value;
    showComment.value = false;
    showSettings.value = false;
}

function getVariableExists(source) {
    if (!source || !props.variables) return false;
    return getVariableValue(source) !== undefined;
}

function getVariableValue(source) {
    if (!source || !props.variables) return undefined;

    // Handle dot notation (e.g., user.email, report_data.product.id)
    if (source.includes('.')) {
        const parts = source.split('.');
        let value = props.variables[parts[0]];

        for (let i = 1; i < parts.length && value != null; i++) {
            if (typeof value === 'object' && value !== null) {
                value = value[parts[i]];
            } else {
                return undefined;
            }
        }

        return value;
    }

    return props.variables[source];
}

function canExpand(value) {
    return value !== null && value !== undefined && typeof value === 'object' && !Array.isArray(value);
}

function toggleExpanded(key) {
    expandedVariables.value[key] = !expandedVariables.value[key];
}

function getNestedProperties(obj) {
    if (!obj || typeof obj !== 'object' || Array.isArray(obj)) return {};

    const result = {};
    Object.keys(obj).forEach(key => {
        result[key] = obj[key];
    });
    return result;
}

function getValueType(value) {
    if (value === null) return 'null';
    if (value === undefined) return 'undefined';
    if (Array.isArray(value)) return `array[${value.length}]`;
    if (typeof value === 'object') return 'object';
    return typeof value;
}

function formatPreviewValue(value) {
    if (value === null) return 'null';
    if (value === undefined) return 'undefined';

    const str = typeof value === 'object' ? JSON.stringify(value) : String(value);
    return str.length > 30 ? str.substring(0, 30) + '...' : str;
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

// Title editing functions
function startEditingTitle() {
    isEditingTitle.value = true
    editingTitle.value = props.data.customTitle || 'Model Lookup'
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

onMounted(() => {
  fetchModels();
});

// Fetches available database tables (models)
const fetchModels = async () => {
  try {
    const response = await fetch('/api/witchcraft/tables');
    if (!response.ok) {
        // Handle error more gracefully in production, maybe a notification
        console.error(`HTTP error! status: ${response.status}`);
        // Optionally: display a message in the UI if critical
    }
    availableModels.value = await response.json();
  } catch (error) {
    console.error('Failed to fetch models:', error);
  }
};

defineOptions({
  nodeMetadata: {
    category: 'Data',
    icon: null, // Icon is now an inline SVG in the template
    label: 'Model Lookup',
    initialData: {
      modelName: '',
      searchField: '', // Field to search by
      searchValue: '', // Direct input for search value
      sourceVariable: '', // Variable name for search value
      withRelations: '', // Relationships to eager load
      useEloquent: false, // Force Eloquent usage
      outputKey: 'fetchedRecord',
      fieldToExtract: '', // Field to extract from the found record (supports dot notation)
      // Enhanced properties
      customTitle: "",
      customDescription: "",
      comment: "",
      colorTheme: "purple"
    },
  },
});
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
