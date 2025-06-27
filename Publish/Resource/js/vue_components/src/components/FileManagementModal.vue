<template>
    <div v-if="show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-[#111] border border-gray-700 rounded-xl w-[900px] max-h-[90vh] overflow-hidden">
            <!-- Header -->
            <div class="p-6 border-b border-gray-800">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-white">Workflow Files</h2>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-white">
                        <XIcon class="w-5 h-5" />
                    </button>
                </div>

                <!-- Enhanced Search and Filters -->
                <div class="flex gap-3">
                    <div class="flex-1 relative">
                        <SearchIcon class="absolute left-3 top-3 w-4 h-4 text-gray-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search workflows by name, description, or trigger..."
                            class="w-full bg-[#1a1a1a] border border-gray-700 rounded-lg px-3 py-2 pl-10 text-white text-sm placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                        />
                    </div>

                    <!-- Category Filter -->
                    <select
                        v-model="selectedCategory"
                        class="bg-[#1a1a1a] border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:border-blue-500 focus:outline-none"
                    >
                        <option value="">All Categories</option>
                        <option v-for="category in categories" :key="category" :value="category">
                            {{ category }}
                        </option>
                    </select>

                    <!-- Sort Options -->
                    <select
                        v-model="sortBy"
                        class="bg-[#1a1a1a] border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:border-blue-500 focus:outline-none"
                    >
                        <option value="created_at">Newest First</option>
                        <option value="name">Name A-Z</option>
                        <option value="updated_at">Recently Updated</option>
                    </select>
                </div>
            </div>

            <!-- Workflow Grid -->
            <div class="p-6 max-h-96 overflow-y-auto">
                <div v-if="loading" class="text-center py-8">
                    <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full mx-auto"></div>
                    <p class="text-gray-400 mt-2">Loading workflows...</p>
                </div>

                <div v-else-if="filteredDiagrams.length === 0" class="text-center py-8">
                    <FolderIcon class="w-12 h-12 text-gray-600 mx-auto mb-3" />
                    <p class="text-gray-400">No workflows found</p>
                    <p class="text-gray-500 text-sm">Try adjusting your search or create a new workflow</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="diagram in filteredDiagrams"
                        :key="diagram.id"
                        class="group p-4 bg-[#1a1a1a] border border-gray-700 rounded-lg hover:border-gray-600 cursor-pointer transition-all hover:shadow-lg hover:shadow-blue-500/10"
                        @click="$emit('load-diagram', diagram)"
                    >
                        <!-- Header with Icon and Actions -->
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center gap-3">
                                <!-- Dynamic Icon -->
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center group-hover:from-blue-400 group-hover:to-purple-500 transition-all">
                                    <component
                                        :is="getIcon(diagram.icon || 'WorkflowIcon')"
                                        class="w-6 h-6 text-white"
                                    />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-medium text-white group-hover:text-blue-400 transition-colors truncate">
                                        {{ diagram.name }}
                                    </h3>
                                    <div class="flex items-center gap-2 mt-1">
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
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                    @click.stop="copyTriggerCode(diagram.trigger_code)"
                                    v-if="diagram.trigger_code"
                                    class="p-1.5 text-gray-400 hover:text-blue-400 hover:bg-blue-500/20 rounded"
                                    title="Copy trigger code"
                                >
                                    <CopyIcon class="w-4 h-4" />
                                </button>
                                <button
                                    @click.stop="$emit('delete-diagram', diagram)"
                                    class="p-1.5 text-gray-400 hover:text-red-400 hover:bg-red-500/20 rounded"
                                    title="Delete workflow"
                                >
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Description -->
                        <p class="text-sm text-gray-400 mb-3 line-clamp-2">
                            {{ diagram.description || 'No description provided' }}
                        </p>

                        <!-- Stats -->
                        <div class="flex justify-between items-center text-xs text-gray-500">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center gap-1">
                                    <component :is="getIcon('GitBranchIcon')" class="w-3 h-3" />
                                    {{ getNodeCount(diagram) }} nodes
                                </span>
                                <span class="flex items-center gap-1">
                                    <component :is="getIcon('LinkIcon')" class="w-3 h-3" />
                                    {{ getEdgeCount(diagram) }} connections
                                </span>
                            </div>
                            <span>{{ formatDate(diagram.updated_at || diagram.created_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-gray-800 flex justify-between items-center">
                <div class="text-sm text-gray-400">
                    {{ filteredDiagrams.length }} of {{ diagrams.length }} workflows
                </div>
                <div class="flex gap-3">
                    <button
                        @click="$emit('close')"
                        class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors"
                    >
                        Close
                    </button>
                    <button
                        @click="$emit('create-new')"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-500 transition-colors flex items-center gap-2"
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
            class="fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg transition-all duration-300"
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
</style>
