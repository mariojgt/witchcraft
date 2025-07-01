<template>
    <div v-if="show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-[#111] border border-gray-700 rounded-xl w-full max-w-7xl max-h-[95vh] flex flex-col overflow-hidden">
            <!-- Header -->
            <div class="flex-shrink-0 p-6 border-b border-gray-800">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-white">Workflow Files</h2>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-white transition-colors p-1 rounded-lg hover:bg-gray-800">
                        <XIcon class="w-5 h-5" />
                    </button>
                </div>

                <!-- Enhanced Search and Filters -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1 relative">
                        <SearchIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search workflows by name, description, or trigger..."
                            class="w-full bg-[#1a1a1a] border border-gray-700 rounded-lg px-3 py-2.5 pl-10 text-white text-sm placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none transition-all"
                        />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white"
                        >
                            <XIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Category Filter -->
                    <select
                        v-model="selectedCategory"
                        class="bg-[#1a1a1a] border border-gray-700 rounded-lg px-3 py-2.5 text-white text-sm focus:border-blue-500 focus:outline-none transition-all min-w-[140px]"
                    >
                        <option value="">All Categories</option>
                        <option v-for="category in categories" :key="category" :value="category">
                            {{ category }}
                        </option>
                    </select>

                    <!-- Sort Options -->
                    <select
                        v-model="sortBy"
                        class="bg-[#1a1a1a] border border-gray-700 rounded-lg px-3 py-2.5 text-white text-sm focus:border-blue-500 focus:outline-none transition-all min-w-[140px]"
                    >
                        <option value="created_at">Newest First</option>
                        <option value="name">Name A-Z</option>
                        <option value="updated_at">Recently Updated</option>
                    </select>
                </div>

                <!-- Stats Row -->
                <div class="flex justify-between items-center mt-4 text-sm text-gray-400">
                    <span>{{ filteredDiagrams.length }} of {{ diagrams.length }} workflows</span>
                    <div class="flex items-center gap-4">
                        <span v-if="selectedCategory" class="text-blue-400">
                            Category: {{ selectedCategory }}
                        </span>
                        <span v-if="searchQuery" class="text-green-400">
                            Search: "{{ searchQuery }}"
                        </span>
                    </div>
                </div>
            </div>

            <!-- Workflow Grid - Scrollable Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <div v-if="loading" class="flex items-center justify-center py-20">
                    <div class="text-center">
                        <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full mx-auto"></div>
                        <p class="text-gray-400 mt-3">Loading workflows...</p>
                    </div>
                </div>

                <div v-else-if="filteredDiagrams.length === 0" class="flex items-center justify-center py-20">
                    <div class="text-center">
                        <FolderIcon class="w-16 h-16 text-gray-600 mx-auto mb-4" />
                        <p class="text-gray-400 text-lg mb-2">No workflows found</p>
                        <p class="text-gray-500 text-sm mb-6">
                            {{ searchQuery || selectedCategory
                                ? 'Try adjusting your search or filters'
                                : 'Create your first workflow to get started' }}
                        </p>
                        <button
                            @click="$emit('create-new')"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-500 transition-colors flex items-center gap-2 mx-auto"
                        >
                            <PlusIcon class="w-4 h-4" />
                            New Workflow
                        </button>
                    </div>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div
                        v-for="diagram in filteredDiagrams"
                        :key="diagram.id"
                        class="group p-4 bg-[#1a1a1a] border border-gray-700 rounded-lg hover:border-gray-600 cursor-pointer transition-all hover:shadow-lg hover:shadow-blue-500/10 hover:transform hover:scale-[1.02] relative overflow-hidden"
                        @click="$emit('load-diagram', diagram)"
                    >
                        <!-- Header with Icon and Actions -->
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <!-- Dynamic Icon -->
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center group-hover:from-blue-400 group-hover:to-purple-500 transition-all flex-shrink-0">
                                    <component
                                        :is="getIcon(diagram.icon || 'WorkflowIcon')"
                                        class="w-5 h-5 text-white"
                                    />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-medium text-white group-hover:text-blue-400 transition-colors truncate text-sm">
                                        {{ diagram.name }}
                                    </h3>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
                                <button
                                    @click.stop="copyTriggerCode(diagram.trigger_code)"
                                    v-if="diagram.trigger_code"
                                    class="p-1.5 text-gray-400 hover:text-blue-400 hover:bg-blue-500/20 rounded transition-colors"
                                    title="Copy trigger code"
                                >
                                    <CopyIcon class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    @click.stop="$emit('delete-diagram', diagram)"
                                    class="p-1.5 text-gray-400 hover:text-red-400 hover:bg-red-500/20 rounded transition-colors"
                                    title="Delete workflow"
                                >
                                    <TrashIcon class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Badges Row -->
                        <div class="flex flex-wrap gap-1.5 mb-3">
                            <!-- Category Badge -->
                            <span v-if="diagram.category"
                                  class="px-2 py-0.5 bg-gray-800 text-gray-300 text-xs rounded-full">
                                {{ diagram.category }}
                            </span>
                            <!-- Trigger Badge -->
                            <span v-if="diagram.trigger_code"
                                  class="px-2 py-0.5 bg-green-900/30 text-green-400 text-xs rounded-full font-mono">
                                {{ diagram.trigger_code }}
                            </span>
                        </div>

                        <!-- Description -->
                        <p class="text-xs text-gray-400 mb-3 line-clamp-2 leading-relaxed">
                            {{ diagram.description || 'No description provided' }}
                        </p>

                        <!-- Stats -->
                        <div class="flex justify-between items-center text-xs text-gray-500">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center gap-1">
                                    <component :is="getIcon('GitBranchIcon')" class="w-3 h-3" />
                                    {{ getNodeCount(diagram) }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <component :is="getIcon('LinkIcon')" class="w-3 h-3" />
                                    {{ getEdgeCount(diagram) }}
                                </span>
                            </div>
                            <span class="text-right">{{ formatDate(diagram.updated_at || diagram.created_at) }}</span>
                        </div>

                        <!-- Hover overlay -->
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-500/5 to-purple-500/5 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none rounded-lg"></div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex-shrink-0 p-6 border-t border-gray-800 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="text-sm text-gray-400">
                    <span class="hidden sm:inline">Showing </span>{{ filteredDiagrams.length }} of {{ diagrams.length }} workflows
                    <span v-if="searchQuery || selectedCategory" class="text-blue-400 ml-2">
                        (filtered)
                    </span>
                </div>
                <div class="flex gap-3">
                    <button
                        @click="clearFilters"
                        v-if="searchQuery || selectedCategory"
                        class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors text-sm"
                    >
                        Clear Filters
                    </button>
                    <button
                        @click="$emit('close')"
                        class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors text-sm"
                    >
                        Close
                    </button>
                    <button
                        @click="$emit('create-new')"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-500 transition-colors flex items-center gap-2 text-sm"
                    >
                        <PlusIcon class="w-4 h-4" />
                        New Workflow
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast Notification -->
        <div
            v-if="showToast"
            class="fixed bottom-6 right-6 bg-green-600 text-white px-4 py-3 rounded-lg shadow-lg transition-all duration-300 z-60"
        >
            <div class="flex items-center gap-2">
                <component :is="getIcon('CheckIcon')" class="w-4 h-4" />
                {{ toastMessage }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import * as LucideIcons from 'lucide-vue-next';
import {
    XIcon, SearchIcon, FolderIcon, TrashIcon, PlusIcon, CopyIcon
} from 'lucide-vue-next';

// Props
const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    diagrams: {
        type: Array,
        default: () => []
    },
    loading: {
        type: Boolean,
        default: false
    }
});

