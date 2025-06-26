<!-- Improved Controls.vue -->
<template>
    <Panel position="top-right">
        <div class="bg-[#111] border border-gray-700 rounded-xl shadow-2xl w-80 overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-gray-800 to-gray-700 p-4 border-b border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 bg-gradient-to-br from-blue-500 to-purple-600 rounded-md flex items-center justify-center">
                            <span class="text-white font-bold text-xs">C</span>
                        </div>
                        <h3 class="text-white font-semibold text-sm">Controls</h3>
                    </div>
                    <button
                        @click="collapsed = !collapsed"
                        class="p-1 rounded hover:bg-gray-600 transition-colors"
                        :title="collapsed ? 'Expand' : 'Collapse'"
                    >
                        <ChevronDownIcon
                            :class="`w-4 h-4 text-gray-400 transform transition-transform ${collapsed ? 'rotate-180' : ''}`"
                        />
                    </button>
                </div>
            </div>

            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="max-h-0 opacity-0"
                enter-to-class="max-h-[800px] opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="max-h-[800px] opacity-100"
                leave-to-class="max-h-0 opacity-0"
            >
                <div v-if="!collapsed" class="p-4 space-y-4 overflow-hidden">
                    <!-- Enhanced Search -->
                    <div class="space-y-3">
                        <h4 class="text-xs text-gray-400 uppercase tracking-wider font-medium">Node Library</h4>
                        <div class="relative">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search nodes..."
                                class="w-full bg-[#1a1a1a] border border-gray-600 rounded-lg px-3 py-2 pl-9 text-white text-sm placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none transition-all"
                            />
                            <SearchIcon class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                class="absolute right-2 top-2 w-6 h-6 text-gray-400 hover:text-white rounded flex items-center justify-center"
                            >
                                <XIcon class="w-3 h-3" />
                            </button>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="space-y-3">
                        <h4 class="text-xs text-gray-400 uppercase tracking-wider font-medium">Quick Actions</h4>

                        <!-- Primary Actions Row -->
                        <div class="grid grid-cols-2 gap-2">
                            <ActionButton
                                @click="$emit('save')"
                                icon="SaveIcon"
                                label="Save"
                                color="emerald"
                                size="sm"
                            />
                            <ActionButton
                                @click="$emit('load')"
                                icon="FolderIcon"
                                label="Load"
                                color="blue"
                                size="sm"
                            />
                        </div>

                        <!-- Simulation Control -->
                        <ActionButton
                            @click="$emit('simulate')"
                            icon="PlayIcon"
                            label="Run Simulation"
                            color="purple"
                            :full-width="true"
                        />

                        <!-- Secondary Actions Row -->
                        <div class="grid grid-cols-2 gap-2">
                            <ActionButton
                                @click="$emit('export')"
                                icon="DownloadIcon"
                                label="Export"
                                color="teal"
                                size="sm"
                            />
                            <ActionButton
                                @click="$emit('import')"
                                icon="UploadIcon"
                                label="Import"
                                color="orange"
                                size="sm"
                            />
                        </div>

                        <!-- Utility Actions Row -->
                        <div class="grid grid-cols-2 gap-2">
                            <ActionButton
                                @click="$emit('toggle-logs')"
                                icon="ListIcon"
                                label="Logs"
                                color="gray"
                                size="sm"
                            />
                            <ActionButton
                                @click="$emit('clear')"
                                icon="TrashIcon"
                                label="Clear"
                                color="red"
                                size="sm"
                            />
                        </div>
                    </div>

                    <!-- Simulation Settings -->
                    <div class="space-y-3">
                        <h4 class="text-xs text-gray-400 uppercase tracking-wider font-medium">Simulation</h4>

                        <!-- Speed Control -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <label class="text-sm text-gray-300">Speed</label>
                                <span class="text-xs text-gray-400 bg-[#1a1a1a] px-2 py-1 rounded font-mono">{{ speed }}ms</span>
                            </div>
                            <input
                                :value="speed"
                                @input="$emit('change-speed', parseInt($event.target.value))"
                                type="range"
                                min="100"
                                max="3000"
                                step="100"
                                class="w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer slider"
                            />
                            <div class="flex justify-between text-xs text-gray-500">
                                <span>Fast (100ms)</span>
                                <span>Slow (3s)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Node Categories -->
                    <div class="space-y-2">
                        <h4 class="text-xs text-gray-400 uppercase tracking-wider font-medium">Available Nodes</h4>

                        <div class="max-h-96 overflow-y-auto custom-scrollbar space-y-1">
                            <div v-for="(category, categoryName) in filteredNodes" :key="categoryName">
                                <!-- Category Header -->
                                <div
                                    @click="toggleCategory(categoryName)"
                                    class="flex items-center justify-between py-2 px-2 rounded-lg cursor-pointer hover:bg-[#1a1a1a] transition-colors group"
                                >
                                    <div class="flex items-center gap-2">
                                        <ChevronDownIcon
                                            :class="`w-3 h-3 text-gray-400 transition-transform ${expandedCategories[categoryName] ? 'rotate-90' : ''}`"
                                        />
                                        <span class="text-gray-300 text-sm font-medium group-hover:text-white transition-colors">{{ categoryName }}</span>
                                    </div>
                                    <span class="text-xs text-gray-500 bg-gray-800 px-1.5 py-0.5 rounded-full">{{ category.length }}</span>
                                </div>

                                <!-- Category Nodes -->
                                <Transition
                                    enter-active-class="transition-all duration-200 ease-out"
                                    enter-from-class="opacity-0 -translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition-all duration-150 ease-in"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 -translate-y-1"
                                >
                                    <div v-if="expandedCategories[categoryName]" class="ml-4 space-y-1">
                                        <div
                                            v-for="type in category"
                                            :key="type.id"
                                            draggable="true"
                                            @dragstart="onDragStart($event, type)"
                                            @dblclick="$emit('add-node', type)"
                                            class="group flex items-center gap-2 p-2 rounded-lg cursor-move hover:bg-[#1a1a1a] border border-transparent hover:border-gray-600 transition-all"
                                        >
                                            <!-- Icon -->
                                            <div class="w-6 h-6 rounded flex items-center justify-center bg-gray-700 group-hover:bg-blue-600 transition-colors flex-shrink-0">
                                                <template v-if="isLucideIcon(type.icon)">
                                                    <component :is="getLucideIcon(type.icon)" class="w-4 h-4 text-white" />
                                                </template>
                                                <template v-else-if="isSvgString(type.icon)">
                                                    <div v-html="sanitizeSvg(type.icon)" class="w-4 h-4 text-white"></div>
                                                </template>
                                                <template v-else>
                                                    <BoxIcon class="w-4 h-4 text-white" />
                                                </template>
                                            </div>

                                            <!-- Label -->
                                            <div class="flex-1 min-w-0">
                                                <div class="text-white text-sm font-medium group-hover:text-blue-300 transition-colors truncate">{{ type.label }}</div>
                                                <div class="text-gray-400 text-xs truncate">{{ type.description || 'Drag to canvas' }}</div>
                                            </div>

                                            <!-- Drag Indicator -->
                                            <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                                <MoveIcon class="w-3 h-3 text-gray-400" />
                                            </div>
                                        </div>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Tips -->
                    <div class="pt-3 border-t border-gray-700">
                        <details class="group">
                            <summary class="cursor-pointer text-xs text-gray-400 hover:text-white transition-colors flex items-center gap-2">
                                <HelpCircleIcon class="w-3 h-3" />
                                Quick Tips
                                <ChevronDownIcon class="w-3 h-3 transform group-open:rotate-180 transition-transform ml-auto" />
                            </summary>
                            <div class="mt-2 space-y-1 text-xs text-gray-500">
                                <div class="flex items-center gap-2">
                                    <span class="w-1 h-1 bg-blue-500 rounded-full"></span>
                                    <span>Drag nodes from library to canvas</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-1 h-1 bg-green-500 rounded-full"></span>
                                    <span>Double-click to add to center</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-1 h-1 bg-red-500 rounded-full"></span>
                                    <span>Alt+Click to disconnect</span>
                                </div>
                            </div>
                        </details>
                    </div>
                </div>
            </Transition>
        </div>
    </Panel>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { Panel } from '@vue-flow/core'
