export default class DiagramService {
    static async fetchAll(params = {}) {
        try {
            const queryString = new URLSearchParams(params).toString();
            const url = `/api/witchcraft/diagrams${queryString ? `?${queryString}` : ''}`;

            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            // Parse JSON strings back to arrays for each diagram
            return data.map(diagram => {
                if (typeof diagram.nodes === 'string') {
                    diagram.nodes = JSON.parse(diagram.nodes);
                }
                if (typeof diagram.edges === 'string') {
                    diagram.edges = JSON.parse(diagram.edges);
                }
                return diagram;
            });
        } catch (error) {
            console.error('Error fetching diagrams:', error);
            throw error;
        }
    }

    static async fetch(id) {
        try {
            const response = await fetch(`/api/witchcraft/diagrams/${id}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            // Parse JSON strings back to arrays for frontend use
            if (typeof data.nodes === 'string') {
                data.nodes = JSON.parse(data.nodes);
            }
            if (typeof data.edges === 'string') {
                data.edges = JSON.parse(data.edges);
            }

            return data;
        } catch (error) {
            console.error('Error fetching diagram:', error);
            throw error;
        }
    }

    static async store(data) {
        try {
            const response = await fetch('/api/witchcraft/diagrams', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    name: data.name,
                    description: data.description,
                    category: data.category || 'General',
                    icon: data.icon || 'WorkflowIcon',
                    trigger_code: data.trigger_code || null,
                    nodes: JSON.stringify(data.nodes),
                    edges: JSON.stringify(data.edges)
                })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to save diagram');
            }

            const savedData = await response.json();
            return savedData;
        } catch (error) {
            console.error('Error storing diagram:', error);
            throw error;
        }
    }

    static async update(id, data) {
        try {
            const response = await fetch(`/api/witchcraft/diagrams/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    name: data.name,
                    description: data.description,
                    category: data.category || 'General',
                    icon: data.icon || 'WorkflowIcon',
                    trigger_code: data.trigger_code || null,
                    nodes: JSON.stringify(data.nodes),
                    edges: JSON.stringify(data.edges)
                })
            });

            if (!response.ok) {
                throw new Error('Failed to update diagram');
            }

            return response.json();
        } catch (error) {
            console.error('Update error:', error);
            throw error;
        }
    }

    static async destroy(id) {
        try {
            const response = await fetch(`/api/witchcraft/diagrams/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to delete diagram');
            }

            return true;
        } catch (error) {
            console.error('Error deleting diagram:', error);
            throw error;
        }
    }

    /**
     * Get available categories
     */
    static async getCategories() {
        try {
            const response = await fetch('/api/witchcraft/categories');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        } catch (error) {
            console.error('Error fetching categories:', error);
            throw error;
        }
    }

    /**
     * Execute flow by trigger code
     */
    static async executeByTriggerCode(triggerCode, data = {}) {
        try {
            const response = await fetch(`/api/witchcraft/execute-trigger/${triggerCode}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({ data })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'Failed to execute flow');
            }

            return response.json();
        } catch (error) {
            console.error('Error executing flow by trigger code:', error);
            throw error;
        }
    }

    /**
     * Get diagrams for selection dropdown
     */
    static async forSelection(params = {}) {
        try {
            const queryString = new URLSearchParams(params).toString();
            const url = `/api/witchcraft/diagrams-for-selection${queryString ? `?${queryString}` : ''}`;

            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return response.json();
        } catch (error) {
            console.error('Error fetching diagrams for selection:', error);
            throw error;
        }
    }

    /**
     * Get flow statistics
     */
    static async getStatistics() {
        try {
            const response = await fetch('/api/witchcraft/flow-statistics');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return response.json();
        } catch (error) {
            console.error('Error fetching flow statistics:', error);
            throw error;
        }
    }
}
