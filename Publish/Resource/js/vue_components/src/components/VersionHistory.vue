<template>
    <div v-if="show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-[#111] border border-gray-700 rounded-xl w-[800px] max-h-[90vh] overflow-hidden">
            <!-- Header -->
            <div class="p-6 border-b border-gray-800">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-white">Version History</h2>
                        <p class="text-gray-400 text-sm mt-1">View and load previous versions</p>
                    </div>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-white">
                        <XIcon class="w-5 h-5" />
                    </button>
                </div>

                <!-- Search and Filters -->
                <div class="flex gap-3">
                    <div class="flex-1 relative">
                        <SearchIcon class="absolute left-3 top-3 w-4 h-4 text-gray-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search by version notes, date, or version number..."
                            class="w-full bg-[#1a1a1a] border border-gray-700 rounded-lg px-3 py-2 pl-10 text-white text-sm placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                        />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            class="absolute right-2 top-2 w-6 h-6 text-gray-400 hover:text-white rounded flex items-center justify-center"
                        >
                            <XIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Sort Options -->
                    <select
                        v-model="sortBy"
                        class="bg-[#1a1a1a] border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:border-blue-500 focus:outline-none"
                    >
                        <option value="version_desc">Version (Newest First)</option>
                        <option value="version_asc">Version (Oldest First)</option>
                        <option value="date_desc">Date (Newest First)</option>
                        <option value="date_asc">Date (Oldest First)</option>
                    </select>
                </div>
            </div>

            <!-- Versions List -->
            <div class="p-6 max-h-96 overflow-y-auto">
                <div v-if="loading" class="text-center py-8">
                    <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full mx-auto"></div>
                    <p class="text-gray-400 mt-2">Loading versions...</p>
                </div>

                <div v-else-if="versions.length === 0" class="text-center py-8">
                    <HistoryIcon class="w-12 h-12 text-gray-600 mx-auto mb-3" />
                    <p class="text-gray-400">No versions found</p>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="version in filteredAndSortedVersions"
                        :key="version.id"
                        class="group p-4 bg-[#1a1a1a] border rounded-lg transition-all"
                        :class="{
                            'border-blue-500 bg-blue-900/10': version.is_latest_version,
                            'border-gray-700 hover:border-gray-600': !version.is_latest_version
                        }"
                    >
                        <!-- Version Header -->
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br flex items-center justify-center"
                                     :class="{
                                         'from-blue-500 to-purple-600': version.is_latest_version,
                                         'from-gray-600 to-gray-700': !version.is_latest_version
                                     }">
                                    <span class="text-white font-bold text-sm">v{{ version.version }}</span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-medium text-white">Version {{ version.version }}</h3>
                                        <span v-if="version.is_latest_version"
                                              class="px-2 py-0.5 bg-blue-900/30 text-blue-400 text-xs rounded-full">
                                            CURRENT
                                        </span>
                                    </div>
                                    <p class="text-gray-400 text-sm">{{ formatDate(version.created_at) }}</p>
                                </div>
                            </div>

                            <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                    @click="previewVersion(version)"
                                    class="p-2 text-gray-400 hover:text-blue-400 hover:bg-blue-500/20 rounded"
                                    title="Preview version"
                                >
                                    <EyeIcon class="w-4 h-4" />
                                </button>
                                <button
                                    v-if="!version.is_latest_version"
                                    @click="restoreVersion(version)"
                                    class="p-2 text-gray-400 hover:text-blue-400 hover:bg-blue-500/20 rounded"
                                    title="Load this version for editing"
                                >
                                    <RotateCcwIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Version Notes -->
                        <div v-if="version.version_notes" class="mb-3">
                            <p class="text-gray-300 text-sm">{{ version.version_notes }}</p>
                        </div>

                        <!-- Version Stats -->
                        <div class="flex justify-between items-center text-xs text-gray-500">
                            <div class="flex items-center gap-4">
                                <span class="flex items-center gap-1">
                                    <GitBranchIcon class="w-3 h-3" />
                                    {{ getNodeCount(version) }} nodes
                                </span>
                                <span class="flex items-center gap-1">
                                    <LinkIcon class="w-3 h-3" />
                                    {{ getEdgeCount(version) }} connections
                                </span>
                            </div>
                            <span>{{ getTimeSince(version.created_at) }}</span>
                        </div>

                        <!-- Expanded Preview -->
                        <div v-if="previewingVersion === version.id" class="mt-4 pt-4 border-t border-gray-700">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <h4 class="text-gray-300 font-medium mb-2">Workflow Details</h4>
                                    <div class="space-y-1 text-xs">
                                        <div><span class="text-gray-400">Name:</span> <span class="text-white">{{ version.name }}</span></div>
                                        <div><span class="text-gray-400">Category:</span> <span class="text-white">{{ version.category }}</span></div>
                                        <div><span class="text-gray-400">Trigger:</span> <span class="text-green-400 font-mono">{{ version.trigger_code }}</span></div>
                                        <div><span class="text-gray-400">Protected:</span>
                                            <span :class="version.is_deletable ? 'text-gray-400' : 'text-red-400'">
                                                {{ version.is_deletable ? 'No' : 'Yes' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-gray-300 font-medium mb-2">Changes</h4>
                                    <div class="text-xs text-gray-400">
                                        {{ version.version_notes || 'No change notes provided' }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 mt-4">
                                <button
                                    @click="previewingVersion = null"
                                    class="px-3 py-1 text-xs bg-gray-700 text-white rounded hover:bg-gray-600"
                                >
                                    Close Preview
                                </button>
                                <button
                                    v-if="!version.is_latest_version"
                                    @click="restoreVersion(version)"
                                    class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-500"
                                >
                                    Load Version
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-gray-800 flex justify-between items-center">
                <div class="text-sm text-gray-400">
                    {{ filteredAndSortedVersions.length }} of {{ versions.length }} versions
                    <span v-if="searchQuery" class="text-blue-400">(filtered)</span>
                </div>
                <div class="flex gap-3">
                    <button
                        @click="refreshVersions"
                        :disabled="loading"
                        class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors disabled:opacity-50"
                    >
                        <RefreshCwIcon class="w-4 h-4 inline mr-1" />
                        Refresh
                    </button>
                    <button
                        @click="$emit('close')"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-500 transition-colors"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- Restore Confirmation Modal -->
        <div v-if="showRestoreConfirm" class="fixed inset-0 bg-black/70 flex items-center justify-center z-60">
            <div class="bg-[#111] border border-gray-700 rounded-xl w-[400px] mx-4">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Load Version</h3>
                    <p class="text-gray-300 text-sm mb-6">
                        Load version {{ restoreCandidate?.version }} into the editor for modification?
                        You can review and modify the workflow before saving as a new version.
                    </p>

                    <div class="flex justify-end gap-3">
                        <button
                            @click="showRestoreConfirm = false; restoreCandidate = null"
                            class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmRestore"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-500"
                        >
                            Load Version
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import {
    XIcon, HistoryIcon, EyeIcon, RotateCcwIcon, GitBranchIcon,
    LinkIcon, RefreshCwIcon, SearchIcon
} from 'lucide-vue-next';

// Props
const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    flowDiagramId: {
        type: Number,
        required: true
    }
});

// Emits
const emit = defineEmits(['close', 'version-restored']);

// State
const loading = ref(false);
const versions = ref([]);
const previewingVersion = ref(null);
const showRestoreConfirm = ref(false);
const restoreCandidate = ref(null);
const searchQuery = ref('');
const sortBy = ref('version_desc');

// Computed
const filteredAndSortedVersions = computed(() => {
    let filtered = [...versions.value];

    // Apply search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(version => {
            const versionNumber = `v${version.version}`.toLowerCase();
            const versionNotes = (version.version_notes || '').toLowerCase();
            const dateString = formatDate(version.created_at).toLowerCase();
            const name = (version.name || '').toLowerCase();
            const category = (version.category || '').toLowerCase();

            return versionNumber.includes(query) ||
                   versionNotes.includes(query) ||
                   dateString.includes(query) ||
                   name.includes(query) ||
                   category.includes(query) ||
                   query.includes(versionNumber.replace('v', ''));
        });
    }

    // Apply sorting
    filtered.sort((a, b) => {
        switch (sortBy.value) {
            case 'version_asc':
                return a.version - b.version;
            case 'version_desc':
                return b.version - a.version;
            case 'date_asc':
                return new Date(a.created_at) - new Date(b.created_at);
            case 'date_desc':
            default:
                return new Date(b.created_at) - new Date(a.created_at);
        }
    });

    return filtered;
});