// Emits
const emit = defineEmits(['close', 'load-diagram', 'delete-diagram', 'create-new']);

// State
const searchQuery = ref('');
const selectedCategory = ref('');
const sortBy = ref('created_at');
const showToast = ref(false);
const toastMessage = ref('');

// Computed
const categories = computed(() => {
    const cats = props.diagrams
        .map(diagram => diagram.category)
        .filter(Boolean)
        .filter((value, index, self) => self.indexOf(value) === index);
    return cats.sort();
});

const filteredDiagrams = computed(() => {
    let filtered = [...props.diagrams];

    // Apply search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(diagram =>
            diagram.name.toLowerCase().includes(query) ||
            (diagram.description && diagram.description.toLowerCase().includes(query)) ||
            (diagram.trigger_code && diagram.trigger_code.toLowerCase().includes(query)) ||
            (diagram.category && diagram.category.toLowerCase().includes(query))
        );
    }

    // Apply category filter
    if (selectedCategory.value) {
        filtered = filtered.filter(diagram => diagram.category === selectedCategory.value);
    }

    // Apply sorting
    filtered.sort((a, b) => {
        switch (sortBy.value) {
            case 'name':
                return a.name.localeCompare(b.name);
            case 'updated_at':
                return new Date(b.updated_at || b.created_at) - new Date(a.updated_at || a.created_at);
            case 'created_at':
            default:
                return new Date(b.created_at) - new Date(a.created_at);
        }
    });

    return filtered;
});

