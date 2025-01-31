// services/SimulationService.js
export default class SimulationService {
    constructor() {
        this.variables = {};
        this.nodeStatuses = {};
        this.currentNodeId = null;
        this.simulationSpeed = 1000;
        this.onNodeStatusChange = null;
        this.onLogAdded = null;
        this.onVariablesChange = null;
    }

    setSimulationSpeed(speed) {
        this.simulationSpeed = speed;
    }

    reset() {
        this.variables = {};
        this.nodeStatuses = {};
        this.currentNodeId = null;
        if (this.onNodeStatusChange) {
            this.onNodeStatusChange(this.nodeStatuses, null);
        }
        if (this.onVariablesChange) {
            this.onVariablesChange({});
        }
    }

    async processFlow(nodes, edges) {
        try {
            this.reset(); // Reset before starting

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
            this.addLog(`Simulation failed: ${error.message}`, 'error');
            return false;
        } finally {
            // Don't reset immediately to show final state briefly
            setTimeout(() => {
                this.reset();
            }, 1000);
        }
    }

    async processNode(node, allNodes, edges) {
        try {
            // Clear any existing node highlights
            this.currentNodeId = node.id;
            this.nodeStatuses[node.id] = 'processing';
            this.updateNodeStatus(node.id, 'processing');

            // Add a small delay for visualization
            await this.delay(this.simulationSpeed);

            // Your existing node processing code...
            const response = await fetch('/api/process-node', {
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

            // Update variables with node output
            if (result.output) {
                Object.assign(this.variables, result.output);
                this.updateVariables();
            }

            this.addLog(result.message, result.success ? 'success' : 'warning');

            // Mark as completed after processing
            this.updateNodeStatus(node.id, 'completed');

            // Process next nodes...
            const outgoingEdges = edges.filter(edge => edge.source === node.id);
            for (const edge of outgoingEdges) {

                if (node.type === 'if') {
                    const shouldFollow = edge.sourceHandle === (result.conditionResult ? 'true' : 'false');
                    if (!shouldFollow) continue;
                } else if (node.type === 'switchcase') {
                    const selectedCase = result.selectedCase;
                    if (edge.sourceHandle !== String(selectedCase)) continue;
                }

                const nextNode = allNodes.find(n => n.id === edge.target);
                if (nextNode) {
                    await this.processNode(nextNode, allNodes, edges);
                }
            }

            return result;
        } catch (error) {
            this.updateNodeStatus(node.id, 'error');
            this.addLog(`Error in node ${node.id}: ${error.message}`, 'error');
            throw error;
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

    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}