import * as LucideIcons from 'lucide-vue-next'
import {
    ChevronDownIcon,
    PlayIcon,
    DownloadIcon,
    UploadIcon,
    ListIcon,
    TrashIcon,
    SaveIcon,
    FolderIcon,
    SearchIcon,
    BoxIcon,
    XIcon,
    MoveIcon,
    HelpCircleIcon
} from 'lucide-vue-next'

// Props
const props = defineProps({
    speed: {
        type: Number,
        default: 1000
    }
})

// Emits (keeping all original functionality)
defineEmits([
    'simulate',
    'export',
    'import',
    'clear',
    'toggle-logs',
    'save',
    'load',
    'add-node',
    'change-speed'
])

// State
const collapsed = ref(false)
const nodeTypes = reactive([])
const expandedCategories = reactive({})
const searchQuery = ref('')

// Action Button Component
const ActionButton = {
    props: {
        icon: String,
        label: String,
        color: {
            type: String,
            default: 'blue'
        },
        size: {
            type: String,
            default: 'md'
        },
        fullWidth: {
            type: Boolean,
            default: false
        }
    },
    setup(props, { emit }) {
        const colors = {
            blue: 'from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600',
            emerald: 'from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600',
            purple: 'from-purple-600 to-purple-700 hover:from-purple-500 hover:to-purple-600',
            teal: 'from-teal-600 to-teal-700 hover:from-teal-500 hover:to-teal-600',
            orange: 'from-orange-600 to-orange-700 hover:from-orange-500 hover:to-orange-600',
            red: 'from-red-600 to-red-700 hover:from-red-500 hover:to-red-600',
            gray: 'from-gray-600 to-gray-700 hover:from-gray-500 hover:to-gray-600'
        }

        const sizes = {
            sm: 'px-3 py-2 text-xs',
            md: 'px-4 py-2 text-sm'
        }

        return { colors, sizes }
    },
    template: `
        <button
            @click="$emit('click')"
            :class="\`bg-gradient-to-r \${colors[color]} text-white rounded-lg shadow-sm transition-all duration-200 flex items-center gap-2 justify-center font-medium \${sizes[size]} \${fullWidth ? 'w-full' : ''} hover:scale-105 hover:shadow-lg active:scale-95\`"
        >
            <component :is="LucideIcons[icon]" class="w-4 h-4" />
            <span>{{ label }}</span>
        </button>
    `
}

