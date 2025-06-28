export default class SimulationService {
    constructor() {
        this.variables = {};
        this.nodeStatuses = {};
        this.currentNodeId = null;
        this.simulationSpeed = 1000;
        this.onNodeStatusChange = null;
        this.onLogAdded = null;
        this.onVariablesChange = null;

        // Pause functionality
        this.isPaused = false;
        this.isRunning = false;
        this.pauseResolvers = [];
        this.onPauseStateChange = null;

        // NEW: Breakpoint functionality
        this.breakpoints = new Set(); // Node IDs that have breakpoints
        this.onBreakpointHit = null; // Callback when breakpoint is hit
    }

    // NEW: Breakpoint management methods
    addBreakpoint(nodeId) {
        this.breakpoints.add(nodeId);
        this.addLog(`Breakpoint added to node: ${nodeId}`, 'info');
    }

    removeBreakpoint(nodeId) {
        this.breakpoints.delete(nodeId);
        this.addLog(`Breakpoint removed from node: ${nodeId}`, 'info');
    }

    toggleBreakpoint(nodeId) {
        if (this.breakpoints.has(nodeId)) {
            this.removeBreakpoint(nodeId);
            return false;
        } else {
            this.addBreakpoint(nodeId);
            return true;
        }
    }

    hasBreakpoint(nodeId) {
        return this.breakpoints.has(nodeId);
    }

    clearAllBreakpoints() {
        this.breakpoints.clear();
        this.addLog('All breakpoints cleared', 'info');
    }

    // Get all breakpoints (for debugging)
    getBreakpoints() {
        return Array.from(this.breakpoints);
    }

    // NEW: Check for breakpoint hit
    async checkBreakpoint(nodeId) {
        if (!this.isRunning) {
            throw new Error('Simulation stopped');
        }

        if (this.breakpoints.has(nodeId)) {
            this.addLog(`🔴 Breakpoint hit at node: ${nodeId}`, 'warning');

            // Automatically pause when breakpoint is hit
            this.isPaused = true;

            if (this.onPauseStateChange) {
                this.onPauseStateChange(this.isPaused, this.isRunning);
            }

            if (this.onBreakpointHit) {
                this.onBreakpointHit(nodeId);
            }

            // Wait for user to resume
            return new Promise(resolve => {
                this.pauseResolvers.push(resolve);
            });
        }
    }

    setSimulationSpeed(speed) {
        this.simulationSpeed = speed;
    }

    // Pause controls (unchanged)
    pause() {
        if (!this.isRunning) return;

        this.isPaused = true;
        this.addLog('Simulation paused', 'warning');

        if (this.onPauseStateChange) {
            this.onPauseStateChange(this.isPaused, this.isRunning);
        }
    }

    resume() {
        if (!this.isRunning || !this.isPaused) return;

        this.isPaused = false;
        this.addLog('Simulation resumed', 'info');

        // Resolve all pending pause promises
        this.pauseResolvers.forEach(resolve => resolve());
        this.pauseResolvers = [];

        if (this.onPauseStateChange) {
            this.onPauseStateChange(this.isPaused, this.isRunning);
        }
    }

    stop() {
        this.isRunning = false;
        this.isPaused = false;

        // Resolve all pending pause promises to allow cleanup
        this.pauseResolvers.forEach(resolve => resolve());
        this.pauseResolvers = [];

        this.addLog('Simulation stopped', 'warning');

        if (this.onPauseStateChange) {
            this.onPauseStateChange(this.isPaused, this.isRunning);
        }
    }

    // Helper method to check for pause (unchanged)
    async checkPause() {
        if (!this.isRunning) {
            throw new Error('Simulation stopped');
        }

        if (this.isPaused) {
            return new Promise(resolve => {
                this.pauseResolvers.push(resolve);
            });
        }
    }

    reset() {
        this.variables = {};
        this.nodeStatuses = {};
        this.currentNodeId = null;
        this.isRunning = false;
        this.isPaused = false;
        this.pauseResolvers = [];
        // NOTE: Don't clear breakpoints on reset - they should persist

        if (this.onNodeStatusChange) {
            this.onNodeStatusChange(this.nodeStatuses, null);
        }
        if (this.onVariablesChange) {
            this.onVariablesChange({});
        }
        if (this.onPauseStateChange) {
            this.onPauseStateChange(this.isPaused, this.isRunning);
        }
    }

    async processFlow(nodes, edges) {
        try {
            this.reset(); // Reset before starting
            this.isRunning = true;
            this.isPaused = false;

            if (this.onPauseStateChange) {
                this.onPauseStateChange(this.isPaused, this.isRunning);
            }

            // Find start nodes (nodes with no incoming edges)
            const startNodes = nodes.filter(
                node => !edges.some(edge => edge.target === node.id)
            );

            if (startNodes.length === 0) {
                this.addLog('No start nodes found', 'error');
                return false;
            }

            this.addLog('Starting simulation...', 'info');

            for (const startNode of startNodes) {
                await this.processNode(startNode, nodes, edges);
            }

            this.addLog('Simulation completed successfully', 'success');
            return true;
        } catch (error) {
            if (error.message === 'Simulation stopped') {
                this.addLog('Simulation was stopped', 'warning');
                return false;
            }

            this.addLog(`Simulation failed: ${error.message}`, 'error');
            return false;
        } finally {
            this.isRunning = false;
            if (this.onPauseStateChange) {
                this.onPauseStateChange(this.isPaused, this.isRunning);
            }

            // Don't reset immediately to show final state briefly
            setTimeout(() => {
                if (!this.isRunning) { // Only reset if still not running
                    this.reset();
                }
            }, 1000);
        }
    }

    async processNode(node, allNodes, edges) {
        try {
            // Check for pause/stop before processing
            await this.checkPause();

            // NEW: Check for breakpoint BEFORE processing the node
            await this.checkBreakpoint(node.id);

            // Clear any existing node highlights
            this.currentNodeId = node.id;
            this.nodeStatuses[node.id] = 'processing';
            this.updateNodeStatus(node.id, 'processing');

            // Check for pause/stop before delay
            await this.checkPause();

            // Add a small delay for visualization
            await this.delay(this.simulationSpeed);

            // Check for pause/stop before API call
            await this.checkPause();

            // Updated route for node processing
            const response = await fetch('/api/witchcraft/simulate-node', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    node: node,
                    variables: this.variables
                })
            });

            if (!response.ok) {
                throw new Error('Failed to process node');
            }

            const { result } = await response.json();

            // Check for pause/stop after API call
            await this.checkPause();

            // Update variables with node output
            if (result.output) {
                Object.assign(this.variables, result.output);
                this.updateVariables();
            }

            this.addLog(result.message, result.success ? 'success' : 'warning');

            // Mark as completed after processing
            this.updateNodeStatus(node.id, 'completed');

            // Check for pause/stop before processing next nodes
            await this.checkPause();

            // Process next nodes - fixed to match PHP logic
            await this.processNextNodes(node, result, allNodes, edges);

            return result;
        } catch (error) {
            if (error.message === 'Simulation stopped') {
                // Don't mark as error if simulation was intentionally stopped
                throw error;
            }

            this.updateNodeStatus(node.id, 'error');
            this.addLog(`Error in node ${node.id}: ${error.message}`, 'error');
            throw error;
        }
    }

    async processNextNodes(node, result, allNodes, edges) {
        const outgoingEdges = edges.filter(edge => edge.source === node.id);

        for (const edge of outgoingEdges) {
            // Check for pause/stop before processing each edge
            await this.checkPause();

            let shouldFollow = true;

            // Handle conditional nodes
            if ('conditionResult' in result) {
                shouldFollow = edge.sourceHandle === (result.conditionResult ? 'true' : 'false');
            }
            // Handle conditional cases for this object result.output.conditionResult
            if ('conditionResult' in result.output) {
                shouldFollow = edge.sourceHandle === (result.output.conditionResult ? 'true' : 'false');
            }

            if ('selectedCase' in result) {
                shouldFollow = parseInt(edge.sourceHandle) === (result.selectedCase ?? null);
            }

            if (shouldFollow) {
                const nextNode = allNodes.find(n => n.id === edge.target);
                if (nextNode) {
                    await this.processNode(nextNode, allNodes, edges);
                }
            }
        }
    }

    updateNodeStatus(nodeId, status) {
        this.nodeStatuses[nodeId] = status;
        if (status === 'processing') {
            this.currentNodeId = nodeId;
        } else if (status === 'completed' && this.currentNodeId === nodeId) {
            this.currentNodeId = null;
        }
        if (this.onNodeStatusChange) {
            this.onNodeStatusChange(this.nodeStatuses, this.currentNodeId);
        }
    }

    updateVariables() {
        if (this.onVariablesChange) {
            this.onVariablesChange(this.variables);
        }
    }

    addLog(message, type = 'info') {
        if (this.onLogAdded) {
            this.onLogAdded(message, type);
        }
    }

    async delay(ms) {
        // Split delay into smaller chunks to allow pausing during delays
        const chunkSize = 100; // Check every 100ms
        const chunks = Math.ceil(ms / chunkSize);

        for (let i = 0; i < chunks; i++) {
            await this.checkPause();
            const remainingTime = Math.min(chunkSize, ms - (i * chunkSize));
            await new Promise(resolve => setTimeout(resolve, remainingTime));
        }
    }
}