// Methods
async function loadVersions() {
    if (!props.flowDiagramId) return;

    loading.value = true;
    try {
        const response = await fetch(`/api/witchcraft/diagrams/${props.flowDiagramId}/versions`);
        if (response.ok) {
            versions.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to load versions:', error);
    } finally {
        loading.value = false;
    }
}

function previewVersion(version) {
    previewingVersion.value = previewingVersion.value === version.id ? null : version.id;
}

function restoreVersion(version) {
    restoreCandidate.value = version;
    showRestoreConfirm.value = true;
}

async function confirmRestore() {
    if (!restoreCandidate.value) return;

    try {
        const response = await fetch(`/api/witchcraft/diagrams/${restoreCandidate.value.id}/restore`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });

        if (response.ok) {
            const result = await response.json();

            // Instead of reloading, emit the version data for loading into editor
            emit('version-restored', result.version_data);
            showRestoreConfirm.value = false;
            restoreCandidate.value = null;
            emit('close');
        } else {
            throw new Error('Failed to load version');
        }
    } catch (error) {
        console.error('Failed to load version:', error);
        alert('Failed to load version for editing');
    }
}

function refreshVersions() {
    loadVersions();
}

function getNodeCount(version) {
    try {
        const nodes = typeof version.nodes === 'string'
            ? JSON.parse(version.nodes)
            : version.nodes;
        return Array.isArray(nodes) ? nodes.length : 0;
    } catch {
        return 0;
    }
}

function getEdgeCount(version) {
    try {
        const edges = typeof version.edges === 'string'
            ? JSON.parse(version.edges)
            : version.edges;
        return Array.isArray(edges) ? edges.length : 0;
    } catch {
        return 0;
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString();
}

function getTimeSince(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'Today';
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) return `${diffDays} days ago`;
    if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;

    return `${Math.floor(diffDays / 30)} months ago`;
}

// Watch for dialog open/close
watch(() => props.show, (show) => {
    if (show) {
        loadVersions();
    } else {
        previewingVersion.value = null;
        showRestoreConfirm.value = false;
        restoreCandidate.value = null;
        searchQuery.value = ''; // Clear search when closing
    }
});
</script>

<style scoped>
/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: #374151;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #4b5563;
}
</style>