// Methods
function getIcon(iconName) {
    // Default icons mapping
    const iconMap = {
        'WorkflowIcon': 'GitBranchIcon',
        'DatabaseIcon': 'DatabaseIcon',
        'ApiIcon': 'GlobeIcon',
        'ProcessIcon': 'CpuIcon',
        'NotificationIcon': 'BellIcon',
        'ScheduleIcon': 'ClockIcon',
        'GitBranchIcon': 'GitBranchIcon',
        'LinkIcon': 'LinkIcon',
        'CheckIcon': 'CheckIcon'
    };

    const mappedIcon = iconMap[iconName] || iconName;
    return LucideIcons[mappedIcon] || LucideIcons.WorkflowIcon || LucideIcons.BoxIcon;
}

function getNodeCount(diagram) {
    try {
        const nodes = typeof diagram.nodes === 'string'
            ? JSON.parse(diagram.nodes)
            : diagram.nodes;
        return Array.isArray(nodes) ? nodes.length : 0;
    } catch {
        return 0;
    }
}

function getEdgeCount(diagram) {
    try {
        const edges = typeof diagram.edges === 'string'
            ? JSON.parse(diagram.edges)
            : diagram.edges;
        return Array.isArray(edges) ? edges.length : 0;
    } catch {
        return 0;
    }
}

function formatDate(dateString) {
    if (!dateString) return 'Unknown';

    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'Today';
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) return `${diffDays} days ago`;
    if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;

    return date.toLocaleDateString();
}

function copyTriggerCode(triggerCode) {
    if (!triggerCode) return;

    navigator.clipboard.writeText(triggerCode).then(() => {
        showToast.value = true;
        toastMessage.value = 'Trigger code copied!';
        setTimeout(() => {
            showToast.value = false;
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy: ', err);
    });
}

function clearFilters() {
    searchQuery.value = '';
    selectedCategory.value = '';
}

// Clear search when modal closes
watch(() => props.show, (newValue) => {
    if (!newValue) {
        searchQuery.value = '';
        selectedCategory.value = '';
    }
});
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Custom scrollbar for the workflow grid */
.overflow-y-auto::-webkit-scrollbar {
    width: 8px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #374151;
    border-radius: 4px;
    border: 1px solid transparent;
    background-clip: content-box;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #4b5563;
    background-clip: content-box;
}

/* Ensure proper text wrapping in cards */
.group h3 {
    word-break: break-word;
    hyphens: auto;
}

/* Responsive grid adjustments */
@media (max-width: 768px) {
    .grid {
        grid-template-columns: 1fr;
    }
}

@media (min-width: 769px) and (max-width: 1024px) {
    .grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1025px) and (max-width: 1400px) {
    .grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 1401px) {
    .grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
</style>
