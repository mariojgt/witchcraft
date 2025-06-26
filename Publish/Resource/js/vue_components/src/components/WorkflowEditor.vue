<template>
    <div class="h-screen bg-[#0a0a0a] relative flex">
        <!-- Professional Sidebar with Node Library -->
        <div class="w-72 bg-[#111] border-r border-gray-800 flex flex-col">
            <!-- Header -->
            <div class="p-4 border-b border-gray-800">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">W</span>
                    </div>
                    <h1 class="text-white font-semibold text-lg">Workflow Editor</h1>
                </div>

                <!-- Enhanced Search -->
                <div class="relative">
                    <input
                        v-model="searchQuery"
                        ref="searchInput"
                        type="text"
                        placeholder="Search nodes... (⌘K)"
                        class="w-full bg-[#1a1a1a] border border-gray-700 rounded-lg px-3 py-2 pl-9 text-white text-sm placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none transition-all"
                        @keydown.escape="searchQuery = ''"
                    />
                    <SearchIcon class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" />
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="absolute right-2 top-2 w-6 h-6 text-gray-400 hover:text-white rounded flex items-center justify-center"
                    >
                        <XIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Node Library -->
            <div class="flex-1 overflow-y-auto p-4">
                <div class="space-y-1">
                    <div v-for="(category, categoryName) in filteredNodes" :key="categoryName">
                        <!-- Category Header -->
                        <div
                            @click="toggleCategory(categoryName)"
                            class="flex items-center justify-between py-2 px-2 rounded-lg cursor-pointer hover:bg-[#1a1a1a] transition-colors group"
                        >
                            <div class="flex items-center gap-2">
                                <ChevronDownIcon
                                    :class="`w-4 h-4 text-gray-400 transition-transform ${expandedCategories[categoryName] ? 'rotate-90' : ''}`"
                                />
                                <span class="text-gray-300 text-sm font-medium group-hover:text-white transition-colors">{{ categoryName }}</span>
                            </div>
                            <span class="text-xs text-gray-500 bg-gray-800 px-2 py-0.5 rounded-full">{{ category.length }}</span>
                        </div>

                        <!-- Category Nodes -->
                        <div v-if="expandedCategories[categoryName]" class="ml-4 space-y-1">
                            <div
                                v-for="nodeType in category"
                                :key="nodeType.id"
                                draggable="true"
                                @dragstart="onNodeDragStart($event, nodeType)"
                                @dblclick="addNodeToCenter(nodeType)"
                                class="group flex items-center gap-3 p-3 rounded-lg cursor-move hover:bg-[#1a1a1a] border border-transparent hover:border-gray-700 transition-all"
                            >
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center group-hover:from-blue-600 group-hover:to-purple-600 transition-all">
                                    <!-- Dynamic Icon Rendering -->
                                    <template v-if="isLucideIcon(nodeType.icon)">
                                        <component :is="getLucideIcon(nodeType.icon)" class="w-5 h-5 text-white" />
                                    </template>
                                    <template v-else-if="isSvgString(nodeType.icon)">
                                        <div v-html="sanitizeSvg(nodeType.icon)" class="w-5 h-5 text-white"></div>
                                    </template>
                                    <template v-else>
                                        <BoxIcon class="w-5 h-5 text-white" />
                                    </template>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-white text-sm font-medium group-hover:text-blue-400 transition-colors">{{ nodeType.label }}</div>
                                    <div class="text-gray-400 text-xs truncate">{{ nodeType.description || 'Drag to add to canvas' }}</div>
                                </div>
                                <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                    <PlusIcon class="w-4 h-4 text-gray-400" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="mt-6 pt-4 border-t border-gray-800">
                    <div class="text-xs text-gray-400 uppercase tracking-wide font-medium mb-3">Simulation</div>
                    <div class="space-y-2">
                        <!-- Speed Control -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <label class="text-sm text-gray-400">Speed</label>
                                <span class="text-xs text-gray-500 bg-gray-800 px-2 py-1 rounded">{{ simulationSpeed }}ms</span>
                            </div>
                            <input
                                v-model="simulationSpeed"
                                @input="changeSimulationSpeed($event.target.value)"
                                type="range"
                                min="100"
                                max="3000"
                                step="100"
                                class="w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer slider"
                            />
                            <div class="flex justify-between text-xs text-gray-500">
                                <span>Fast</span>
                                <span>Slow</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Actions -->
            <div class="p-4 border-t border-gray-800 space-y-2">
                <div class="text-xs text-gray-400 uppercase tracking-wide font-medium mb-3">Keyboard Shortcuts</div>
                <div class="space-y-1 text-xs text-gray-500">
                    <div class="flex justify-between">
                        <span>Select All</span>
                        <kbd class="bg-gray-800 px-1.5 py-0.5 rounded text-xs">⌘A</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span>Copy</span>
                        <kbd class="bg-gray-800 px-1.5 py-0.5 rounded text-xs">⌘C</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span>Paste</span>
                        <kbd class="bg-gray-800 px-1.5 py-0.5 rounded text-xs">⌘V</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span>Undo</span>
                        <kbd class="bg-gray-800 px-1.5 py-0.5 rounded text-xs">⌘Z</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span>Disconnect</span>
                        <kbd class="bg-gray-800 px-1.5 py-0.5 rounded text-xs">Alt+Click</kbd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Canvas Area -->
        <div class="flex-1 flex flex-col">
            <!-- Top Toolbar -->
            <div class="h-14 bg-[#111] border-b border-gray-800 flex items-center justify-between px-4">
                <!-- Left Actions -->
                <div class="flex items-center gap-2">
                    <button
                        @click="undo"
                        :disabled="!canUndo"
                        class="p-2 rounded-lg hover:bg-[#1a1a1a] text-gray-400 hover:text-white disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        title="Undo (⌘Z)"
                    >
                        <UndoIcon class="w-4 h-4" />
                    </button>
                    <button
                        @click="redo"
                        :disabled="!canRedo"
                        class="p-2 rounded-lg hover:bg-[#1a1a1a] text-gray-400 hover:text-white disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        title="Redo (⌘Y)"
                    >
                        <RedoIcon class="w-4 h-4" />
                    </button>

                    <div class="w-px h-6 bg-gray-700 mx-1"></div>

                    <button
                        @click="selectAllNodes"
                        class="p-2 rounded-lg hover:bg-[#1a1a1a] text-gray-400 hover:text-white transition-colors"
                        title="Select All (⌘A)"
                    >
                        <TextSelect class="w-4 h-4" />
                    </button>

                    <button
                        @click="copySelected"
                        :disabled="selectedNodes.length === 0"
                        class="p-2 rounded-lg hover:bg-[#1a1a1a] text-gray-400 hover:text-white disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        title="Copy (⌘C)"
                    >
                        <CopyIcon class="w-4 h-4" />
                    </button>

                    <button
                        @click="pasteNodes"
                        :disabled="clipboard.length === 0"
                        class="p-2 rounded-lg hover:bg-[#1a1a1a] text-gray-400 hover:text-white disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        title="Paste (⌘V)"
                    >
                        <ClipboardIcon class="w-4 h-4" />
                    </button>

                    <div class="w-px h-6 bg-gray-700 mx-1"></div>

                    <button
                        @click="toggleLogs"
                        class="p-2 rounded-lg hover:bg-[#1a1a1a] text-gray-400 hover:text-white transition-colors"
                        title="Toggle Logs"
                    >
                        <ListIcon class="w-4 h-4" />
                    </button>

                    <button
                        @click="toggleSnapToGrid"
                        :class="`p-2 rounded-lg hover:bg-[#1a1a1a] transition-colors ${
                            snapToGrid ? 'text-blue-400 bg-blue-500/20' : 'text-gray-400 hover:text-white'
                        }`"
                        title="Toggle Snap to Grid"
                    >
                        <GridIcon class="w-4 h-4" />
                    </button>

                    <div class="w-px h-6 bg-gray-700 mx-1"></div>

                    <button
                        @click="toggleLogs"
                        class="p-2 rounded-lg hover:bg-[#1a1a1a] text-gray-400 hover:text-white transition-colors"
                        title="Toggle Logs"
                    >
                        <ListIcon class="w-4 h-4" />
                    </button>

                    <button
                        @click="clearLogs"
                        class="p-2 rounded-lg hover:bg-[#1a1a1a] text-gray-400 hover:text-white transition-colors"
                        title="Clear Logs"
                    >
                        <TrashIcon class="w-4 h-4" />
                    </button>
                </div>

                <!-- Center Info -->
                <div class="flex items-center gap-4 text-sm text-gray-400">
                    <span v-if="selectedNodes.length > 0">{{ selectedNodes.length }} selected</span>
                    <span>{{ nodes.length }} nodes</span>
                    <span>{{ edges.length }} connections</span>
                </div>

                <!-- Right Actions -->
                <div class="flex items-center gap-2">
                    <button
                        @click="showSidebar = !showSidebar"
                        class="px-3 py-1.5 text-sm bg-[#1a1a1a] hover:bg-gray-700 text-gray-300 hover:text-white rounded-lg transition-colors"
                    >
                        <FolderIcon class="w-4 h-4 inline mr-1" />
                        Files
                    </button>

                    <button
                        @click="startSimulation"
                        :disabled="nodes.length === 0"
                        class="px-4 py-1.5 text-sm bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed font-medium"
                    >
                        <PlayIcon class="w-4 h-4 inline mr-1" />
                        Run
                    </button>

                    <div class="relative">
                        <button
                            @click="showExportMenu = !showExportMenu"
                            class="px-3 py-1.5 text-sm bg-[#1a1a1a] hover:bg-gray-700 text-gray-300 hover:text-white rounded-lg transition-colors flex items-center gap-1"
                        >
                            <DownloadIcon class="w-4 h-4" />
                            Export
                            <ChevronDownIcon class="w-3 h-3" />
                        </button>

                        <!-- Export Dropdown -->
                        <div v-if="showExportMenu" class="absolute right-0 top-full mt-1 w-48 bg-[#1a1a1a] border border-gray-700 rounded-lg shadow-xl z-50">
                            <button @click="exportFlow(); showExportMenu = false" class="w-full text-left px-3 py-2 hover:bg-gray-700 text-gray-300 hover:text-white transition-colors first:rounded-t-lg">
                                <DownloadIcon class="w-4 h-4 inline mr-2" />
                                Export JSON
                            </button>
                            <button @click="importFlow(); showExportMenu = false" class="w-full text-left px-3 py-2 hover:bg-gray-700 text-gray-300 hover:text-white transition-colors">
                                <UploadIcon class="w-4 h-4 inline mr-2" />
                                Import JSON
                            </button>
                            <button @click="showSaveDialog = true; showExportMenu = false" class="w-full text-left px-3 py-2 hover:bg-gray-700 text-gray-300 hover:text-white transition-colors last:rounded-b-lg">
                                <SaveIcon class="w-4 h-4 inline mr-2" />
                                Save to Database
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Canvas -->
            <div class="flex-1 relative">
                <VueFlow
                    v-model:nodes="nodes"
                    v-model:edges="edges"
                    :class="{ 'simulation-mode': simulationState.isRunning }"
                    @connect="onConnect"
                    @node-click="onNodeClick"
                    @edge-click="onEdgeClick"
                    @node-double-click="onNodeDoubleClick"
                    @selection-change="onSelectionChange"
                    @dragover.prevent
                    @drop="onDrop"
                    @pane-click="onPaneClick"
                    class="workflow-canvas"
                    :default-viewport="{ zoom: 0.8 }"
                    :min-zoom="0.1"
                    :max-zoom="2"
                    :selection-key-code="null"
                    :multi-selection-key-code="['Meta', 'Control']"
                    :delete-key-code="['Delete', 'Backspace']"
                    :snap-to-grid="snapToGrid"
                    :snap-grid="[20, 20]"
                >
                    <!-- Enhanced Grid Background -->
                    <Background
                        variant="dots"
                        pattern-color="#2a2a2a"
                        :gap="20"
                        :size="2"
                        :offset="0"
                    />
                    <Background
                        variant="lines"
                        pattern-color="#1a1a1a"
                        :gap="100"
                        :size="1"
                        :offset="0"
                    />

                    <!-- Simulation Logs -->
                    <SimulationLogs v-if="showLogs"
                                   :logs="simulationLogs"
                                   :simulation-mode="simulationState.isRunning"
                                   @clear="clearLogs" />

                    <!-- Node Components -->
                    <template v-for="(nodeComponent, nodeType) in nodeComponents"
                            :key="nodeType"
                            #[`node-${nodeType}`]="nodeProps">
                        <component
                            :is="nodeComponent"
                            v-bind="nodeProps"
                            :class="[
                                nodeProps.class,
                                getNodeClass(nodeProps),
                                { 'simulation-active': simulationState.isRunning }
                            ]"
                            :output="nodeOutputs[nodeProps.id]"
                            @delete="() => removeNode(nodeProps.id)"
                        />
                    </template>
                </VueFlow>

                <!-- Simulation Progress -->
                <div v-if="simulationState.isRunning"
                     class="fixed bottom-4 right-4 bg-[#111] border border-gray-700 p-4 rounded-lg shadow-xl text-white z-50 min-w-[280px]">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                        <h3 class="text-sm font-semibold">Simulation Running</h3>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Current Node:</span>
                            <span class="text-blue-400 font-medium">{{ simulationState.currentNodeId || 'None' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Progress:</span>
                            <span class="text-green-400 font-medium">{{ completedCount }} / {{ nodes.length }}</span>
                        </div>
                        <div class="w-full bg-gray-700 rounded-full h-2">
                            <div class="bg-gradient-to-r from-blue-500 to-green-500 h-2 rounded-full transition-all duration-500"
                                 :style="{ width: `${progressPercentage}%` }"></div>
                        </div>
                        <div v-if="Object.keys(simulationState.variables).length > 0" class="mt-3">
                            <h4 class="text-xs font-semibold text-gray-300 mb-1">Variables:</h4>
                            <div class="space-y-1 max-h-24 overflow-y-auto">
                                <div v-for="(value, key) in simulationState.variables" :key="key"
                                     class="bg-gray-800 px-2 py-1 rounded text-xs">
                                    <span class="text-yellow-400">{{ key }}:</span>
                                    <span class="text-gray-200">{{ formatValue(value) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- File Management Modal -->
        <div v-if="showSidebar" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-[#111] border border-gray-700 rounded-xl w-[600px] max-h-[80vh] overflow-hidden">
                <div class="p-6 border-b border-gray-800">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-white">Workflow Files</h2>
                        <button @click="showSidebar = false" class="text-gray-400 hover:text-white">
                            <XIcon class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <div class="p-6 max-h-96 overflow-y-auto">
                    <div class="grid grid-cols-2 gap-4">
                        <div v-for="diagram in savedDiagrams" :key="diagram.id"
                            class="p-4 bg-[#1a1a1a] border border-gray-700 rounded-lg hover:border-gray-600 cursor-pointer transition-colors"
                            @click="loadDiagram(diagram)">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-medium text-white">{{ diagram.name }}</h3>
                                <button @click.stop="deleteDiagram(diagram)" class="text-red-400 hover:text-red-300">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                            <p class="text-sm text-gray-400">{{ diagram.description || 'No description' }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-t border-gray-800 flex justify-end gap-3">
                    <button @click="showSidebar = false" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600">
                        Close
                    </button>
                    <button @click="createNewWorkflow" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-500">
                        New Workflow
                    </button>
                </div>
            </div>
        </div>

        <!-- Save Modal -->
        <div v-if="showSaveDialog" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-[#111] border border-gray-700 rounded-xl w-96 mx-4">
                <div class="p-6 border-b border-gray-800">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-white">Save Workflow</h2>
                        <button @click="showSaveDialog = false" class="text-gray-400 hover:text-white">
                            <XIcon class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <input v-model="diagramName" type="text" placeholder="Workflow name"
                           class="w-full px-3 py-2 bg-[#1a1a1a] border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none" />

                    <textarea v-model="diagramDescription" placeholder="Description (optional)" rows="3"
                              class="w-full px-3 py-2 bg-[#1a1a1a] border border-gray-700 rounded-lg text-white placeholder-gray-400 resize-none focus:border-blue-500 focus:outline-none"></textarea>
                </div>

                <div class="p-6 border-t border-gray-800 flex justify-end gap-3">
                    <button @click="showSaveDialog = false" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600">
                        Cancel
                    </button>
                    <button @click="saveDiagram" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-500">
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, markRaw, nextTick, onBeforeUnmount } from 'vue';
import { VueFlow, useVueFlow } from "@vue-flow/core";
import { Background } from "@vue-flow/background";
import * as LucideIcons from 'lucide-vue-next';
import {
    XIcon, PlusIcon, EditIcon, TrashIcon, SearchIcon, ChevronDownIcon,
    PlayIcon, DownloadIcon, UploadIcon, ListIcon, SaveIcon, FolderIcon,
    UndoIcon, RedoIcon, TextSelect, CopyIcon, ClipboardIcon, BoxIcon,
    GridIcon
} from 'lucide-vue-next';
import DiagramService from './services/DiagramService';
import SimulationService from './services/SimulationService';
import SimulationLogs from './SimulationLogs.vue';

// Core state
const nodes = ref([]);
const edges = ref([]);
const selectedNodes = ref([]);
const selectedEdges = ref([]);
const nodeComponents = reactive({});
const snapToGrid = ref(true);
const { addEdges, removeNodes, addNodes, removeEdges, project, fitView, toObject } = useVueFlow();

// UI state
const showSaveDialog = ref(false);
const showSidebar = ref(false);
const showExportMenu = ref(false);
const savedDiagrams = ref([]);
const currentDiagramId = ref(null);
const diagramName = ref('');
const diagramDescription = ref('');
const searchQuery = ref('');
const searchInput = ref(null);

// Enhanced features state
const clipboard = ref([]);
const history = reactive({ past: [], future: [] });

// Node library state
const nodeTypes = reactive([]);
const expandedCategories = reactive({});

// Simulation state (keeping original functionality)
const showLogs = ref(true);
const simulationSpeed = ref(1000);
const simulationLogs = ref([]);
const nodeOutputs = reactive({});

const simulationState = reactive({
    isRunning: false,
    currentNodeId: null,
    nodeStatuses: {},
    variables: {}
});

// Initialize Services (keeping original)
const simulationService = new SimulationService();

// Setup simulation service callbacks (keeping original)
simulationService.onNodeStatusChange = (statuses, currentNodeId) => {
    console.log('Status Update:', { statuses, currentNodeId });
    simulationState.nodeStatuses = { ...statuses };
    simulationState.currentNodeId = currentNodeId;

    // Force Vue to update the node classes
    nodes.value = nodes.value.map(node => ({
        ...node,
        class: getNodeClass({ id: node.id })
    }));
};

simulationService.onLogAdded = (message, type) => {
    simulationLogs.value.push({
        message,
        type,
        timestamp: new Date()
    });
};

simulationService.onVariablesChange = (variables) => {
    simulationState.variables = { ...variables };
};

// Computed properties
const canUndo = computed(() => history.past.length > 0);
const canRedo = computed(() => history.future.length > 0);
const completedCount = computed(() =>
    Object.values(simulationState.nodeStatuses).filter(s => s === 'completed').length
);
const progressPercentage = computed(() =>
    nodes.value.length > 0 ? (completedCount.value / nodes.value.length) * 100 : 0
);

const filteredNodes = computed(() => {
    const query = searchQuery.value.toLowerCase();
    const filtered = query
        ? nodeTypes.filter(node =>
            node.label.toLowerCase().includes(query) ||
            node.category.toLowerCase().includes(query) ||
            (node.description && node.description.toLowerCase().includes(query))
          )
        : nodeTypes;

    return groupByCategory(filtered);
});

function groupByCategory(nodes) {
    return nodes.reduce((acc, node) => {
        if (!acc[node.category]) acc[node.category] = [];
        acc[node.category].push(node);
        return acc;
    }, {});
}

function formatValue(value) {
    if (typeof value === 'object') {
        return JSON.stringify(value, null, 2).substring(0, 50) + '...';
    }
    return String(value).substring(0, 50);
}

// History management
function saveToHistory() {
    history.past.push({
        nodes: JSON.parse(JSON.stringify(nodes.value)),
        edges: JSON.parse(JSON.stringify(edges.value))
    });
    history.future = [];

    if (history.past.length > 50) {
        history.past.shift();
    }
}

function undo() {
    if (!canUndo.value) return;

    const current = { nodes: nodes.value, edges: edges.value };
    history.future.unshift(current);

    const previous = history.past.pop();
    nodes.value = previous.nodes;
    edges.value = previous.edges;
}

function redo() {
    if (!canRedo.value) return;

    const current = { nodes: nodes.value, edges: edges.value };
    history.past.push(current);

    const next = history.future.shift();
    nodes.value = next.nodes;
    edges.value = next.edges;
}

// Enhanced selection management
function onSelectionChange({ nodes: selectedNodeItems, edges: selectedEdgeItems }) {
    selectedNodes.value = selectedNodeItems || [];
    selectedEdges.value = selectedEdgeItems || [];

    // Also update the nodes array to reflect selection state
    nodes.value = nodes.value.map(node => ({
        ...node,
        selected: selectedNodeItems.some(selectedNode => selectedNode.id === node.id)
    }));

    console.log('Selection changed:', selectedNodes.value.length, 'nodes selected');
}

function selectAllNodes() {
    nodes.value = nodes.value.map(node => ({ ...node, selected: true }));
    selectedNodes.value = nodes.value;
}

function onPaneClick() {
    selectedNodes.value = [];
    selectedEdges.value = [];
    nodes.value = nodes.value.map(node => ({ ...node, selected: false }));
    edges.value = edges.value.map(edge => ({ ...edge, selected: false }));
}

// Copy/Paste functionality
function copySelected() {
    // Get selected nodes from both sources to ensure we have them
    const nodesToCopy = selectedNodes.value.length > 0
        ? selectedNodes.value
        : nodes.value.filter(node => node.selected);

    if (nodesToCopy.length === 0) {
        console.log('No nodes selected for copying');
        return;
    }

    // Clear clipboard first to prevent stale data
    clipboard.value = [];

    // Deep clone the selected nodes with new IDs
    clipboard.value = nodesToCopy.map(node => ({
        ...JSON.parse(JSON.stringify(node)), // Deep clone to prevent reference issues
        // Don't generate new ID here - do it during paste instead
    }));

    console.log(`Copied ${clipboard.value.length} nodes:`, clipboard.value.map(n => n.id));
}

function pasteNodes() {
    if (clipboard.value.length === 0) {
        console.log('Clipboard is empty');
        return;
    }

    saveToHistory();

    // Generate fresh nodes from clipboard with unique IDs
    const timestamp = Date.now();
    const pastedNodes = clipboard.value.map((node, index) => ({
        ...JSON.parse(JSON.stringify(node)), // Deep clone
        id: `${node.type}-${timestamp}-${index}-${Math.random().toString(36).substr(2, 9)}`,
        position: {
            x: node.position.x + 50,
            y: node.position.y + 50
        },
        selected: true
    }));

    // Clear current selection
    nodes.value = nodes.value.map(node => ({ ...node, selected: false }));

    // Add new nodes
    addNodes(pastedNodes);

    // Update selection state
    selectedNodes.value = pastedNodes;

    console.log(`Pasted ${pastedNodes.length} nodes from clipboard:`, pastedNodes.map(n => n.id));
}

// Icon Helper Functions (keeping original Controls functionality)
function isLucideIcon(icon) {
    if (!icon) return false;
    return typeof icon === 'string' && icon.endsWith('Icon') && icon in LucideIcons;
}

function isSvgString(icon) {
    if (!icon) return false;
    return typeof icon === 'string' && icon.trim().startsWith('<svg');
}

function getLucideIcon(iconName) {
    return LucideIcons[iconName] || LucideIcons.BoxIcon;
}

function sanitizeSvg(svg) {
    if (!svg) return '';

    // Remove scripts
    svg = svg.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
    // Remove event handlers
    svg = svg.replace(/\son\w+="[^"]*"/g, '');
    // Add sizing class
    if (!svg.includes('class="')) {
        svg = svg.replace('<svg', '<svg class="w-5 h-5"');
    }
    // Ensure currentColor is used for strokes
    if (!svg.includes('stroke="currentColor"')) {
        svg = svg.replace(/stroke="[^"]*"/g, 'stroke="currentColor"');
        if (!svg.includes('stroke=')) {
            svg = svg.replace('<svg', '<svg stroke="currentColor"');
        }
    }
    return svg;
}

// Node library functions
function toggleCategory(categoryName) {
    expandedCategories[categoryName] = !expandedCategories[categoryName];
}

function onNodeDragStart(event, nodeType) {
    event.dataTransfer.setData('application/nodeType', JSON.stringify({
        type: nodeType.id,
        initialData: nodeType.initialData || { label: nodeType.label }
    }));
    event.dataTransfer.effectAllowed = 'copy';
}

function addNodeToCenter(nodeType) {
    if (!nodeType) return;

    saveToHistory();

    const newNode = {
        id: `${nodeType.id}-${Date.now()}`,
        type: nodeType.id.toLowerCase(),
        position: { x: 400, y: 300 },
        data: { ...nodeType.initialData || { label: nodeType.label } },
        selected: true
    };

    nodes.value = nodes.value.map(node => ({ ...node, selected: false }));

    addNodes([newNode]);
    selectedNodes.value = [newNode];
}

// Enhanced drop with smooth positioning (keeping original functionality)
function onDrop(event) {
    event.preventDefault();
    const nodeTypeData = event.dataTransfer.getData('application/nodeType');
    if (!nodeTypeData) return;

    const { type, initialData } = JSON.parse(nodeTypeData);

    saveToHistory();

    const position = project({
        x: event.clientX,
        y: event.clientY,
    });

    const newNode = {
        id: `${type}-${Date.now()}`,
        type: type.toLowerCase(),
        position,
        data: { ...initialData },
        selected: true
    };

    nodes.value = nodes.value.map(node => ({ ...node, selected: false }));

    addNodes([newNode]);
    selectedNodes.value = [newNode];
}

// Enhanced node interaction (keeping Alt+click functionality)
function onNodeClick(event) {
    const { node, event: clickEvent } = event;

    if (clickEvent.altKey) {
        const connectedEdges = edges.value.filter(edge =>
            edge.source === node.id || edge.target === node.id
        );

        if (connectedEdges.length > 0) {
            saveToHistory();
            removeEdges(connectedEdges);
            console.log(`Disconnected ${connectedEdges.length} connection(s)`);
        }
    }
}

function onEdgeClick(event) {
    const { edge, event: clickEvent } = event;

    if (clickEvent.altKey) {
        saveToHistory();
        removeEdges([edge]);
        console.log('Connection removed');
    }
}

// Keep original double-click functionality
function onNodeDoubleClick(event) {
    // Don't disconnect if double-clicking on input/textarea/select elements
    if (event.event?.target?.tagName?.toLowerCase() === 'input' ||
        event.event?.target?.tagName?.toLowerCase() === 'textarea' ||
        event.event?.target?.tagName?.toLowerCase() === 'select') {
        return;
    }

    const node = event.node;
    const edge = edges.value.find(edge => edge.source === node.id || edge.target === node.id);
    if (edge) {
        saveToHistory();
        removeEdges([edge]);
    }
}

function onConnect(params) {
    saveToHistory();
    addEdges([params]);
}

function removeNode(nodeId) {
    saveToHistory();
    removeNodes([nodeId]);
    delete nodeOutputs[nodeId];
}

// Keep original node class functionality
function getNodeClass(nodeProps) {
    console.log('Node Status:', {
        id: nodeProps.id,
        currentNode: simulationState.currentNodeId,
        status: simulationState.nodeStatuses[nodeProps.id],
        isCurrentNode: nodeProps.id === simulationState.currentNodeId
    });

    const status = simulationState.nodeStatuses[nodeProps.id];
    const isCurrentNode = nodeProps.id === simulationState.currentNodeId;

    return {
        'simulation-node': true,
        'current': isCurrentNode,
        'processing': status === 'processing',
        'completed': status === 'completed',
        'error': status === 'error',
        'highlight': isCurrentNode && status === 'processing'
    };
}

// Keep original simulation functions
function toggleLogs() {
    showLogs.value = !showLogs.value;
}

function toggleSnapToGrid() {
    snapToGrid.value = !snapToGrid.value;
    console.log(`Snap to grid ${snapToGrid.value ? 'enabled' : 'disabled'}`);
}

function clearLogs() {
    simulationLogs.value = [];
}

function changeSimulationSpeed(newSpeed) {
    simulationSpeed.value = parseInt(newSpeed);
    simulationService.setSimulationSpeed(simulationSpeed.value);
}

async function startSimulation() {
    try {
        simulationLogs.value = [];
        resetSimulationState();
        simulationState.isRunning = true;
        simulationService.setSimulationSpeed(simulationSpeed.value);

        const workflowData = toObject();
        await simulationService.processFlow(workflowData.nodes, workflowData.edges);
    } catch (error) {
        console.error('Simulation failed:', error);
    } finally {
        setTimeout(() => {
            resetSimulationState();
        }, 1000);
    }
}

function resetSimulationState() {
    simulationState.isRunning = false;
    simulationState.currentNodeId = null;
    simulationState.nodeStatuses = {};
    simulationState.variables = {};

    nodes.value = nodes.value.map(node => ({
        ...node,
        class: ''
    }));
}

// Keep original workflow management functions
function createNewWorkflow() {
    if (nodes.value.length > 0 && !confirm('Create new workflow? Current work will be lost.')) {
        return;
    }

    nodes.value = [];
    edges.value = [];
    selectedNodes.value = [];
    selectedEdges.value = [];
    currentDiagramId.value = null;
    diagramName.value = '';
    diagramDescription.value = '';
    showSidebar.value = false;

    history.past = [];
    history.future = [];

    console.log('New workflow created');
}

async function loadSavedDiagrams() {
    try {
        savedDiagrams.value = await DiagramService.fetchAll();
    } catch (error) {
        console.error('Failed to load diagrams:', error);
        alert('Failed to load saved diagrams');
    }
}

async function loadDiagram(diagram) {
    try {
        const loadedDiagram = await DiagramService.fetch(diagram.id);
        nodes.value = loadedDiagram.nodes || [];
        edges.value = loadedDiagram.edges || [];
        selectedNodes.value = [];
        selectedEdges.value = [];
        currentDiagramId.value = diagram.id;
        diagramName.value = diagram.name;
        diagramDescription.value = diagram.description || '';
        showSidebar.value = false;

        history.past = [];
        history.future = [];

        nextTick(() => {
            fitView({ duration: 800, padding: 0.2 });
        });

        console.log('Workflow loaded');
    } catch (error) {
        console.error('Failed to load diagram:', error);
        alert('Failed to load diagram');
    }
}

async function deleteDiagram(diagram) {
    if (!confirm(`Delete "${diagram.name}"?`)) return;

    try {
        await DiagramService.destroy(diagram.id);
        savedDiagrams.value = savedDiagrams.value.filter(d => d.id !== diagram.id);

        if (currentDiagramId.value === diagram.id) {
            createNewWorkflow();
        }
        console.log('Workflow deleted');
    } catch (error) {
        console.error('Failed to delete diagram:', error);
        alert('Failed to delete diagram');
    }
}

async function saveDiagram() {
    if (!diagramName.value.trim()) {
        alert('Please enter a workflow name');
        return;
    }

    try {
        const diagramData = {
            name: diagramName.value,
            description: diagramDescription.value,
            nodes: nodes.value,
            edges: edges.value
        };

        if (currentDiagramId.value) {
            await DiagramService.update(currentDiagramId.value, diagramData);
            alert('Diagram updated successfully');
        } else {
            const newDiagram = await DiagramService.store(diagramData);
            currentDiagramId.value = newDiagram.id;
            alert('Diagram created successfully');
        }

        await loadSavedDiagrams();
        showSaveDialog.value = false;

        if (!currentDiagramId.value) {
            diagramName.value = '';
            diagramDescription.value = '';
        }
    } catch (error) {
        console.error('Failed to save diagram:', error);
        alert(`Failed to ${currentDiagramId.value ? 'update' : 'save'} diagram`);
    }
}

// Keep original export/import functions
function exportFlow() {
    const flowData = {
        nodes: nodes.value,
        edges: edges.value,
        name: diagramName.value || 'workflow',
        metadata: {
            exportedAt: new Date().toISOString(),
            version: '1.0'
        }
    };

    const blob = new Blob([JSON.stringify(flowData, null, 2)], {
        type: 'application/json',
    });

    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${diagramName.value || 'workflow'}.json`;
    a.click();
    URL.revokeObjectURL(url);

    console.log('Workflow exported');
}

