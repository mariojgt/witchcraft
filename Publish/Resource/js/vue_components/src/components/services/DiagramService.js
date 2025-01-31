// services/DiagramService.js
export default class DiagramService {
    static async fetchAll() {
        try {
            const response = await fetch('/api/flow-diagrams');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error fetching diagrams:', error);
            throw error;
        }
    }

    static async fetch(id) {
        try {
            const response = await fetch(`/api/flow-diagrams/${id}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error fetching diagram:', error);
            throw error;
        }
    }

    static async store(data) {
        try {
            const response = await fetch('/api/flow-diagrams', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    name: data.name,
                    description: data.description,
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
            const response = await fetch(`/api/flow-diagrams/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    name: data.name,
                    description: data.description,
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
            const response = await fetch(`/api/flow-diagrams/${id}`, {
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
}
