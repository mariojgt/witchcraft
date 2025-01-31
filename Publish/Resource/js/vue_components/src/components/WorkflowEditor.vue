<!-- WorkflowEditor.vue -->
<template>
    <div class="relative h-full">
        <!-- Save Modal -->
        <div v-if="showSaveDialog"
             class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-gray-800 p-6 rounded-lg w-[425px] max-w-full mx-4">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-white">Save Diagram</h2>
                    <button @click="showSaveDialog = false" class="text-gray-400 hover:text-white">
                        <XIcon class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-200">Name</label>
                        <input v-model="diagramName"
                               type="text"
                               placeholder="Enter diagram name"
                               class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-200">Description</label>
                        <textarea v-model="diagramDescription"
                                  rows="3"
                                  placeholder="Enter diagram description"
                                  class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white resize-none"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button @click="showSaveDialog = false"
                            class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600">
                        Cancel
                    </button>
                    <button @click="saveDiagram"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-500">
                        Save
                    </button>
                </div>
            </div>
        </div>

        <!-- Load Diagrams Sidebar -->
        <div v-if="showSidebar"
            class="fixed left-0 top-0 h-full w-64 bg-gray-800 text-white p-4 z-50 overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">Flow Diagrams</h2>
                <button @click="showSidebar = false" class="text-gray-400 hover:text-white">
                    <XIcon class="w-5 h-5" />
                </button>
            </div>

            <button @click="createNewDiagram"
                    class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg mb-4 flex items-center justify-center gap-2">
                <PlusIcon class="w-4 h-4" />
                New Diagram
            </button>

            <div class="space-y-2">
                <div v-for="diagram in savedDiagrams"
                    :key="diagram.id"
                    class="bg-gray-700 p-3 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="font-medium">{{ diagram.name }}</span>
                        <div class="flex gap-2">
                            <button @click="loadDiagram(diagram)"
                                    class="text-blue-400 hover:text-blue-300">
                                <EditIcon class="w-4 h-4" />
                            </button>
                            <button @click="deleteDiagram(diagram)"
                                    class="text-red-400 hover:text-red-300">
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                    <p class="text-sm text-gray-400 mt-1">{{ diagram.description || 'No description' }}</p>
                </div>
            </div>
        </div>

        <!-- Simulation Progress -->
        <div v-if="simulationState.isRunning"
             class="fixed bottom-4 right-4 bg-gray-800 p-4 rounded-lg shadow-lg text-white z-50">
            <h3 class="text-lg font-semibold mb-2">Simulation Progress</h3>
            <div class="space-y-1">
                <p>Current Node: {{ simulationState.currentNodeId || 'None' }}</p>
                <p>Completed: {{ Object.values(simulationState.nodeStatuses).filter(s => s === 'completed').length }} / {{ nodes.length }}</p>
                <div v-if="Object.keys(simulationState.variables).length > 0">
                    <h4 class="text-sm font-semibold mt-2">Variables:</h4>
                    <div class="text-sm opacity-80">
                        <div v-for="(value, key) in simulationState.variables" :key="key">
                            {{ key }}: {{ value }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Flow Editor -->
        <VueFlow v-model:nodes="nodes"
                 v-model:edges="edges"
                 :class="{ 'simulation-mode': simulationState.isRunning }"
                 @connect="onConnect"
                 @dragover.prevent
                 @node-double-click="onNodeDoubleClick"
                 @drop="onDrop">

            <!-- Controls -->
            <Controls @simulate="startSimulation"
                     @export="exportFlow"
                     @import="importFlow"
                     @clear="clearLogs"
                     @toggle-logs="toggleLogs"
                     @save="showSaveDialog = true"
                     @load="showSidebar = true"
                     :speed="simulationSpeed"
                     @change-speed="changeSimulationSpeed" />

            <Background />

            <!-- Simulation Logs -->
            <SimulationLogs v-if="showLogs"
                           :logs="simulationLogs"
                           :simulation-mode="simulationState.isRunning"
                           @clear="clearLogs" />

            <!-- Node Components -->
            <template v-for="(nodeComponent, nodeType) in nodeComponents"
                    :key="nodeType"
                    #[`node-${nodeType}`]="nodeProps">
                <component :is="nodeComponent"
                        v-bind="nodeProps"
                        :class="[
                            nodeProps.class,
                            getNodeClass(nodeProps),
                            simulationState.isRunning ? 'simulation-active' : ''
                        ]"
                        :output="nodeOutputs[nodeProps.id]"
                        @delete="() => removeNode(nodeProps.id)" />
            </template>
        </VueFlow>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, markRaw } from 'vue';
import { VueFlow, useVueFlow } from "@vue-flow/core";
import { Background } from "@vue-flow/background";
import { XIcon, PlusIcon, EditIcon, TrashIcon } from 'lucide-vue-next';
import DiagramService from './services/DiagramService';
import SimulationService from './services/SimulationService';
import SimulationLogs from './SimulationLogs.vue';
import Controls from './Controls.vue';