function importFlow() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json';
    input.onchange = async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        try {
            const text = await file.text();
            const data = JSON.parse(text);

            if (nodes.value.length > 0) {
                saveToHistory();
            }

            nodes.value = data.nodes || [];
            edges.value = data.edges || [];
            diagramName.value = data.name || '';
            selectedNodes.value = [];
            selectedEdges.value = [];

            nextTick(() => {
                fitView({ duration: 800, padding: 0.2 });
            });

            console.log('Workflow imported');
        } catch (error) {
            console.error('Import error:', error);
            alert('Failed to import workflow');
        }
    };
    input.click();
}

// Enhanced keyboard shortcuts - Fixed event handling
function handleKeyDown(event) {
    // Allow normal behavior in input fields
    if (event.target.tagName === 'INPUT' ||
        event.target.tagName === 'TEXTAREA' ||
        event.target.tagName === 'SELECT') {
        return;
    }

    const isCmd = event.metaKey || event.ctrlKey;

    switch (event.key.toLowerCase()) {
        case 'c':
            if (isCmd) {
                event.preventDefault();
                copySelected(); // Always try to copy when Cmd+C is pressed
            }
            break;

        case 'v':
            if (isCmd && clipboard.value.length > 0) {
                event.preventDefault();
                pasteNodes();
            }
            break;

        case 'delete':
        case 'backspace':
            if (selectedNodes.value.length > 0) {
                event.preventDefault();
                saveToHistory();
                const selectedIds = selectedNodes.value.map(node => node.id);
                removeNodes(selectedIds);
                selectedNodes.value = [];
            }
            break;
    }
}

