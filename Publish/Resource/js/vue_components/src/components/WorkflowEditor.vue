<template>
    <div class="h-screen bg-[#0a0a0a] relative flex">
        <!-- Professional Sidebar with Node Library -->
        <div class="bg-[#111] border-r border-gray-800 flex flex-col relative"
             :style="{ width: sidebarWidth + 'px' }">
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
                                min="1"
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

                <!-- NEW: Variables Section -->
                <div v-if="availableVariables.length > 0 || variableSearchQuery.trim()" class="mt-6 pt-4 border-t border-gray-800">
                    <div class="text-xs text-gray-400 uppercase tracking-wide font-medium mb-3">
                        Variables ({{ availableVariables.length }})
                        <span v-if="variableSearchQuery.trim() && availableVariables.length > 0" class="text-purple-400"> - filtered</span>
                    </div>

                    <!-- Variable Search -->
                    <div class="relative mb-3">
                        <input
                            v-model="variableSearchQuery"
                            type="text"
                            placeholder="Search variables..."
                            class="w-full bg-[#1a1a1a] border border-gray-700 rounded-lg px-3 py-2 pl-9 text-white text-sm placeholder-gray-400 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 focus:outline-none transition-all"
                        />
                        <SearchIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <!-- Clear button -->
                        <button
                            v-if="variableSearchQuery"
                            @click="variableSearchQuery = ''"
                            class="absolute right-2 top-1/2 transform -translate-y-1/2 p-1 rounded hover:bg-gray-600 transition-colors"
                        >
                            <XIcon class="w-3 h-3 text-gray-400 hover:text-white" />
                        </button>
                    </div>

                    <div class="space-y-1 max-h-64 overflow-y-auto">
                        <div
                            v-for="variable in availableVariables"
                            :key="variable.id"
                            draggable="true"
                            @dragstart="onVariableDragStart($event, variable)"
                            class="group flex items-center gap-3 p-3 rounded-lg cursor-move hover:bg-[#1a1a1a] border border-transparent hover:border-purple-500/30 transition-all bg-gradient-to-r from-purple-500/5 to-transparent"
                            :title="`Drag to create Get Variable node for: ${variable.name}`"
                        >
                            <!-- Variable Icon -->
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-purple-500/20 border border-purple-500/30 group-hover:bg-purple-500/30 transition-colors">
                                <DownloadIcon class="w-4 h-4 text-purple-400 group-hover:text-purple-300" />
                            </div>

                            <!-- Variable Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <code class="text-sm font-medium text-purple-300 truncate">{{ variable.name }}</code>
                                    <span class="text-xs text-gray-500 bg-gray-700/50 px-1.5 py-0.5 rounded-full">var</span>
                                </div>
                                <div class="text-xs text-gray-400 truncate">{{ variable.description }}</div>
                                <div class="text-xs text-gray-500 truncate">From: {{ variable.nodeTitle }}</div>
                            </div>

                            <!-- Drag indicator -->
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                <PlusIcon class="w-4 h-4 text-gray-400" />
                            </div>
                        </div>

                        <!-- Empty state for search with no results -->
                        <div v-if="availableVariables.length === 0 && variableSearchQuery.trim() !== ''"
                             class="text-center py-8 text-gray-500">
                            <SearchIcon class="w-8 h-8 mx-auto mb-2 text-gray-600" />
                            <div class="text-sm">No variables found</div>
                            <div class="text-xs text-gray-600 mt-1">Try a different search term</div>
                        </div>

                        <!-- Helper text -->
                        <div v-if="availableVariables.length > 0" class="text-xs text-gray-500 text-center py-2 border-t border-gray-700/30 mt-2">
                            💡 Drag variables to canvas to create Get Variable nodes
                        </div>
                    </div>
                </div>

                <!-- DEBUG: Show debug info (remove this later) -->
                <div class="mt-6 pt-4 border-t border-gray-800">
                    <div class="text-xs text-gray-400 uppercase tracking-wide font-medium mb-3">Debug Info</div>
                    <div class="text-xs text-gray-500 space-y-1">
                        <div>Total nodes: {{ nodes.length }}</div>
                        <div>Available variables: {{ availableVariables.length }}</div>
                        <div>Node types: {{ nodes.map(n => n.type).join(', ') }}</div>
                        <div>Available components: {{ Object.keys(nodeComponents).join(', ') }}</div>
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

            <!-- Resize Handle -->
            <div
                @mousedown="startResize"
                class="absolute top-0 right-0 w-1 h-full cursor-col-resize hover:bg-blue-500 transition-colors bg-transparent group"
                :class="{ 'bg-blue-500': isResizing }"
            >
                <div class="absolute inset-y-0 -right-1 -left-1 w-3"></div>
            </div>
        </div>

        <!-- Main Canvas Area -->
        <div class="flex flex-col"
             :style="{ width: `calc(100vw - ${sidebarWidth}px)` }">
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
                        @click="showVersionHistory = true"
                        :disabled="!currentDiagramId"
                        class="p-2 rounded-lg hover:bg-[#1a1a1a] text-gray-400 hover:text-white disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        title="Version History"
                    >
                        <HistoryIcon class="w-4 h-4" />
                    </button>

                    <button
                        @click="showSimulationHistory = true"
                        :disabled="!currentDiagramId"
                        class="p-2 rounded-lg hover:bg-[#1a1a1a] text-gray-400 hover:text-white disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        title="Simulation History"
                    >
                        <ClockIcon class="w-4 h-4" />
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
                    <span v-if="currentDiagramData.name" class="text-blue-400">{{ currentDiagramData.name }}</span>
                </div>

                <!-- Right Actions with Enhanced Simulation Controls -->
                <div class="flex items-center gap-2">
                    <button
                        @click="showSidebar = !showSidebar"
                        class="px-3 py-1.5 text-sm bg-[#1a1a1a] hover:bg-gray-700 text-gray-300 hover:text-white rounded-lg transition-colors"
                    >
                        <FolderIcon class="w-4 h-4 inline mr-1" />
                        Files
                    </button>

                    <!-- Enhanced Simulation Controls -->
                    <div class="flex items-center gap-1">
                        <!-- Run Button (show only when not running) -->
                        <button
                            v-if="!pauseState.isRunning"
                            @click="startSimulation"
                            :disabled="nodes.length === 0"
                            class="px-4 py-1.5 text-sm bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed font-medium"
                        >
                            <PlayIcon class="w-4 h-4 inline mr-1" />
                            Run
                        </button>

                        <!-- Pause Button (show when running and not paused) -->
                        <button
                            v-if="pauseState.isRunning && !pauseState.isPaused"
                            @click="pauseSimulation"
                            class="px-4 py-1.5 text-sm bg-gradient-to-r from-yellow-600 to-orange-600 hover:from-yellow-500 hover:to-orange-500 text-white rounded-lg transition-all font-medium"
                        >
                            <PauseIcon class="w-4 h-4 inline mr-1" />
                            Pause
                        </button>

                        <!-- Resume Button (show when paused) -->
                        <button
                            v-if="pauseState.isRunning && pauseState.isPaused"
                            @click="resumeSimulation"
                            class="px-4 py-1.5 text-sm bg-gradient-to-r from-green-600 to-blue-600 hover:from-green-500 hover:to-blue-500 text-white rounded-lg transition-all font-medium"
                        >
                            <PlayIcon class="w-4 h-4 inline mr-1" />
                            Resume
                        </button>

                        <!-- Stop Button (show when running) -->
                        <button
                            v-if="pauseState.isRunning"
                            @click="stopSimulation"
                            class="px-3 py-1.5 text-sm bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 text-white rounded-lg transition-all font-medium"
                        >
                            <OctagonMinus class="w-4 h-4 inline mr-1" />
                            Stop
                        </button>
                    </div>

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
                            <button @click="openSaveDialog(); showExportMenu = false" class="w-full text-left px-3 py-2 hover:bg-gray-700 text-gray-300 hover:text-white transition-colors last:rounded-b-lg">
                                <SaveIcon class="w-4 h-4 inline mr-2" />
                                Save to Database
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Canvas -->
            <div class="flex-1 relative" @contextmenu.prevent>
                <VueFlow
                    v-model:nodes="nodes"
                    v-model:edges="edges"
            :class="{ 'simulation-mode': simulationState.isRunning, 'panning-right': isRightPanning }"
                    @connect="onConnect"
                    @node-click="onNodeClick"
                    @edge-click="onEdgeClick"
                    @node-double-click="onNodeDoubleClick"
                    @selection-change="onSelectionChange"
                    @node-drag-stop="onNodeDragStop"
                    @edges-change="onEdgesChange"
                    @nodes-change="onNodesChange"
                    @dragover.prevent
                    @drop="onDrop"
                    @pane-click="onPaneClick"

                    class="workflow-canvas"
                    :default-viewport="{ zoom: 0.8 }"
                    :min-zoom="0.1"
                    :max-zoom="2"
                    :pan-on-drag="false"
                    :select-nodes-on-drag="true"
                    :selection-on-drag="true"
                    :elements-selectable="true"
                    :nodes-draggable="true"
                    :selection-key-code="'Shift'"
                    :multi-selection-key-code="['Meta', 'Control', 'Shift']"
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
                                   :variables="simulationState.variables"
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
                            :variables="simulationState.variables"
                            :simulation-logs="simulationLogs"
                            :current-node-id="simulationState.currentNodeId"
                            @delete="() => removeNode(nodeProps.id)"
                        />
                    </template>
                </VueFlow>

                <!-- Enhanced Simulation Progress/Results with Pause Status -->
                <div v-if="simulationState.isRunning || simulationState.lastResult || pauseState.isRunning"
                     class="fixed bottom-4 right-4 bg-[#111] border border-gray-700 p-4 rounded-lg shadow-xl text-white z-50 min-w-[280px] max-w-[400px]">

                    <!-- Running State -->
                    <div v-if="pauseState.isRunning" class="flex items-center gap-2 mb-3">
                        <!-- Paused State -->
                        <div v-if="pauseState.isPaused" class="flex items-center gap-2">
                            <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                            <h3 class="text-sm font-semibold text-yellow-300">Simulation Paused</h3>
                        </div>
                        <!-- Running State -->
                        <div v-else class="flex items-center gap-2">
                            <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                            <h3 class="text-sm font-semibold">Simulation Running</h3>
                        </div>
                        <button @click="toggleSimulationDetails" class="ml-auto text-gray-400 hover:text-white">
                            <ChevronDownIcon :class="`w-4 h-4 transition-transform ${showSimulationDetails ? 'rotate-180' : ''}`" />
                        </button>
                    </div>

                    <!-- Completed State -->
                    <div v-else-if="simulationState.lastResult" class="flex items-center gap-2 mb-3">
                        <div class="w-2 h-2 rounded-full"
                             :class="{
                                 'bg-green-500': simulationState.lastResult.success,
                                 'bg-red-500': !simulationState.lastResult.success
                             }"></div>
                        <h3 class="text-sm font-semibold">
                            Simulation {{ simulationState.lastResult.success ? 'Completed' : 'Failed' }}
                        </h3>
                        <div class="ml-auto flex items-center gap-1">
                            <button @click="toggleSimulationDetails" class="text-gray-400 hover:text-white">
                                <ChevronDownIcon :class="`w-4 h-4 transition-transform ${showSimulationDetails ? 'rotate-180' : ''}`" />
                            </button>
                            <button @click="clearSimulationResults" class="text-gray-400 hover:text-white">
                                <XIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Current Node Info (Running or Paused) -->
                    <div v-if="pauseState.isRunning && currentNodeInfo" class="mb-3 p-2 border rounded"
                         :class="{
                             'bg-yellow-900/20 border-yellow-700/30': pauseState.isPaused,
                             'bg-blue-900/20 border-blue-700/30': !pauseState.isPaused
                         }">
                        <div class="flex items-center gap-2 mb-1">
                            <component :is="getNodeIcon(currentNodeInfo.type)" class="w-4 h-4"
                                       :class="{
                                           'text-yellow-400': pauseState.isPaused,
                                           'text-blue-400': !pauseState.isPaused
                                       }" />
                            <span class="text-sm font-medium"
                                  :class="{
                                      'text-yellow-300': pauseState.isPaused,
                                      'text-blue-300': !pauseState.isPaused
                                  }">{{ currentNodeInfo.title || currentNodeInfo.type }}</span>
                        </div>
                        <div class="text-xs text-gray-300">
                            {{ pauseState.isPaused ? 'Paused at: ' : '' }}{{ currentNodeInfo.description || 'Processing...' }}
                        </div>
                        <!-- currentNodeInfo add the for each loop check if a option and create a json scruter -->
                        <div v-if="simulationState.variables" class="mt-2 text-xs text-gray-400 scrollbar max-h-32 overflow-y-auto">
                            <pre class="bg-gray-800 p-2 rounded">{{ JSON.stringify(simulationState.variables, null, 2) }}</pre>
                        </div>

                    </div>

                    <!-- Completion Summary (Completed) -->
                    <div v-else-if="simulationState.lastResult" class="mb-3 p-2 rounded"
                         :class="{
                             'bg-green-900/20 border border-green-700/30': simulationState.lastResult.success,
                             'bg-red-900/20 border border-red-700/30': !simulationState.lastResult.success
                         }">
                        <div class="flex items-center gap-2 mb-1">
                            <CheckCircleIcon v-if="simulationState.lastResult.success" class="w-4 h-4 text-green-400" />
                            <XCircleIcon v-else class="w-4 h-4 text-red-400" />
                            <span class="text-sm font-medium"
                                  :class="{
                                      'text-green-300': simulationState.lastResult.success,
                                      'text-red-300': !simulationState.lastResult.success
                                  }">
                                {{ simulationState.lastResult.summary }}
                            </span>
                        </div>
                        <div class="text-xs text-gray-300">
                            Duration: {{ simulationState.lastResult.duration }}ms |
                            {{ simulationState.lastResult.completedNodes }}/{{ simulationState.lastResult.totalNodes }} nodes
                        </div>
                    </div>

                    <!-- Progress Info -->
                    <div class="space-y-2 text-sm">
                        <div v-if="pauseState.isRunning" class="flex justify-between">
                            <span class="text-gray-400">Progress:</span>
                            <span class="text-green-400 font-medium">{{ completedCount }} / {{ nodes.length }}</span>
                        </div>

                        <div v-if="pauseState.isRunning" class="w-full bg-gray-700 rounded-full h-2">
                            <div class="bg-gradient-to-r from-blue-500 to-green-500 h-2 rounded-full transition-all duration-500"
                                 :style="{ width: `${progressPercentage}%` }"></div>
                        </div>

                        <!-- Final Results Summary -->
                        <div v-else-if="simulationState.lastResult && showSimulationDetails" class="space-y-2">
                            <div class="text-xs text-gray-400">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>Success: {{ simulationState.lastResult.nodeResults.success || 0 }}</div>
                                    <div>Failed: {{ simulationState.lastResult.nodeResults.failed || 0 }}</div>
                                    <div>Variables: {{ Object.keys(simulationState.lastResult.finalVariables || {}).length }}</div>
                                    <div>Outputs: {{ Object.keys(simulationState.lastResult.outputs || {}).length }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed View -->
                        <div v-if="showSimulationDetails" class="mt-3 space-y-2">
                            <!-- Final Variables (Completed) or Current Variables (Running) -->
                            <div v-if="(pauseState.isRunning && Object.keys(simulationState.variables).length > 0) ||
                                      (!pauseState.isRunning && simulationState.lastResult?.finalVariables)">
                                <h4 class="text-xs font-semibold text-gray-300 mb-1">
                                    {{ pauseState.isRunning ? 'Recent Activity:' : 'Execution Log:' }}
                                </h4>
                                <div class="space-y-1 max-h-32 overflow-y-auto">
                                    <div v-for="log in (pauseState.isRunning ? recentLogs : simulationState.lastResult?.logs?.slice(-8) || [])"
                                         :key="log.id || log.timestamp"
                                         class="text-xs p-1 rounded"
                                         :class="{
                                             'text-green-300': log.type === 'success',
                                             'text-red-300': log.type === 'error',
                                             'text-yellow-300': log.type === 'warning',
                                             'text-blue-300': log.type === 'info',
                                             'text-gray-300': log.type === 'default'
                                         }">
                                        {{ log.message }}
                                    </div>
                                </div>
                            </div>

                            <!-- Node Status Summary (Running) -->
                            <div v-if="pauseState.isRunning && nodeStatusSummary">
                                <h4 class="text-xs font-semibold text-gray-300 mb-1">Node Status:</h4>
                                <div class="grid grid-cols-3 gap-1 text-xs">
                                    <div class="text-green-400">✓ {{ nodeStatusSummary.completed }}</div>
                                    <div class="text-blue-400">⏳ {{ nodeStatusSummary.processing }}</div>
                                    <div class="text-red-400">✗ {{ nodeStatusSummary.error }}</div>
                                </div>
                            </div>

                            <!-- Error Details (Failed) -->
                            <div v-if="!pauseState.isRunning && simulationState.lastResult && !simulationState.lastResult.success">
                                <h4 class="text-xs font-semibold text-red-300 mb-1">Error Details:</h4>
                                <div class="text-xs text-red-300 bg-red-900/20 p-2 rounded">
                                    {{ simulationState.lastResult.error || 'Unknown error occurred' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- File Management Modal -->
        <FileManagementModal
            :show="showSidebar"
            :diagrams="savedDiagrams"
            :loading="loadingDiagrams"
            @close="showSidebar = false"
            @load-diagram="loadDiagram"
            @delete-diagram="deleteDiagram"
            @create-new="createNewWorkflow"
        />

        <!-- Enhanced Save Modal -->
        <EnhancedSaveDialog
            :show="showSaveDialog"
            :initial-data="currentDiagramData"
            :is-update="!!currentDiagramId"
            :has-changes="hasUnsavedChanges"
            :current-version="currentDiagramData.version || 1"
            :available-icons="availableIcons"
            @close="showSaveDialog = false"
            @save="saveDiagram"
        />

        <!-- Version History Modal -->
        <VersionHistory
            :show="showVersionHistory"
            :flow-diagram-id="currentDiagramId"
            @close="showVersionHistory = false"
            @version-restored="handleVersionRestored"
        />

        <!-- Simulation History Modal -->
        <SimulationHistory
            :show="showSimulationHistory"
            :flow-diagram-id="currentDiagramId"
            @close="showSimulationHistory = false"
        />
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, markRaw, nextTick, onBeforeUnmount, watch, provide } from 'vue';
import { VueFlow, useVueFlow } from "@vue-flow/core";
import { Background } from "@vue-flow/background";
import * as LucideIcons from 'lucide-vue-next';
import {
    XIcon, PlusIcon, EditIcon, TrashIcon, SearchIcon, ChevronDownIcon,
    PlayIcon, DownloadIcon, UploadIcon, ListIcon, SaveIcon, FolderIcon,
    UndoIcon, RedoIcon, TextSelect, CopyIcon, ClipboardIcon, BoxIcon,
    GridIcon, HistoryIcon, ClockIcon, DatabaseIcon, GitBranchIcon,
    GlobeIcon, CpuIcon, BellIcon, FilterIcon, CheckCircleIcon, XCircleIcon,
    PauseIcon, OctagonMinus
} from 'lucide-vue-next';
import DiagramService from './services/DiagramService';
import SimulationService from './services/SimulationService';
import SimulationLogs from './SimulationLogs.vue';
import FileManagementModal from './FileManagementModal.vue';
import EnhancedSaveDialog from './EnhancedSaveDialog.vue';
import VersionHistory from './VersionHistory.vue';
import SimulationHistory from './SimulationHistory.vue';

// Core state
const nodes = ref([]);
const edges = ref([]);
const selectedNodes = ref([]);
const selectedEdges = ref([]);
const nodeComponents = reactive({});
const snapToGrid = ref(true);
const { addEdges, removeNodes, addNodes, removeEdges, project, fitView, toObject, setViewport } = useVueFlow();

// UI state
const showSaveDialog = ref(false);
const showSidebar = ref(false);
const showExportMenu = ref(false);
const showVersionHistory = ref(false);
const showSimulationHistory = ref(false);
const savedDiagrams = ref([]);
const currentDiagramId = ref(null);
const currentDiagramData = ref({});
const searchQuery = ref('');
const variableSearchQuery = ref('');
const searchInput = ref(null);
const availableIcons = ref({});
const hasUnsavedChanges = ref(false);

// Sidebar resizing functionality
const sidebarWidth = ref(288); // 18rem = 288px default (w-72)
const isResizing = ref(false);
const minSidebarWidth = 200;
const maxSidebarWidth = 600;

// Computed properties for responsive design
const sidebarClasses = computed(() => ({
    'text-sm': sidebarWidth.value < 250,
    'text-base': sidebarWidth.value >= 250
}));

const isCompactSidebar = computed(() => sidebarWidth.value < 250);

// Enhanced features state
const clipboard = ref([]);
const history = reactive({ past: [], future: [] });
const loadingDiagrams = ref(false);

const breakpoints = ref(new Set());

// Guard to avoid saving history while applying undo/redo
const isApplyingHistory = ref(false);

// Unreal-style right-button panning state
const isRightPanning = ref(false);
const panStart = ref({ x: 0, y: 0 });
const panViewportStart = ref({ x: 0, y: 0, zoom: 1 });

// Sidebar resize functionality
function startResize(event) {
    isResizing.value = true;
    event.preventDefault();
    document.addEventListener('mousemove', handleResize);
    document.addEventListener('mouseup', stopResize);
    document.body.style.cursor = 'col-resize';
    document.body.style.userSelect = 'none';
}

function handleResize(event) {
    if (!isResizing.value) return;

    const newWidth = Math.max(minSidebarWidth, Math.min(maxSidebarWidth, event.clientX));
    sidebarWidth.value = newWidth;
    // Save to localStorage
    localStorage.setItem('workflowEditor.sidebarWidth', newWidth.toString());
}

function stopResize() {
    isResizing.value = false;
    document.removeEventListener('mousemove', handleResize);
    document.removeEventListener('mouseup', stopResize);
    document.body.style.cursor = '';
    document.body.style.userSelect = '';
}

// Right-mouse drag panning (Unreal-style)
function onRootMouseDown(e) {
    // Only respond to right mouse button
    if (e.button !== 2) return;
    // If Shift is held, let custom selection handle it
    if (e.shiftKey) return;
    // Ignore if a modal/overlay might be active or default prevented elsewhere
    // Start panning
    e.preventDefault();
    const { viewport } = toObject();
    panViewportStart.value = { ...viewport };
    panStart.value = { x: e.clientX, y: e.clientY };
    isRightPanning.value = true;
    window.addEventListener('mousemove', onRootMouseMove);
    window.addEventListener('mouseup', onRootMouseUp, { once: true });
}

function onRootMouseMove(e) {
    if (!isRightPanning.value) return;
    const dx = e.clientX - panStart.value.x;
    const dy = e.clientY - panStart.value.y;
    setViewport({
        x: panViewportStart.value.x + dx,
        y: panViewportStart.value.y + dy,
        zoom: panViewportStart.value.zoom
    });
}

function onRootMouseUp() {
    isRightPanning.value = false;
    window.removeEventListener('mousemove', onRootMouseMove);
}

// Track changes for versioning
function markAsChanged() {
    hasUnsavedChanges.value = true;
}

// Watch for changes to nodes and edges
watch([nodes, edges], () => {
    if (currentDiagramId.value) {
        markAsChanged();
    }
}, { deep: true });

// Node library state
const nodeTypes = reactive([]);
const expandedCategories = reactive({});

// Simulation state
const showLogs = ref(true);
const simulationSpeed = ref(1000);
const simulationLogs = ref([]);
const nodeOutputs = reactive({});
const showSimulationDetails = ref(false);

// Payload display state
const expandedNodePayloads = ref({});
const expandedRawPayloads = ref({});
const nodePayloads = reactive({});

const simulationState = reactive({
    isRunning: false,
    currentNodeId: null,
    nodeStatuses: {},
    variables: {},
    lastResult: null
});

// Pause state
const pauseState = reactive({
    isPaused: false,
    isRunning: false
});

// Computed properties for simulation
const currentNodeInfo = computed(() => {
    if (!simulationState.currentNodeId) return null;

    const node = nodes.value.find(n => n.id === simulationState.currentNodeId);
    if (!node) return null;

    return {
        id: node.id,
        type: node.type,
        title: node.data?.customTitle || node.data?.label,
        description: node.data?.customDescription || getNodeDescription(node.type),
        data: node.data,
        lastPayload: nodePayloads[node.id]
    };
});

const recentLogs = computed(() => {
    return simulationLogs.value.slice(-5).reverse().map((log, index) => ({
        ...log,
        id: Date.now() + index
    }));
});

const nodeStatusSummary = computed(() => {
    const statuses = Object.values(simulationState.nodeStatuses);
    return {
        completed: statuses.filter(s => s === 'completed').length,
        processing: statuses.filter(s => s === 'processing').length,
        error: statuses.filter(s => s === 'error').length
    };
});

// Initialize Services
const simulationService = new SimulationService();

provide('simulationService', simulationService);

// Setup simulation service callbacks
simulationService.onNodeStatusChange = (statuses, currentNodeId) => {
    console.log('Status Update:', { statuses, currentNodeId });
    simulationState.nodeStatuses = { ...statuses };
    simulationState.currentNodeId = currentNodeId;

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

// Setup pause service callback
simulationService.onPauseStateChange = (isPaused, isRunning) => {
    pauseState.isPaused = isPaused;
    pauseState.isRunning = isRunning;
};

// Add callback for capturing node payload data
simulationService.onNodePayload = (nodeId, payload) => {
    nodePayloads[nodeId] = payload;
    console.log('Node payload captured:', { nodeId, payload });
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

// NEW: Extract variables from SetVariableNode instances
const availableVariables = computed(() => {
    console.log('Available Variables Debug:');
    console.log('Total nodes:', nodes.value.length);
    console.log('All node types:', nodes.value.map(n => ({ id: n.id, type: n.type, data: n.data })));

    const filtered = nodes.value.filter(node => {
        const isSetVariable = node.type === 'setvariablenode' ||
                             node.type === 'set-variable' ||
                             node.type === 'SetVariableNode' ||
                             node.type?.toLowerCase().includes('setvariable') ||
                             node.type?.toLowerCase().includes('variable');
        console.log(`Node ${node.id}: type="${node.type}", isSetVariable=${isSetVariable}`);
        return isSetVariable;
    });

    console.log('Filtered SetVariable nodes:', filtered.length);

    const mapped = filtered.map(node => ({
        id: node.id,
        name: node.data?.variableName || 'unnamed',
        displayName: node.data?.customTitle || node.data?.variableName || 'Unnamed Variable',
        description: node.data?.customDescription || `Variable: ${node.data?.variableName || 'unnamed'}`,
        nodeTitle: node.data?.customTitle || 'Set Variable',
        nodeId: node.id,
        type: 'variable',
        icon: 'VariableIcon',
        value: node.data?.value || node.data?.defaultValue || ''
    }));

    console.log('Mapped variables:', mapped);

    // Remove duplicates by variable name - keep the most recent (last) occurrence
    const uniqueVariables = mapped.reduce((acc, current) => {
        const existingIndex = acc.findIndex(item => item.name === current.name);
        if (existingIndex !== -1) {
            // Replace existing with current (keep most recent)
            acc[existingIndex] = current;
        } else {
            acc.push(current);
        }
        return acc;
    }, []);

    console.log('Unique variables after deduplication:', uniqueVariables);

    const finalFiltered = uniqueVariables.filter(variable => variable.name && variable.name.trim() !== '' && variable.name !== 'unnamed');
    console.log('Final filtered variables:', finalFiltered);

    // Apply search filter if there's a search query
    const searchFiltered = variableSearchQuery.value.trim() === ''
        ? finalFiltered
        : finalFiltered.filter(variable => {
            const query = variableSearchQuery.value.toLowerCase();
            return variable.name.toLowerCase().includes(query) ||
                   variable.displayName.toLowerCase().includes(query) ||
                   variable.description.toLowerCase().includes(query) ||
                   (variable.value && String(variable.value).toLowerCase().includes(query));
        });

    return searchFiltered.sort((a, b) => a.name.localeCompare(b.name));
})

function formatValue(value) {
    if (typeof value === 'object') {
        return JSON.stringify(value, null, 2).substring(0, 50) + '...';
    }
    return String(value).substring(0, 50);
}

// Payload display helper functions
function formatPayloadData(data) {
    if (!data) return 'No data';

    if (typeof data === 'object') {
        return JSON.stringify(data, null, 2);
    }
    return String(data);
}

function toggleNodePayload(nodeId) {
    expandedNodePayloads.value[nodeId] = !expandedNodePayloads.value[nodeId];
}

function toggleRawPayload(nodeId) {
    expandedRawPayloads.value[nodeId] = !expandedRawPayloads.value[nodeId];
}

function copyPayloadToClipboard(payload) {
    if (!payload) return;

    try {
        const jsonString = JSON.stringify(payload, null, 2);
        navigator.clipboard.writeText(jsonString).then(() => {
            // You could add a toast notification here
            console.log('Payload copied to clipboard');
        }).catch(err => {
            console.error('Failed to copy payload:', err);
            // Fallback method
            const textArea = document.createElement('textarea');
            textArea.value = jsonString;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        });
    } catch (error) {
        console.error('Error copying payload:', error);
    }
}

function copyVariablesToClipboard(variables) {
    if (!variables) return;

    try {
        const jsonString = JSON.stringify(variables, null, 2);
        navigator.clipboard.writeText(jsonString).then(() => {
            console.log('Variables copied to clipboard');
        }).catch(err => {
            console.error('Failed to copy variables:', err);
            // Fallback method
            const textArea = document.createElement('textarea');
            textArea.value = jsonString;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        });
    } catch (error) {
        console.error('Error copying variables:', error);
    }
}

function formatVariableValue(value) {
    if (value === null) return 'null';
    if (value === undefined) return 'undefined';
    if (typeof value === 'boolean') return value.toString();
    if (typeof value === 'number') return value.toString();
    if (typeof value === 'string') {
        // Truncate long strings but show full content for short ones
        return value.length > 100 ? value.substring(0, 100) + '...' : value;
    }
    if (typeof value === 'object') {
        // For objects/arrays, show a compact representation
        const jsonStr = JSON.stringify(value);
        return jsonStr.length > 100 ? jsonStr.substring(0, 100) + '...' : jsonStr;
    }
    return String(value);
}

// History management
function saveToHistory() {
    if (isApplyingHistory.value) return;

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
    isApplyingHistory.value = true;
    const current = { nodes: nodes.value, edges: edges.value };
    history.future.unshift(current);

    const previous = history.past.pop();
    nodes.value = previous.nodes;
    edges.value = previous.edges;
    isApplyingHistory.value = false;
}

function redo() {
    if (!canRedo.value) return;
    isApplyingHistory.value = true;
    const current = { nodes: nodes.value, edges: edges.value };
    history.past.push(current);

    const next = history.future.shift();
    nodes.value = next.nodes;
    edges.value = next.edges;
    isApplyingHistory.value = false;
}

// Push changes to history when dragging ends or graph changes
function onNodeDragStop() {
    saveToHistory();
}

function onEdgesChange(changes) {
    if (isApplyingHistory.value) return;
    // Only record meaningful changes (add/remove); ignore selection highlight changes
    if (Array.isArray(changes) && changes.some(c => c.type === 'add' || c.type === 'remove' || c.type === 'reset')) {
        saveToHistory();
    }
}

function onNodesChange(changes) {
    if (isApplyingHistory.value) return;
    if (!Array.isArray(changes)) return;
    // Record only add/remove here; position is handled on drag stop, selection/dimensions are ignored
    if (changes.some(c => c.type === 'add' || c.type === 'remove')) {
        saveToHistory();
    }
}

// Enhanced selection management
function onSelectionChange({ nodes: selectedNodeItems, edges: selectedEdgeItems }) {
    const selNodes = selectedNodeItems || [];
    const selEdges = selectedEdgeItems || [];

    selectedNodes.value = selNodes;
    selectedEdges.value = selEdges;

    nodes.value = nodes.value.map(node => ({
        ...node,
        selected: selNodes.some(selectedNode => selectedNode.id === node.id)
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
    const nodesToCopy = selectedNodes.value.length > 0
        ? selectedNodes.value
        : nodes.value.filter(node => node.selected);

    if (nodesToCopy.length === 0) {
        console.log('No nodes selected for copying');
        return;
    }

    clipboard.value = [];
    clipboard.value = nodesToCopy.map(node => ({
        ...JSON.parse(JSON.stringify(node))
    }));

    console.log(`Copied ${clipboard.value.length} nodes:`, clipboard.value.map(n => n.id));
}

function pasteNodes() {
    if (clipboard.value.length === 0) {
        console.log('Clipboard is empty');
        return;
    }

    saveToHistory();

    const timestamp = Date.now();
    const pastedNodes = clipboard.value.map((node, index) => ({
        ...JSON.parse(JSON.stringify(node)),
        id: `${node.type}-${timestamp}-${index}-${Math.random().toString(36).substr(2, 9)}`,
        position: {
            x: node.position.x + 50,
            y: node.position.y + 50
        },
        selected: true
    }));

    nodes.value = nodes.value.map(node => ({ ...node, selected: false }));
    addNodes(pastedNodes);
    selectedNodes.value = pastedNodes;

    console.log(`Pasted ${pastedNodes.length} nodes from clipboard:`, pastedNodes.map(n => n.id));
}

// Icon Helper Functions
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

    svg = svg.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
    svg = svg.replace(/\son\w+="[^"]*"/g, '');
    if (!svg.includes('class="')) {
        svg = svg.replace('<svg', '<svg class="w-5 h-5"');
    }
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

// NEW: Handle variable drag start - creates GetVariableNode with selected variable
function onVariableDragStart(event, variable) {
    const getVariableNodeData = {
        type: 'getvariable', // Fixed: should be 'getvariable' not 'getvariablenode'
        initialData: {
            variableName: variable.name,
            outputVariable: `${variable.name}_retrieved`,
            defaultValue: '',
            fromCache: false,
            failIfNotFound: false,
            customTitle: `Get ${variable.displayName}`,
            customDescription: `Retrieve ${variable.name} variable`,
            comment: `Getting variable from: ${variable.nodeTitle}`,
            colorTheme: "yellow"
        }
    }

    console.log('Variable drag started:', getVariableNodeData);
    event.dataTransfer.setData('application/nodeType', JSON.stringify(getVariableNodeData))
    event.dataTransfer.effectAllowed = 'copy'
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

function onDrop(event) {
    event.preventDefault();
    const nodeTypeData = event.dataTransfer.getData('application/nodeType');
    if (!nodeTypeData) return;

    console.log('Drop data received:', nodeTypeData);
    const { type, initialData } = JSON.parse(nodeTypeData);
    console.log('Parsed drop data - type:', type, 'initialData:', initialData);

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

    console.log('Creating new node:', newNode);

    nodes.value = nodes.value.map(node => ({ ...node, selected: false }));

    addNodes([newNode]);
    selectedNodes.value = [newNode];
}

// Enhanced node interaction
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

function onNodeDoubleClick(event) {
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

function getNodeClass(nodeProps) {
    const status = simulationState.nodeStatuses[nodeProps.id];
    const isCurrentNode = nodeProps.id === simulationState.currentNodeId;
    const hasBreakpointSet = hasBreakpoint(nodeProps.id);

    return {
        'simulation-node': true,
        'current': isCurrentNode,
        'processing': status === 'processing',
        'completed': status === 'completed',
        'error': status === 'error',
        'highlight': isCurrentNode && status === 'processing',
        'has-breakpoint': hasBreakpointSet // NEW: Add breakpoint class
    };
}

// Simulation functions
function toggleLogs() {
    showLogs.value = !showLogs.value;
}

function toggleSnapToGrid() {
    snapToGrid.value = !snapToGrid.value;
}

function toggleSimulationDetails() {
    showSimulationDetails.value = !showSimulationDetails.value;
}

function clearSimulationResults() {
    simulationState.lastResult = null;
    showSimulationDetails.value = false;
}

function getNodeIcon(nodeType) {
    const iconMap = {
        'model-lookup': DatabaseIcon,
        'condition': FilterIcon,
        'api-request': GlobeIcon,
        'artisan-command': CpuIcon,
        'notification': BellIcon,
        'variable': EditIcon,
        'json-extract': GitBranchIcon,
        'trigger': PlayIcon,
        'default': BoxIcon
    };

    return iconMap[nodeType] || iconMap.default;
}

function getNodeDescription(nodeType) {
    const descriptions = {
        'model-lookup': 'Fetching record from database',
        'condition': 'Evaluating condition logic',
        'api-request': 'Making HTTP request',
        'artisan-command': 'Executing Laravel command',
        'notification': 'Sending notification',
        'variable': 'Setting variable value',
        'json-extract': 'Extracting JSON data',
        'trigger': 'Triggering workflow',
        'default': 'Processing node'
    };

    return descriptions[nodeType] || descriptions.default;
}

function clearLogs() {
    simulationLogs.value = [];
}

function changeSimulationSpeed(newSpeed) {
    simulationSpeed.value = parseInt(newSpeed);
    simulationService.setSimulationSpeed(simulationSpeed.value);
}

// Pause control functions
function pauseSimulation() {
    simulationService.pause();
}

function resumeSimulation() {
    simulationService.resume();
}

function stopSimulation() {
    simulationService.stop();
}

function resetSimulationState() {
    const lastResult = {
        success: Object.values(simulationState.nodeStatuses).every(s => s === 'completed'),
        summary: `Processed ${completedCount.value}/${nodes.value.length} nodes`,
        duration: Date.now() - (simulationState.startTime || Date.now()),
        completedNodes: completedCount.value,
        totalNodes: nodes.value.length,
        finalVariables: { ...simulationState.variables },
        logs: [...simulationLogs.value],
        nodeResults: {
            success: Object.values(simulationState.nodeStatuses).filter(s => s === 'completed').length,
            failed: Object.values(simulationState.nodeStatuses).filter(s => s === 'error').length
        },
        outputs: { ...nodeOutputs },
        error: null
    };

    simulationState.lastResult = lastResult;
    simulationState.isRunning = false;
    simulationState.currentNodeId = null;
    simulationState.nodeStatuses = {};

    // Clear payload data
    Object.keys(nodePayloads).forEach(key => delete nodePayloads[key]);
    expandedNodePayloads.value = {};
    expandedRawPayloads.value = {};

    nodes.value = nodes.value.map(node => ({
        ...node,
        class: ''
    }));
}

// Workflow management functions
function createNewWorkflow() {
    if (nodes.value.length > 0 && !confirm('Create new workflow? Current work will be lost.')) {
        return;
    }

    nodes.value = [];
    edges.value = [];
    selectedNodes.value = [];
    selectedEdges.value = [];
    currentDiagramId.value = null;
    currentDiagramData.value = {};
    hasUnsavedChanges.value = false;
    showSidebar.value = false;

    history.past = [];
    history.future = [];

    console.log('New workflow created');
}

async function loadSavedDiagrams() {
    try {
        loadingDiagrams.value = true;
        savedDiagrams.value = await DiagramService.fetchAll();

        const iconsResponse = await fetch('/api/witchcraft/available-icons');
        if (iconsResponse.ok) {
            availableIcons.value = await iconsResponse.json();
        }
    } catch (error) {
        console.error('Failed to load diagrams:', error);
        alert('Failed to load saved diagrams');
    } finally {
        loadingDiagrams.value = false;
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
        currentDiagramData.value = {
            name: diagram.name,
            description: diagram.description || '',
            category: diagram.category || 'General',
            icon: diagram.icon || 'WorkflowIcon',
            trigger_code: diagram.trigger_code || '',
            is_deletable: diagram.is_deletable !== undefined ? diagram.is_deletable : true,
            version: diagram.version || 1
        };
        showSidebar.value = false;
        hasUnsavedChanges.value = false;

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

function openSaveDialog() {
    if (currentDiagramId.value && currentDiagramData.value.name) {
        showSaveDialog.value = true;
    } else {
        currentDiagramData.value = {
            name: '',
            description: '',
            category: 'General',
            icon: 'WorkflowIcon',
            trigger_code: '',
            is_deletable: true,
            version: 1
        };
        showSaveDialog.value = true;
    }
}

async function saveDiagram(formData) {
    try {
        const diagramData = {
            name: formData.name,
            description: formData.description,
            category: formData.category,
            icon: formData.icon,
            trigger_code: formData.trigger_code,
            is_deletable: formData.is_deletable,
            version_notes: formData.version_notes,
            nodes: nodes.value,
            edges: edges.value
        };

        if (currentDiagramId.value) {
            const updatedDiagram = await DiagramService.update(currentDiagramId.value, diagramData);
            currentDiagramId.value = updatedDiagram.id;
            alert('Workflow updated successfully!');
        } else {
            const newDiagram = await DiagramService.store(diagramData);
            currentDiagramId.value = newDiagram.id;
            alert('Workflow saved successfully!');
        }

        currentDiagramData.value = { ...formData, version: formData.version || 1 };
        hasUnsavedChanges.value = false;

        await loadSavedDiagrams();
        showSaveDialog.value = false;
    } catch (error) {
        console.error('Failed to save diagram:', error);
        alert(`Failed to ${currentDiagramId.value ? 'update' : 'save'} diagram`);
    }
}

async function handleVersionRestored(versionData) {
    try {
        nodes.value = versionData.nodes || [];
        edges.value = versionData.edges || [];
        selectedNodes.value = [];
        selectedEdges.value = [];

        currentDiagramData.value = {
            ...currentDiagramData.value,
            name: versionData.name,
            description: versionData.description,
            category: versionData.category || 'General',
            icon: versionData.icon || 'WorkflowIcon',
            is_deletable: versionData.is_deletable !== undefined ? versionData.is_deletable : true
        };

        hasUnsavedChanges.value = true;

        history.past = [];
        history.future = [];

        nextTick(() => {
            fitView({ duration: 800, padding: 0.2 });
        });

        const versionInfo = versionData.version_info;
        alert(`Version ${versionInfo.restored_from_version} loaded into editor. Make your changes and save to create a new version.`);

        console.log('Version data loaded for editing');
    } catch (error) {
        console.error('Failed to load version data:', error);
        alert('Failed to load version data');
    }
}

// Enhanced simulation with history tracking and pause support
async function startSimulation() {
    try {
        simulationState.lastResult = null;
        simulationLogs.value = [];
        resetSimulationState();

        simulationState.isRunning = true;
        simulationState.startTime = Date.now();
        simulationService.setSimulationSpeed(simulationSpeed.value);

        const startTime = Date.now();
        const workflowData = toObject();

        await simulationService.processFlow(workflowData.nodes, workflowData.edges);

        if (currentDiagramId.value && simulationService.isRunning) {
            const endTime = Date.now();
            const runData = {
                execution_log: simulationLogs.value,
                final_variables: simulationState.variables,
                status: 'success',
                total_nodes: workflowData.nodes.length,
                completed_nodes: completedCount.value,
                started_at: new Date().toISOString(),
                completed_at: new Date(endTime).toISOString(),
                duration_ms: 0
            };

            await fetch(`/api/witchcraft/diagrams/${currentDiagramId.value}/simulation-runs`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify(runData)
            });
        }
    } finally {
        setTimeout(() => {
            resetSimulationState();
        }, 1000);
    }
}

// Export/import functions
function exportFlow() {
    const flowData = {
        nodes: nodes.value,
        edges: edges.value,
        name: currentDiagramData.value.name || 'workflow',
        category: currentDiagramData.value.category || 'General',
        icon: currentDiagramData.value.icon || 'WorkflowIcon',
        trigger_code: currentDiagramData.value.trigger_code || '',
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
    a.download = `${currentDiagramData.value.name || 'workflow'}.json`;
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
            currentDiagramData.value = {
                name: data.name || '',
                description: data.description || '',
                category: data.category || 'General',
                icon: data.icon || 'WorkflowIcon',
                trigger_code: data.trigger_code || ''
            };
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

// Enhanced keyboard shortcuts
// ALSO UPDATE the keyboard shortcut handler:
function handleKeyDown(event) {
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
                copySelected();
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

        case 'a':
            if (isCmd) {
                event.preventDefault();
                selectAllNodes();
            }
            break;

        case 'z':
            if (isCmd && !event.shiftKey) {
                event.preventDefault();
                undo();
            } else if (isCmd && event.shiftKey) {
                event.preventDefault();
                redo();
            }
            break;

        case 'y':
            if (isCmd) {
                event.preventDefault();
                redo();
            }
            break;

        case 'k':
            if (isCmd) {
                event.preventDefault();
                if (searchInput.value) {
                    searchInput.value.focus();
                }
            }
            break;

        case ' ':
            if (pauseState.isRunning && !pauseState.isPaused) {
                event.preventDefault();
                pauseSimulation();
            } else if (pauseState.isRunning && pauseState.isPaused) {
                event.preventDefault();
                resumeSimulation();
            }
            break;

        case 'f9':
            event.preventDefault(); // Always prevent default for F9
            console.log('F9 pressed, selected nodes:', selectedNodes.value.length);

            if (selectedNodes.value.length === 1) {
                console.log('Toggling breakpoint for selected node:', selectedNodes.value[0].id);
                toggleBreakpoint(selectedNodes.value[0].id);
            } else if (selectedNodes.value.length > 1) {
                console.log('Multiple nodes selected, toggling breakpoints for all');
                selectedNodes.value.forEach(node => {
                    toggleBreakpoint(node.id);
                });
            } else {
                console.log('No nodes selected for F9 breakpoint toggle');
            }
            break;

        case 'escape':
            if (pauseState.isRunning) {
                event.preventDefault();
                stopSimulation();
            }
            break;
    }
}

simulationService.onBreakpointHit = (nodeId) => {
    console.log('Breakpoint hit at node:', nodeId);
    // Optional: Show notification or highlight the node
};

function toggleBreakpoint(nodeId) {
    console.log('Toggling breakpoint for node:', nodeId);

    const hasBreakpoint = simulationService.toggleBreakpoint(nodeId);
    console.log('Breakpoint result:', hasBreakpoint);

    if (hasBreakpoint) {
        breakpoints.value.add(nodeId);
    } else {
        breakpoints.value.delete(nodeId);
    }

    // Force reactivity update
    breakpoints.value = new Set(breakpoints.value);

    console.log('Current breakpoints:', Array.from(breakpoints.value));

    // Update node visual state immediately
    nodes.value = nodes.value.map(node => ({
        ...node,
        class: getNodeClass({ id: node.id })
    }));
}


function hasBreakpoint(nodeId) {
    return simulationService.hasBreakpoint(nodeId);
}

function handleClickOutside(event) {
    if (!event.target.closest('.relative')) {
        showExportMenu.value = false;
    }
}

// Remove custom selection in favor of Vue Flow's built-in selection box

// Ensure left-drag on pane makes a selection box, but not when starting on nodes/edges/handles
function handleFlowMouseDown(event) {
    // Only left button
    if (event.button !== 0) return;

    // Ignore when starting from a node, edge, or handle so node dragging/connections still work
    const target = event.target;
    const isNodeArea = target?.closest?.('.vue-flow__node');
    const isHandle = target?.closest?.('.vue-flow__handle');
    const isEdge = target?.closest?.('.vue-flow__edge');
    if (isNodeArea || isHandle || isEdge) return;

    // Start our custom selection box for pane left-drag
    event.preventDefault();
    customSelection.isSelecting = true;
    customSelection.startX = event.clientX;
    customSelection.startY = event.clientY;
    customSelection.currentX = event.clientX;
    customSelection.currentY = event.clientY;

    // Convert to flow coordinates
    customSelection.startPosition = project({ x: event.clientX, y: event.clientY });
    customSelection.currentPosition = { ...customSelection.startPosition };

    document.addEventListener('mousemove', updateCustomSelection);
    document.addEventListener('mouseup', endCustomSelection);
}

// Load node components
onMounted(async () => {
    try {
        // Restore saved sidebar width
        const savedWidth = localStorage.getItem('workflowEditor.sidebarWidth');
        if (savedWidth) {
            const width = parseInt(savedWidth, 10);
            if (width >= minSidebarWidth && width <= maxSidebarWidth) {
                sidebarWidth.value = width;
            }
        }

        const defaultNodeFiles = import.meta.glob('./nodes/*.vue', { eager: true });

        for (const path in defaultNodeFiles) {
            const fileName = path.split('/').pop().replace('.vue', '');
            const nodeType = fileName.toLowerCase().replace('node', '');
            const module = defaultNodeFiles[path];

            nodeComponents[nodeType] = markRaw(module.default);

            console.log(`Loaded node: ${fileName} -> ${nodeType}`); // Debug loaded node types

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

        const categories = [...new Set(nodeTypes.map(node => node.category))];
        categories.forEach(category => {
            expandedCategories[category] = true;
        });

        await loadSavedDiagrams();
        document.addEventListener('keydown', handleKeyDown);
        document.addEventListener('click', handleClickOutside);
        // Right-drag panning listener on the flow root
        const rootEl = document.querySelector('.workflow-canvas');
        if (rootEl) {
            rootEl.addEventListener('mousedown', onRootMouseDown); // no capture, right-button only
            // Context menu already prevented on wrapper via @contextmenu.prevent
        }
        saveToHistory();

        // Initialize breakpoint callback
        simulationService.onBreakpointHit = (nodeId) => {
            console.log('Breakpoint hit at node:', nodeId);
            // You can add visual feedback here if needed
        };

    } catch (error) {
        console.error('Failed to load components:', error);
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeyDown);
    document.removeEventListener('click', handleClickOutside);
    // Clean up resize listeners if still active
    document.removeEventListener('mousemove', handleResize);
    document.removeEventListener('mouseup', stopResize);
    // Clean up panning listeners
    const rootEl = document.querySelector('.workflow-canvas');
    if (rootEl) {
        rootEl.removeEventListener('mousedown', onRootMouseDown);
    }
    window.removeEventListener('mousemove', onRootMouseMove);
    document.body.style.cursor = '';
    document.body.style.userSelect = '';
});
</script>

<style scoped>
.workflow-canvas {
    background: #0a0a0a !important;
}

.workflow-canvas.panning-right {
    cursor: grabbing !important;
}

/* Resize handle styling */
.resize-handle {
    position: absolute;
    top: 0;
    right: 0;
    width: 4px;
    height: 100%;
    cursor: col-resize;
    background: transparent;
    transition: background-color 0.2s ease;
}

.resize-handle:hover {
    background: rgba(59, 130, 246, 0.5);
}

.resize-handle.active {
    background: rgba(59, 130, 246, 0.8);
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
    transform: translateZ(0) !important;
}

.vue-flow__node:hover {
    border-color: #6366f1 !important;
    transform: translateY(-1px) translateZ(0) !important;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(99, 102, 241, 0.3) !important;
}

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

.vue-flow__node.selected:not(:only-child) {
    border-color: #8b5cf6 !important;
    background: linear-gradient(145deg, #5b21b6, #7c3aed) !important;
    box-shadow:
        0 0 0 3px rgba(139, 92, 246, 0.4),
        0 0 20px rgba(139, 92, 246, 0.6),
        0 8px 24px rgba(0, 0, 0, 0.6) !important;
}

.vue-flow__node.dragging {
    transform: rotate(2deg) scale(1.05) translateZ(0) !important;
    box-shadow:
        0 12px 32px rgba(0, 0, 0, 0.7),
        0 0 0 2px rgba(59, 130, 246, 0.5) !important;
    z-index: 1000 !important;
}

/* Simulation states */
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
            0 0 30px rgba(66, 153, 225, 0.6),
            0 0 60px rgba(66, 153, 225, 0.3);
    }
}

@keyframes nodeProcessing {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

/* Enhanced selection box */
.vue-flow__selection {
    background: rgba(59, 130, 246, 0.15) !important;
    border: 2px solid #3b82f6 !important;
    border-radius: 8px !important;
    backdrop-filter: blur(4px) !important;
}

/* Enhanced edge styling */
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

/* Enhanced handle styling */
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

/* Using Vue Flow's built-in selection box styles */

.vue-flow__node.has-breakpoint::after {
    content: '';
    position: absolute;
    top: -5px;
    left: -5px;
    width: 10px;
    height: 10px;
    background: white;
    border-radius: 50%;
    z-index: 11;
}
</style>