// Icon Helper Functions (keeping original functionality)
function isLucideIcon(icon) {
    if (!icon) return false
    return typeof icon === 'string' && icon.endsWith('Icon') && icon in LucideIcons
}

function isSvgString(icon) {
    if (!icon) return false
    return typeof icon === 'string' && icon.trim().startsWith('<svg')
}

function getLucideIcon(iconName) {
    return LucideIcons[iconName] || LucideIcons.BoxIcon
}

function sanitizeSvg(svg) {
    if (!svg) return ''

    // Remove scripts and event handlers
    svg = svg.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
    svg = svg.replace(/\son\w+="[^"]*"/g, '')

    // Add sizing class
    if (!svg.includes('class="')) {
        svg = svg.replace('<svg', '<svg class="w-4 h-4"')
    }

    // Ensure currentColor is used for strokes
    if (!svg.includes('stroke="currentColor"')) {
        svg = svg.replace(/stroke="[^"]*"/g, 'stroke="currentColor"')
        if (!svg.includes('stroke=')) {
            svg = svg.replace('<svg', '<svg stroke="currentColor"')
        }
    }

    return svg
}

// Node loading (keeping original functionality)
onMounted(async () => {
    try {
        const defaultNodeFiles = import.meta.glob('./nodes/*.vue')

        const processNodeIcon = (node) => {
            const icon = node.icon
            if (!icon) return 'BoxIcon'
            if (typeof icon === 'string' && icon.endsWith('Icon')) return icon
            if (typeof icon === 'string' && icon.startsWith('<svg')) return icon
            return 'BoxIcon'
        }

        for (const path in defaultNodeFiles) {
            const fileName = path.split('/').pop().replace('Node.vue', '')
            const nodeType = fileName.toLowerCase()
            const module = await defaultNodeFiles[path]()

            nodeTypes.push({
                id: nodeType,
                ...(module.default?.nodeMetadata || module.nodeMetadata || {
                    category: 'Other',
                    icon: 'BoxIcon',
                    label: nodeType.replace(/^\w/, c => c.toUpperCase()),
                    initialData: { label: 'New Node' },
                    description: `A ${nodeType} node`
                }),
                icon: processNodeIcon(module.default?.nodeMetadata || module.nodeMetadata || {})
            })
        }

        // Set up categories (auto-expand all)
        const categories = nodeTypes.reduce((cats, node) => {
            cats[node.category] = true
            return cats
        }, {})
        Object.assign(expandedCategories, categories)

    } catch (error) {
        console.error('Failed to load nodes:', error)
    }
})

// Computed properties (keeping original functionality)
const filteredNodes = computed(() => {
    const query = searchQuery.value.toLowerCase()
    return nodeTypes.reduce((categories, node) => {
        if (
            !query ||
            node.label.toLowerCase().includes(query) ||
            node.category.toLowerCase().includes(query) ||
            (node.description && node.description.toLowerCase().includes(query))
        ) {
            if (!categories[node.category]) {
                categories[node.category] = []
            }
            categories[node.category].push(node)
        }
        return categories
    }, {})
})

// Functions (keeping original functionality)
function toggleCategory(categoryName) {
    expandedCategories[categoryName] = !expandedCategories[categoryName]
}

function onDragStart(event, type) {
    event.dataTransfer.setData('application/nodeType', JSON.stringify({
        type: type.id,
        initialData: type.initialData
    }))
    event.dataTransfer.effectAllowed = 'copy'

    // Visual feedback
    event.target.style.opacity = '0.6'
    setTimeout(() => {
        if (event.target) {
            event.target.style.opacity = '1'
        }
    }, 100)
}
</script>

<style scoped>
/* Enhanced slider styling */
.slider {
    background: linear-gradient(to right, #3b82f6 0%, #3b82f6 30%, #4b5563 30%, #4b5563 100%);
}

.slider::-webkit-slider-thumb {
    appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
    border: 2px solid #111;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
    transition: all 0.2s ease;
}

.slider::-webkit-slider-thumb:hover {
    background: #2563eb;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.slider::-moz-range-thumb {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
    border: 2px solid #111;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
    transition: all 0.2s ease;
}

/* Enhanced scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(75, 85, 99, 0.6);
    border-radius: 3px;
    transition: background 0.2s ease;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(75, 85, 99, 0.8);
}

/* Smooth animations */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Enhanced focus states */
button:focus,
input:focus,
summary:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Node drag visual feedback */
[draggable="true"]:active {
    cursor: grabbing;
    transform: scale(0.95);
}

/* Button loading states */
button:active {
    transform: scale(0.95);
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .w-80 {
        width: 18rem;
    }
}
</style>
