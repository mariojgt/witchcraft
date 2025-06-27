<template>
    <div v-if="show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-[#111] border border-gray-700 rounded-xl w-[900px] max-h-[90vh] overflow-hidden">
            <!-- Header -->
            <div class="p-6 border-b border-gray-800">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-semibold text-white">Simulation History</h2>
                        <p class="text-gray-400 text-sm mt-1">View past simulation runs and their details</p>
                    </div>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-white">
                        <XIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- Stats Summary -->
            <div v-if="stats" class="p-6 border-b border-gray-800">
                <div class="grid grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-400">{{ simulationRuns.length }}</div>
                        <div class="text-xs text-gray-400">Total Runs</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-400">{{ stats.successRate }}%</div>
                        <div class="text-xs text-gray-400">Success Rate</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-400">{{ formatDuration(stats.avgDuration) }}</div>
                        <div class="text-xs text-gray-400">Avg Duration</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-400">{{ stats.errorCount }}</div>
                        <div class="text-xs text-gray-400">Errors</div>
                    </div>
                </div>
            </div>

            <!-- Simulation Runs List -->
            <div class="p-6 max-h-96 overflow-y-auto">
                <div v-if="loading" class="text-center py-8">
                    <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full mx-auto"></div>
                    <p class="text-gray-400 mt-2">Loading simulation history...</p>
                </div>

                <div v-else-if="simulationRuns.length === 0" class="text-center py-8">
                    <PlayIcon class="w-12 h-12 text-gray-600 mx-auto mb-3" />
                    <p class="text-gray-400">No simulation runs found</p>
                    <p class="text-gray-500 text-sm">Run a simulation to see history here</p>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="run in simulationRuns"
                        :key="run.id"
                        class="group p-4 bg-[#1a1a1a] border border-gray-700 rounded-lg hover:border-gray-600 transition-all"
                        :class="{
                            'border-green-600/50': run.status === 'success',
                            'border-red-600/50': run.status === 'error',
                            'border-yellow-600/50': run.status === 'partial'
                        }"
                    >
                        <!-- Run Header -->
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                                     :class="{
                                         'bg-green-600/20 text-green-400': run.status === 'success',
                                         'bg-red-600/20 text-red-400': run.status === 'error',
                                         'bg-yellow-600/20 text-yellow-400': run.status === 'partial'
                                     }">
                                    <CheckCircleIcon v-if="run.status === 'success'" class="w-6 h-6" />
                                    <XCircleIcon v-else-if="run.status === 'error'" class="w-6 h-6" />
                                    <AlertCircleIcon v-else class="w-6 h-6" />
                                </div>
                                <div>
                                    <h3 class="font-medium text-white">
                                        Run #{{ run.id }}
                                        <span class="ml-2 px-2 py-0.5 text-xs rounded-full"
                                              :class="{
                                                  'bg-green-900/30 text-green-400': run.status === 'success',
                                                  'bg-red-900/30 text-red-400': run.status === 'error',
                                                  'bg-yellow-900/30 text-yellow-400': run.status === 'partial'
                                              }">
                                            {{ run.status.toUpperCase() }}
                                        </span>
                                    </h3>
                                    <p class="text-gray-400 text-sm">{{ formatDate(run.created_at) }}</p>
                                </div>
                            </div>

                            <div class="text-right">
                                <p class="text-white text-sm font-medium">{{ formatDuration(run.duration_ms) }}</p>
                                <p class="text-gray-400 text-xs">{{ run.completed_nodes }}/{{ run.total_nodes }} nodes</p>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mb-3">
                            <div class="w-full bg-gray-700 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all duration-300"
                                     :class="{
                                         'bg-green-500': run.status === 'success',
                                         'bg-red-500': run.status === 'error',
                                         'bg-yellow-500': run.status === 'partial'
                                     }"
                                     :style="{ width: `${(run.completed_nodes / run.total_nodes) * 100}%` }">
                                </div>
                            </div>
                        </div>

                        <!-- Error Message -->
                        <div v-if="run.error_message" class="mb-3 p-2 bg-red-900/20 border border-red-700/30 rounded text-red-300 text-xs">
                            {{ run.error_message }}
                        </div>

                        <!-- Final Variables -->
                        <div v-if="run.final_variables && Object.keys(run.final_variables).length > 0" class="mb-3">
                            <button
                                @click="toggleVariables(run.id)"
                                class="flex items-center gap-2 text-gray-400 hover:text-white text-xs"
                            >
                                <ChevronDownIcon
                                    class="w-4 h-4 transition-transform"
                                    :class="{ 'rotate-180': expandedRuns[run.id] }"
                                />
                                Final Variables ({{ Object.keys(run.final_variables).length }})
                            </button>

                            <div v-if="expandedRuns[run.id]" class="mt-2 space-y-1">
                                <div
                                    v-for="(value, key) in run.final_variables"
                                    :key="key"
                                    class="bg-gray-800 px-2 py-1 rounded text-xs"
                                >
                                    <span class="text-yellow-400">{{ key }}:</span>
                                    <span class="text-gray-200 ml-2">{{ formatValue(value) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Execution Log -->
                        <div class="flex justify-between items-center">
                            <button
                                @click="toggleExecutionLog(run.id)"
                                class="flex items-center gap-2 text-blue-400 hover:text-blue-300 text-xs font-medium"
                            >
                                <HistoryIcon class="w-4 h-4" />
                                View Execution Log
                            </button>

                            <div class="flex gap-2">
                                <button
                                    @click="downloadLog(run)"
                                    class="text-gray-400 hover:text-white text-xs"
                                    title="Download log"
                                >
                                    <DownloadIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Expanded Execution Log -->
                        <div v-if="expandedLogs[run.id]" class="mt-3 p-3 bg-black/30 rounded border border-gray-600">
                            <div class="max-h-48 overflow-y-auto space-y-1">
                                <div
                                    v-for="(log, index) in run.execution_log"
                                    :key="index"
                                    class="text-xs font-mono"
                                    :class="{
                                        'text-green-400': log.type === 'success',
                                        'text-red-400': log.type === 'error',
                                        'text-yellow-400': log.type === 'warning',
                                        'text-blue-400': log.type === 'info',
                                        'text-gray-300': log.type === 'default'
                                    }"
                                >
                                    <span class="text-gray-500">[{{ formatTime(log.timestamp) }}]</span>
                                    {{ log.message }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-gray-800 flex justify-between items-center">
                <div class="text-sm text-gray-400">
                    Showing {{ simulationRuns.length }} simulation runs
                </div>
                <div class="flex gap-3">
                    <button
                        @click="refreshHistory"
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
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import {
    XIcon, PlayIcon, CheckCircleIcon, XCircleIcon, AlertCircleIcon,
    ChevronDownIcon, HistoryIcon, DownloadIcon, RefreshCwIcon
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
const emit = defineEmits(['close']);

// State
const loading = ref(false);
const simulationRuns = ref([]);
const expandedRuns = ref({});
const expandedLogs = ref({});

// Computed
const stats = computed(() => {
    if (simulationRuns.value.length === 0) return null;

    const successful = simulationRuns.value.filter(run => run.status === 'success').length;
    const errorCount = simulationRuns.value.filter(run => run.status === 'error').length;
    const avgDuration = simulationRuns.value
        .filter(run => run.duration_ms)
        .reduce((sum, run) => sum + run.duration_ms, 0) / simulationRuns.value.length;

    return {
        successRate: Math.round((successful / simulationRuns.value.length) * 100),
        errorCount,
        avgDuration: avgDuration || 0
    };
});

// Methods
async function loadSimulationHistory() {
    if (!props.flowDiagramId) return;

    loading.value = true;
    try {
        const response = await fetch(`/api/witchcraft/diagrams/${props.flowDiagramId}/simulation-runs`);
        if (response.ok) {
            simulationRuns.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to load simulation history:', error);
    } finally {
        loading.value = false;
    }
}

function toggleVariables(runId) {
    expandedRuns.value[runId] = !expandedRuns.value[runId];
}

function toggleExecutionLog(runId) {
    expandedLogs.value[runId] = !expandedLogs.value[runId];
}

function refreshHistory() {
    loadSimulationHistory();
}

function downloadLog(run) {
    const logData = {
        runId: run.id,
        status: run.status,
        duration: run.duration_ms,
        nodes: `${run.completed_nodes}/${run.total_nodes}`,
        executionLog: run.execution_log,
        finalVariables: run.final_variables,
        timestamp: run.created_at
    };

    const blob = new Blob([JSON.stringify(logData, null, 2)], {
        type: 'application/json',
    });

    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `simulation-run-${run.id}.json`;
    a.click();
    URL.revokeObjectURL(url);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString();
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString();
}

function formatDuration(ms) {
    if (!ms) return '0ms';
    if (ms < 1000) return `${ms}ms`;
    return `${(ms / 1000).toFixed(1)}s`;
}

function formatValue(value) {
    if (typeof value === 'object') {
        return JSON.stringify(value, null, 2).substring(0, 100) + '...';
    }
    return String(value).substring(0, 100);
}

// Watch for dialog open/close
watch(() => props.show, (show) => {
    if (show) {
        loadSimulationHistory();
    } else {
        expandedRuns.value = {};
        expandedLogs.value = {};
    }
});
</script>