const showSidebar = ref(false);
const savedDiagrams = ref([]);
const currentDiagramId = ref(null);

// Load functions
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
        nodes.value = loadedDiagram.nodes;
        edges.value = loadedDiagram.edges;
        currentDiagramId.value = diagram.id;
        diagramName.value = diagram.name;
        diagramDescription.value = diagram.description || '';
        showSidebar.value = false;
    } catch (error) {
        console.error('Failed to load diagram:', error);
        alert('Failed to load diagram');
    }
}

function createNewDiagram() {
    nodes.value = [];
    edges.value = [];
    currentDiagramId.value = null;
    diagramName.value = '';
    diagramDescription.value = '';
    showSidebar.value = false;
}

async function deleteDiagram(diagram) {
    if (!confirm('Are you sure you want to delete this diagram?')) return;

    try {
        await DiagramService.destroy(diagram.id);
        savedDiagrams.value = savedDiagrams.value.filter(d => d.id !== diagram.id);

        if (currentDiagramId.value === diagram.id) {
            createNewDiagram();
        }
    } catch (error) {
        console.error('Failed to delete diagram:', error);
        alert('Failed to delete diagram');
    }
}

// Initialize Services
const simulationService = new SimulationService();

// Flow State
const nodes = ref([]);
const edges = ref([]);
const nodeComponents = reactive({});
const { addEdges, removeNodes, addNodes, toObject, removeEdges } = useVueFlow();

// Simulation State
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

// UI State
const showSaveDialog = ref(false);
const diagramName = ref('');
const diagramDescription = ref('');

// Setup simulation service callbacks
simulationService.onNodeStatusChange = (statuses, currentNodeId) => {
    console.log('Status Update:', { statuses, currentNodeId }); // Debug log
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

// Diagram Management Functions
async function saveDiagram() {
    if (!diagramName.value.trim()) {
        alert('Please enter a diagram name');
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
            // Update existing diagram
            await DiagramService.update(currentDiagramId.value, diagramData);
            alert('Diagram updated successfully');
        } else {
            // Create new diagram
            const newDiagram = await DiagramService.store(diagramData);
            currentDiagramId.value = newDiagram.id;
            alert('Diagram created successfully');
        }

        // Refresh diagrams list
        loadSavedDiagrams();
        showSaveDialog.value = false;

        // Only clear form if it was a new diagram
        if (!currentDiagramId.value) {
            diagramName.value = '';
            diagramDescription.value = '';
        }
    } catch (error) {
        console.error('Failed to save diagram:', error);
        alert(`Failed to ${currentDiagramId.value ? 'update' : 'save'} diagram`);
    }
}

// Flow Management Functions
function onConnect(params) {
    addEdges([params]);
}

function onNodeDoubleClick(event) {
    const node = event.node;

    // now find the edge where i clicked and remove it
    const edge = edges.value.find(edge => edge.source === node.id || edge.target === node.id);
    if (edge) {
        removeEdges([edge]);
    }
}

function removeNode(nodeId) {
    removeNodes([nodeId]);
    delete nodeOutputs[nodeId];
}

function onDrop(event) {
    event.preventDefault();
    const nodeTypeData = event.dataTransfer.getData('application/nodeType');
    if (!nodeTypeData) return;

    const { type, initialData } = JSON.parse(nodeTypeData);
    const newNode = {
        id: `${type}-${Date.now()}`,
        type: type.toLowerCase(),
        position: {
            x: event.clientX - event.target.getBoundingClientRect().left,
            y: event.clientY - event.target.getBoundingClientRect().top,
        },
        data: initialData,
        class: '',
    };
    addNodes([newNode]);
}

function getNodeClass(nodeProps) {
    // Debug the current node status
    console.log('Node Status:', {
        id: nodeProps.id,
        currentNode: simulationState.currentNodeId,
        status: simulationState.nodeStatuses[nodeProps.id],
        isCurrentNode: nodeProps.id === simulationState.currentNodeId
    });

    const status = simulationState.nodeStatuses[nodeProps.id];
    const isCurrentNode = nodeProps.id === simulationState.currentNodeId;

    // Return object with all classes explicitly set
    return {
        'simulation-node': true,
        'current': isCurrentNode,
        'processing': status === 'processing',
        'completed': status === 'completed',
        'error': status === 'error',
        'highlight': isCurrentNode && status === 'processing'
    };
}

function toggleLogs() {
    showLogs.value = !showLogs.value;
}

function clearLogs() {
    simulationLogs.value = [];
}

function changeSimulationSpeed(newSpeed) {
    simulationSpeed.value = newSpeed;
    simulationService.setSimulationSpeed(newSpeed);
}

