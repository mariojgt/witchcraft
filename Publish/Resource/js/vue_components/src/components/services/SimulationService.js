/**
 * SimulationService - Enhanced workflow execution service with proper node ordering
 *
 * Key improvements:
 * - Topological sorting for proper dependency order execution
 * - Parallel execution of nodes at the same dependency level
 * - Better error handling and validation
 * - Debugging utilities for execution plan visualization
 * - Enhanced conditional node support
 *
 * @version 2.0 - Improved with proper graph traversal
 */
export default class SimulationService {
    constructor() {
        this.variables = {};
        this.nodeStatuses = {};
        this.currentNodeId = null;
        this.simulationSpeed = 1000;
        this.onNodeStatusChange = null;
        this.onLogAdded = null;
        this.onVariablesChange = null;
        this.onNodePayload = null; // Callback for node payload data

        // Pause functionality
        this.isPaused = false;
        this.isRunning = false;
        this.pauseResolvers = [];
        this.onPauseStateChange = null;

        // Breakpoint functionality
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

    /**
     * Get a visual representation of the execution plan for debugging
     */
    getExecutionPlanSummary(nodes, edges) {
        const plan = this.buildExecutionPlan(nodes, edges);
        return plan.map((level, index) => ({
            level: index,
            nodes: level.map(node => ({
                id: node.id,
                type: node.type,
                label: node.data?.label || node.data?.customTitle || node.type
            }))
        }));
    }

    /**
     * Validate the workflow before execution
     */
    validateWorkflow(nodes, edges) {
        const issues = [];

        // Check for isolated nodes (no connections)
        const connectedNodes = new Set();
        edges.forEach(edge => {
            connectedNodes.add(edge.source);
            connectedNodes.add(edge.target);
        });

        const isolatedNodes = nodes.filter(node => !connectedNodes.has(node.id));
        if (isolatedNodes.length > 0) {
            issues.push({
                type: 'warning',
                message: `Found ${isolatedNodes.length} isolated node(s): ${isolatedNodes.map(n => n.id).join(', ')}`
            });
        }

        // Check for start nodes
        const startNodes = nodes.filter(
            node => !edges.some(edge => edge.target === node.id)
        );

        if (startNodes.length === 0) {
            issues.push({
                type: 'error',
                message: 'No start nodes found (nodes with no incoming connections)'
            });
        }

        // Check for cycles
        const executionPlan = this.buildExecutionPlan(nodes, edges);
        const processedCount = executionPlan.reduce((sum, level) => sum + level.length, 0);

        if (processedCount < nodes.length) {
            issues.push({
                type: 'error',
                message: 'Circular dependency detected - not all nodes can be processed'
            });
        }

        return issues;
    }

    async processFlow(nodes, edges) {
        try {
            this.reset(); // Reset before starting
            this.isRunning = true;
            this.isPaused = false;

            if (this.onPauseStateChange) {
                this.onPauseStateChange(this.isPaused, this.isRunning);
            }

            // Validate workflow before execution
            const validationIssues = this.validateWorkflow(nodes, edges);

            // Log validation issues
            validationIssues.forEach(issue => {
                this.addLog(issue.message, issue.type);
            });

            // Stop if there are critical errors
            const hasErrors = validationIssues.some(issue => issue.type === 'error');
            if (hasErrors) {
                this.addLog('Cannot start simulation due to workflow errors', 'error');
                return false;
            }

            // ✨ FIXED: Use sequential path-following execution for conditional workflows
            // Find start nodes (nodes with no incoming connections)
            const startNodes = nodes.filter(
                node => !edges.some(edge => edge.target === node.id)
            );

            if (startNodes.length === 0) {
                this.addLog('No start nodes found - cannot begin execution', 'error');
                return false;
            }

            this.addLog(`Starting simulation from ${startNodes.length} start node(s)...`, 'info');

            // Process each start node sequentially to follow conditional paths
            for (const startNode of startNodes) {
                await this.processNodeSequentially(startNode, nodes, edges);
            }

            this.addLog(`Simulation completed successfully`, 'success');
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

    /**
     * Build execution plan using topological sorting to ensure proper dependency order
     * Returns an array of arrays, where each sub-array contains nodes that can be executed in parallel
     */
    buildExecutionPlan(nodes, edges) {
        // Create adjacency list and in-degree count
        const adjacencyList = new Map();
        const inDegree = new Map();
        const nodeMap = new Map();

        // Initialize structures
        nodes.forEach(node => {
            adjacencyList.set(node.id, []);
            inDegree.set(node.id, 0);
            nodeMap.set(node.id, node);
        });

        // Build adjacency list and calculate in-degrees
        edges.forEach(edge => {
            if (adjacencyList.has(edge.source) && adjacencyList.has(edge.target)) {
                adjacencyList.get(edge.source).push(edge.target);
                inDegree.set(edge.target, inDegree.get(edge.target) + 1);
            }
        });

        const executionPlan = [];
        const processed = new Set();

        // Debug: Log the initial state
        this.addLog(`Building execution plan for ${nodes.length} nodes and ${edges.length} edges`, 'info');

        let levelCount = 0;
        while (processed.size < nodes.length) {
            // Find all nodes with no incoming dependencies (in-degree = 0)
            const currentLevel = [];

            for (const [nodeId, degree] of inDegree.entries()) {
                if (!processed.has(nodeId) && degree === 0) {
                    currentLevel.push(nodeMap.get(nodeId));
                    processed.add(nodeId);
                }
            }

            // If no nodes can be processed, we have a circular dependency
            if (currentLevel.length === 0) {
                this.addLog('Circular dependency detected in workflow', 'error');
                // Log remaining unprocessed nodes for debugging
                const remaining = nodes.filter(n => !processed.has(n.id));
                this.addLog(`Unprocessed nodes: ${remaining.map(n => `${n.id}(${n.type})`).join(', ')}`, 'error');
                break;
            }

            // Debug: Log current level
            this.addLog(`Level ${levelCount}: ${currentLevel.map(n => `${n.id}(${n.type})`).join(', ')}`, 'info');

            executionPlan.push(currentLevel);
            levelCount++;

            // Update in-degrees for the next iteration
            currentLevel.forEach(node => {
                adjacencyList.get(node.id).forEach(targetId => {
                    if (!processed.has(targetId)) {
                        inDegree.set(targetId, inDegree.get(targetId) - 1);
                    }
                });
            });
        }

        this.addLog(`Execution plan complete: ${executionPlan.length} levels`, 'info');
        return executionPlan;
    }

    /**
     * Determine if a conditional edge should be followed based on node result
     */
    shouldFollowEdge(edge, nodeResult) {
        // ✨ ENHANCED: Add detailed logging for switch case debugging
        const debugInfo = {
            edgeSourceHandle: edge.sourceHandle,
            nodeResultKeys: Object.keys(nodeResult),
            nodeOutputKeys: Object.keys(nodeResult.output || {})
        };

        // Handle different types of conditional results
        if ('conditionResult' in nodeResult) {
            // Boolean condition (if/else nodes)
            const result = edge.sourceHandle === (nodeResult.conditionResult ? 'true' : 'false');
            this.addLog(`🔀 Conditional edge check: handle="${edge.sourceHandle}", condition=${nodeResult.conditionResult}, following=${result}`, 'info');
            return result;
        }

        if ('conditionResult' in (nodeResult.output || {})) {
            // Boolean condition in output
            const result = edge.sourceHandle === (nodeResult.output.conditionResult ? 'true' : 'false');
            this.addLog(`🔀 Conditional edge check (output): handle="${edge.sourceHandle}", condition=${nodeResult.output.conditionResult}, following=${result}`, 'info');
            return result;
        }

        if ('selectedCase' in nodeResult) {
            // Switch/case nodes
            const selectedCase = nodeResult.selectedCase;
            const handleCase = parseInt(edge.sourceHandle);
            const result = handleCase === (selectedCase ?? null);
            this.addLog(`🔀 Switch case edge check: handle=${edge.sourceHandle} (${handleCase}), selectedCase=${selectedCase}, following=${result}`, 'info');
            return result;
        }

        if ('selectedCase' in (nodeResult.output || {})) {
            // Switch/case in output
            const selectedCase = nodeResult.output.selectedCase;
            const handleCase = parseInt(edge.sourceHandle);
            const result = handleCase === (selectedCase ?? null);
            this.addLog(`🔀 Switch case edge check (output): handle=${edge.sourceHandle} (${handleCase}), selectedCase=${selectedCase}, following=${result}`, 'info');
            return result;
        }

        // For non-conditional nodes, follow all edges
        this.addLog(`🔀 Non-conditional edge: following edge with handle="${edge.sourceHandle}"`, 'info');
        return true;
    }

    /**
     * ✨ NEW: Process nodes sequentially following conditional paths
     */
    async processNodeSequentially(node, allNodes, edges) {
        // Skip if already processed or if it's a comment node
        if (this.nodeStatuses[node.id] === 'completed' || this.isCommentNode(node)) {
            if (this.isCommentNode(node)) {
                this.addLog(`⏭️ Skipping comment node: ${node.id}`, 'info');
            }
            return null;
        }

        try {
            // Check for pause/stop before processing
            await this.checkPause();

            // Check for breakpoint BEFORE processing the node
            await this.checkBreakpoint(node.id);

            // Update node status
            this.currentNodeId = node.id;
            this.nodeStatuses[node.id] = 'processing';
            this.updateNodeStatus(node.id, 'processing');

            // Add visualization delay
            await this.delay(this.simulationSpeed);

            // Check for pause/stop before API call
            await this.checkPause();

            this.addLog(`🔄 Processing node: ${node.id} (${node.type})`, 'info');

            // Process the node
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
                throw new Error(`HTTP ${response.status}: Failed to process node`);
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

            // Mark as completed
            this.updateNodeStatus(node.id, 'completed');

            // ✨ CRITICAL: Process next nodes using conditional logic
            await this.processNextNodesConditionally(node, result, allNodes, edges);

            return result;
        } catch (error) {
            if (error.message === 'Simulation stopped') {
                throw error;
            }

            this.updateNodeStatus(node.id, 'error');
            this.addLog(`❌ Error in node ${node.id}: ${error.message}`, 'error');
            throw error;
        }
    }

    /**
     * ✨ NEW: Process next nodes using conditional logic for switch cases and IF nodes
     */
    async processNextNodesConditionally(node, result, allNodes, edges) {
        // Defensive guards
        if (!node) {
            this.addLog('processNextNodesConditionally: node is undefined/null – aborting', 'warning');
            return;
        }
        if (!Array.isArray(allNodes) || !Array.isArray(edges)) {
            this.addLog('processNextNodesConditionally: invalid graph structures', 'error');
            return;
        }
        // Normalise result to avoid runtime errors
        const safeResult = result && typeof result === 'object' ? result : {};

        const outgoingEdges = edges.filter(e => e.source === node.id);
        if (outgoingEdges.length === 0) {
            this.addLog(`🏁 End of path: no outgoing edges from ${node.id}`, 'info');
            return;
        }

        // Classification helpers
        const type = (node.type || '').toLowerCase();
        const isSwitch = type === 'switchcase' || type === 'switch' || type === 'switch_node';
        const isIf = type === 'ifcondition' || type === 'if' || type === 'condition';
        const isTriggerFlow = type === 'triggerflow' || type === 'triggerflownode';

        this.addLog(`🔀 Evaluating ${outgoingEdges.length} outgoing edge(s) from ${node.id} (${node.type})`, 'info');

        // Special fast-path: TriggerFlow should ALWAYS propagate to every connected next node
        if (isTriggerFlow) {
            this.addLog(`🚀 TriggerFlow node – propagating across all ${outgoingEdges.length} edge(s) unconditionally`, 'success');
            for (const edge of outgoingEdges) {
                await this.checkPause();
                const nextNode = allNodes.find(n => n.id === edge.target);
                if (nextNode) {
                    this.addLog(`➡️ (TriggerFlow) -> ${nextNode.id} (${nextNode.type})`, 'info');
                    await this.processNodeSequentially(nextNode, allNodes, edges);
                } else {
                    this.addLog(`⚠️ (TriggerFlow) target node not found: ${edge.target}`, 'warning');
                }
            }
            return; // Done
        }

        // Context logging for conditional nodes
        if (isSwitch) {
            const selectedCase = safeResult.selectedCase ?? safeResult.output?.selectedCase;
            this.addLog(`🎯 Switch node: selectedCase=${selectedCase}`, 'info');
        } else if (isIf) {
            const conditionResult = safeResult.conditionResult ?? safeResult.output?.conditionResult;
            this.addLog(`🎯 IF node: conditionResult=${conditionResult}`, 'info');
        }

        // For performance & safety keep a small visited tracker on this invocation to avoid duplicate immediate scheduling
        const scheduled = new Set();

        for (const edge of outgoingEdges) {
            await this.checkPause();

            let follow = true; // default for non-conditional types
            if (isSwitch || isIf) {
                try {
                    follow = this.shouldFollowEdge(edge, safeResult);
                } catch (e) {
                    follow = false;
                    this.addLog(`❌ Edge evaluation error (${edge.sourceHandle}) from ${node.id}: ${e.message}`, 'error');
                }
            }

            if (!follow) {
                this.addLog(`⏭️ Skip edge ${node.id}(${node.type}) -> ${edge.target} via handle '${edge.sourceHandle}'`, 'info');
                continue;
            }

            if (scheduled.has(edge.target)) {
                this.addLog(`🔁 Edge target ${edge.target} already scheduled – skipping duplicate`, 'warning');
                continue;
            }
            scheduled.add(edge.target);

            const nextNode = allNodes.find(n => n.id === edge.target);
            if (!nextNode) {
                this.addLog(`⚠️ Target node not found: ${edge.target}`, 'warning');
                continue;
            }
            this.addLog(`➡️ Following edge to: ${nextNode.id} (${nextNode.type}) [handle='${edge.sourceHandle ?? 'default'}']`, 'info');
            await this.processNodeSequentially(nextNode, allNodes, edges);
        }
    }

    /**
     * Helper method to check if a node is a comment/annotation node
     */
    isCommentNode(node) {
        if (!node) return false;

        // Check by node type
        if (node.type === 'comment' || node.type === 'Comment' ||
            node.type === 'sticker' || node.type === 'Sticker') {
            return true;
        }

        // Check by data properties that indicate comment nodes
        if (node.data) {
            if (node.data.customTitle === 'Comment' ||
                node.data.selectedEmoji ||
                node.data.isComment) {
                return true;
            }
        }

        return false;
    }

    /**
     * Process a single node without following edges (used in ordered execution)
     */
    async processNodeInOrder(node, allNodes, edges) {
        try {
            // Check for pause/stop before processing
            await this.checkPause();

            // Check for breakpoint BEFORE processing the node
            await this.checkBreakpoint(node.id);

            // Update node status
            this.currentNodeId = node.id;
            this.nodeStatuses[node.id] = 'processing';
            this.updateNodeStatus(node.id, 'processing');

            // Add visualization delay
            await this.delay(this.simulationSpeed);

            // Check for pause/stop before API call
            await this.checkPause();

            // Prepare node payload for debugging/monitoring
            const nodePayload = {
                nodeId: node.id,
                nodeType: node.type,
                nodeData: node.data,
                currentVariables: { ...this.variables },
                timestamp: new Date().toISOString()
            };

            // Notify about node payload if callback is set
            if (this.onNodePayload) {
                this.onNodePayload(node.id, nodePayload);
            }

            // Process the node
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
                throw new Error(`HTTP ${response.status}: Failed to process node`);
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

            // Mark as completed
            this.updateNodeStatus(node.id, 'completed');

            return result;
        } catch (error) {
            if (error.message === 'Simulation stopped') {
                throw error;
            }

            this.updateNodeStatus(node.id, 'error');
            throw error;
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

            // Process next nodes - this method is now deprecated in favor of ordered execution
            // but kept for backward compatibility
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

    /**
     * Legacy method for processing next nodes - kept for backward compatibility
     * Note: This is now replaced by the ordered execution plan approach
     */

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

            // ✨ ENHANCED: Handle switch case in output object
            if ('selectedCase' in (result.output || {})) {
                shouldFollow = parseInt(edge.sourceHandle) === (result.output.selectedCase ?? null);
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

    /**
     * Get current simulation statistics
     */
    getSimulationStats() {
        const statuses = Object.values(this.nodeStatuses);
        return {
            totalNodes: statuses.length,
            completed: statuses.filter(s => s === 'completed').length,
            processing: statuses.filter(s => s === 'processing').length,
            errors: statuses.filter(s => s === 'error').length,
            pending: statuses.filter(s => s === 'pending').length,
            isRunning: this.isRunning,
            isPaused: this.isPaused,
            currentNode: this.currentNodeId,
            breakpointsCount: this.breakpoints.size,
            variablesCount: Object.keys(this.variables).length
        };
    }

    /**
     * Enable debug mode for detailed console logging
     */
    enableDebugMode() {
        console.log('🔧 SimulationService Debug Mode Enabled');

        const originalLog = this.addLog;
        this.addLog = (message, type = 'info') => {
            console.log(`[SimulationService][${type.toUpperCase()}] ${message}`);
            originalLog.call(this, message, type);
        };

        // Log workflow structure when processing starts
        const originalProcessFlow = this.processFlow;
        this.processFlow = async (nodes, edges) => {
            console.group('🚀 Starting Workflow Simulation');
            console.log('Nodes:', nodes.map(n => ({ id: n.id, type: n.type })));
            console.log('Edges:', edges.map(e => ({ from: e.source, to: e.target, handle: e.sourceHandle })));

            const result = await originalProcessFlow.call(this, nodes, edges);

            console.log('Final Statistics:', this.getSimulationStats());
            console.groupEnd();

            return result;
        };
    }

    /**
     * ✨ NEW: Enable enhanced debugging specifically for conditional nodes (switch/if)
     */
    enableConditionalDebugMode() {
        console.log('🔀 Enhanced Conditional Node Debug Mode Enabled');

        // Override shouldFollowEdge to provide even more detailed logging
        const originalShouldFollowEdge = this.shouldFollowEdge;
        this.shouldFollowEdge = (edge, nodeResult) => {
            console.group(`🔀 Evaluating Edge: ${edge.source} → ${edge.target} (handle: ${edge.sourceHandle})`);

            console.log('Edge Details:', {
                source: edge.source,
                target: edge.target,
                sourceHandle: edge.sourceHandle,
                targetHandle: edge.targetHandle
            });

            console.log('Node Result:', nodeResult);
            console.log('Node Output:', nodeResult.output || {});

            const result = originalShouldFollowEdge.call(this, edge, nodeResult);

            console.log(`Decision: ${result ? '✅ FOLLOW' : '❌ SKIP'} this edge`);
            console.groupEnd();

            return result;
        };

        // Override processNextNodes to show conditional processing
        const originalProcessNextNodes = this.processNextNodes;
        this.processNextNodes = async (node, result, allNodes, edges) => {
            const outgoingEdges = edges.filter(edge => edge.source === node.id);

            if (outgoingEdges.length > 1) {
                console.group(`🔀 Processing ${outgoingEdges.length} outgoing edges from ${node.id} (${node.type})`);

                // Special handling for different node types
                if (node.type === 'SwitchCase' && 'selectedCase' in (result.output || result)) {
                    const selectedCase = result.selectedCase || result.output?.selectedCase;
                    console.log(`🎯 Switch Case Selected: ${selectedCase}`);
                    console.log('Available edges:', outgoingEdges.map(e => ({
                        handle: e.sourceHandle,
                        target: e.target,
                        willFollow: parseInt(e.sourceHandle) === selectedCase
                    })));
                }

                console.groupEnd();
            }

            return await originalProcessNextNodes.call(this, node, result, allNodes, edges);
        };
    }
}