function handleClickOutside(event) {
    if (!event.target.closest('.relative')) {
        showExportMenu.value = false;
    }
}

// Load node components (keeping original functionality)
onMounted(async () => {
    try {
        const defaultNodeFiles = import.meta.glob('./nodes/*.vue');

        for (const path in defaultNodeFiles) {
            const fileName = path.split('/').pop().replace('.vue', '');
            const nodeType = fileName.toLowerCase().replace('node', '');
            const module = await defaultNodeFiles[path]();

            // Register component
            nodeComponents[nodeType] = markRaw(module.default);

            // Add to node library with enhanced metadata
            const metadata = module.default?.nodeMetadata || {
                category: 'Other',
                icon: 'BoxIcon',
                label: nodeType.charAt(0).toUpperCase() + nodeType.slice(1),
                description: `A ${nodeType} node for workflow automation`,
                initialData: { label: 'New Node' }
            };

            nodeTypes.push({
                id: nodeType,
                icon: metadata.icon || 'BoxIcon',
                ...metadata
            });
        }

        // Auto-expand all categories initially
        const categories = [...new Set(nodeTypes.map(node => node.category))];
        categories.forEach(category => {
            expandedCategories[category] = true;
        });

        await loadSavedDiagrams();

        document.addEventListener('keydown', handleKeyDown);
        document.addEventListener('click', handleClickOutside);

        saveToHistory();

    } catch (error) {
        console.error('Failed to load components:', error);
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeyDown);
    document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.workflow-canvas {
    background: #0a0a0a !important;
}

/* Enhanced node styling with better selection visibility */
.vue-flow__node {
    transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1) !important;
    background: linear-gradient(145deg, #1a1a1a, #2a2a2a) !important;
    border: 2px solid #333 !important;
    border-radius: 12px !important;
    color: white !important;
    min-width: 180px !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4) !important;
    transform: translateZ(0) !important; /* Enable hardware acceleration */
}

.vue-flow__node:hover {
    border-color: #6366f1 !important;
    transform: translateY(-1px) translateZ(0) !important;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(99, 102, 241, 0.3) !important;
}

/* Enhanced selection visibility */
.vue-flow__node.selected {
    border-color: #3b82f6 !important;
    background: linear-gradient(145deg, #1e3a8a, #1d4ed8) !important;
    box-shadow:
        0 0 0 3px rgba(59, 130, 246, 0.4),
        0 0 20px rgba(59, 130, 246, 0.6),
        0 8px 24px rgba(0, 0, 0, 0.6) !important;
    transform: translateY(-2px) translateZ(0) !important;
    z-index: 100 !important;
}

/* Multiple selection styling */
.vue-flow__node.selected:not(:only-child) {
    border-color: #8b5cf6 !important;
    background: linear-gradient(145deg, #5b21b6, #7c3aed) !important;
    box-shadow:
        0 0 0 3px rgba(139, 92, 246, 0.4),
        0 0 20px rgba(139, 92, 246, 0.6),
        0 8px 24px rgba(0, 0, 0, 0.6) !important;
}

/* Dragging state for better feedback */
.vue-flow__node.dragging {
    transform: rotate(2deg) scale(1.05) translateZ(0) !important;
    box-shadow:
        0 12px 32px rgba(0, 0, 0, 0.7),
        0 0 0 2px rgba(59, 130, 246, 0.5) !important;
    z-index: 1000 !important;
}

/* Simulation states (keeping original functionality) */
.simulation-mode .vue-flow__node {
    opacity: 0.5 !important;
}

.vue-flow__node.simulation-node {
    opacity: 0.5;
}

.vue-flow__node.current {
    opacity: 1 !important;
    z-index: 9999 !important;
    background: linear-gradient(145deg, rgba(66, 153, 225, 0.2), rgba(66, 153, 225, 0.1)) !important;
    border: 3px solid #4299e1 !important;
    box-shadow:
        0 0 0 4px rgba(66, 153, 225, 0.2),
        0 0 20px rgba(66, 153, 225, 0.4),
        0 0 40px rgba(66, 153, 225, 0.2) !important;
    animation: nodeGlow 1.5s ease-in-out infinite !important;
}

.vue-flow__node.processing {
    opacity: 1 !important;
    border: 2px solid #4299e1 !important;
    box-shadow: 0 0 20px rgba(74, 144, 226, 0.7) !important;
    animation: nodeProcessing 1.5s infinite;
}

.vue-flow__node.completed {
    opacity: 1 !important;
    border: 2px solid #48bb78 !important;
    box-shadow: 0 0 20px rgba(39, 174, 96, 0.7) !important;
}

.vue-flow__node.error {
    opacity: 1 !important;
    border: 2px solid #f56565 !important;
    box-shadow: 0 0 20px rgba(245, 101, 101, 0.7) !important;
}

/* Enhanced selection box */
.vue-flow__selection {
    background: rgba(59, 130, 246, 0.15) !important;
    border: 2px solid #3b82f6 !important;
    border-radius: 8px !important;
    backdrop-filter: blur(4px) !important;
}

/* Enhanced edge styling with better selection visibility */
.vue-flow__edge-path {
    stroke: #6366f1 !important;
    stroke-width: 2px !important;
    filter: drop-shadow(0 0 4px rgba(99, 102, 241, 0.3)) !important;
    transition: all 0.15s ease !important;
}

.vue-flow__edge:hover .vue-flow__edge-path {
    stroke: #4f46e5 !important;
    stroke-width: 3px !important;
    filter: drop-shadow(0 0 8px rgba(79, 70, 229, 0.5)) !important;
}

.vue-flow__edge.selected .vue-flow__edge-path {
    stroke: #3b82f6 !important;
    stroke-width: 4px !important;
    filter: drop-shadow(0 0 12px rgba(59, 130, 246, 0.8)) !important;
    stroke-dasharray: 8 4 !important;
    animation: selectedEdgePulse 2s ease-in-out infinite !important;
}

@keyframes selectedEdgePulse {
    0%, 100% {
        stroke-dashoffset: 0;
        opacity: 1;
    }
    50% {
        stroke-dashoffset: 12;
        opacity: 0.8;
    }
}

/* Enhanced handle styling with better hover feedback */
.vue-flow__handle {
    width: 14px !important;
    height: 14px !important;
    background: #6366f1 !important;
    border: 3px solid #1a1a1a !important;
    border-radius: 50% !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
    transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.vue-flow__handle:hover {
    transform: scale(1.4) !important;
    background: #4f46e5 !important;
    box-shadow:
        0 0 16px rgba(79, 70, 229, 0.8),
        0 4px 12px rgba(0, 0, 0, 0.4) !important;
    border-color: #ffffff !important;
    z-index: 1000 !important;
}

.vue-flow__handle-source {
    background: #10b981 !important;
}

.vue-flow__handle-target {
    background: #f59e0b !important;
}

/* Grid background enhancements */
.vue-flow__background {
    opacity: 0.8 !important;
}

/* Performance optimizations */
.vue-flow__transformationpane {
    will-change: transform !important;
    transform-style: preserve-3d !important;
}

.vue-flow__node,
.vue-flow__edge {
    will-change: transform, box-shadow !important;
}

/* Enhanced slider styling */
.slider {
    background: linear-gradient(to right, #3b82f6 0%, #3b82f6 30%, #475569 30%, #475569 100%);
}

.slider::-webkit-slider-thumb {
    appearance: none;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
    border: 2px solid #1a1a1a;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
    transition: all 0.2s ease;
}

.slider::-webkit-slider-thumb:hover {
    background: #2563eb;
    transform: scale(1.1);
}

/* Enhanced scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: #374151;
    border-radius: 4px;
    border: 1px solid transparent;
    background-clip: content-box;
}

::-webkit-scrollbar-thumb:hover {
    background: #4b5563;
    background-clip: content-box;
}

/* Enhanced keyboard shortcut styling */
kbd {
    background: #374151;
    color: #d1d5db;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 11px;
    font-family: ui-monospace, monospace;
    border: 1px solid #4b5563;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* Focus states for accessibility */
button:focus,
input:focus,
textarea:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}
</style>