async function startSimulation() {
    try {
        simulationLogs.value = [];
        resetSimulationState(); // Reset before starting
        simulationState.isRunning = true;
        simulationService.setSimulationSpeed(simulationSpeed.value);

        const workflowData = toObject();
        await simulationService.processFlow(workflowData.nodes, workflowData.edges);
    } catch (error) {
        console.error('Simulation failed:', error);
    } finally {
        // Add small delay before resetting to show the final state briefly
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

    // Reset all node classes
    nodes.value = nodes.value.map(node => ({
        ...node,
        class: ''
    }));
}

// Export/Import Functions
function exportFlow() {
    const flowData = { nodes: nodes.value, edges: edges.value };
    const blob = new Blob([JSON.stringify(flowData, null, 2)], {
        type: 'application/json',
    });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'workflow.json';
    a.click();
    URL.revokeObjectURL(url);
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
            nodes.value = data.nodes;
            edges.value = data.edges;
        } catch (error) {
            console.error('Import error:', error);
            alert('Failed to import diagram');
        }
    };
    input.click();
}

// Load Node Components
onMounted(async () => {
    try {
        // Load both default and custom components
        const defaultNodeFiles = import.meta.glob('./nodes/*.vue');
        const customNodeFiles = import.meta.glob('/resources/js/witchcraft/nodes/*.vue');

        // Load default components
        for (const path in defaultNodeFiles) {
            const fileName = path.split('/').pop().replace('.vue', '');
            const nodeType = fileName.toLowerCase().replace('node', '');
            const module = await defaultNodeFiles[path]();
            nodeComponents[nodeType] = markRaw(module.default);
        }

        // Load saved diagrams
        await loadSavedDiagrams();
    } catch (error) {
        console.error('Failed to load nodes:', error);
    }
});
</script>

<style>
.vue-flow__node {
    transition: all 0.3s ease !important;
}

.simulation-mode .vue-flow__node {
    opacity: 0.5 !important;
}

.vue-flow__node.simulation-node {
    opacity: 0.5;
}

.vue-flow__node.current {
    opacity: 1 !important;
    z-index: 9999 !important;
    background-color: rgba(66, 153, 225, 0.1) !important;
    border: 3px solid #4299e1 !important;
    box-shadow:
        0 0 0 4px rgba(66, 153, 225, 0.2),
        0 0 20px rgba(66, 153, 225, 0.4),
        0 0 40px rgba(66, 153, 225, 0.2) !important;
    animation: nodeGlow 1.5s ease-in-out infinite !important;
}

@keyframes nodeGlow {
    0%, 100% {
        box-shadow:
            0 0 0 4px rgba(66, 153, 225, 0.2),
            0 0 20px rgba(66, 153, 225, 0.4),
            0 0 40px rgba(66, 153, 225, 0.2);
    }
    50% {
        box-shadow:
            0 0 0 6px rgba(66, 153, 225, 0.3),
            0 0 30px rgba(66, 153, 225, 0.5),
            0 0 50px rgba(66, 153, 225, 0.3);
    }
}

@keyframes nodePulse {
    0% {
        transform: scale(1.1) !important;
        box-shadow:
            0 0 0 4px rgba(66, 153, 225, 0.2),
            0 0 20px rgba(66, 153, 225, 0.4),
            0 0 40px rgba(66, 153, 225, 0.2);
    }
    50% {
        transform: scale(1.15) !important;
        box-shadow:
            0 0 0 6px rgba(66, 153, 225, 0.3),
            0 0 30px rgba(66, 153, 225, 0.5),
            0 0 50px rgba(66, 153, 225, 0.3);
    }
    100% {
        transform: scale(1.1) !important;
        box-shadow:
            0 0 0 4px rgba(66, 153, 225, 0.2),
            0 0 20px rgba(66, 153, 225, 0.4),
            0 0 40px rgba(66, 153, 225, 0.2);
    }
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

.vue-flow__node.highlight {
    opacity: 1 !important;
}

.vue-flow__edge {
    transition: all 0.3s ease;
}

.vue-flow__edge.active {
    stroke: #4299e1 !important;
    stroke-width: 3px !important;
    filter: drop-shadow(0 0 5px rgba(66, 153, 225, 0.5));
}

.vue-flow__edge.active path {
    stroke-dasharray: 5;
    animation: flowEdge 1s linear infinite;
}

@keyframes nodeProcessing {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

@keyframes nodeHighlight {
    0% {
        transform: scale(1.05);
        box-shadow:
            0 0 0 2px rgba(66, 153, 225, 0.3),
            0 0 15px 2px rgba(66, 153, 225, 0.5),
            0 0 30px 4px rgba(66, 153, 225, 0.3);
    }
    50% {
        transform: scale(1.1);
        box-shadow:
            0 0 0 3px rgba(66, 153, 225, 0.4),
            0 0 20px 3px rgba(66, 153, 225, 0.6),
            0 0 40px 6px rgba(66, 153, 225, 0.4);
    }
    100% {
        transform: scale(1.05);
        box-shadow:
            0 0 0 2px rgba(66, 153, 225, 0.3),
            0 0 15px 2px rgba(66, 153, 225, 0.5),
            0 0 30px 4px rgba(66, 153, 225, 0.3);
    }
}

@keyframes flowEdge {
    0% {
        stroke-dashoffset: 10;
    }
    100% {
        stroke-dashoffset: 0;
    }
}
</style>
