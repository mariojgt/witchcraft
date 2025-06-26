export default class DiagramService {
    static async fetchAll() {
        try {
            const response = await fetch('/api/witchcraft/diagrams'); // Updated route
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
            const response = await fetch(`/api/witchcraft/diagrams/${id}`); // Updated route
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
            const response = await fetch('/api/witchcraft/diagrams', { // Updated route
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    name: data.name,
                    description: data.description,
                    nodes: JSON.stringify(data.nodes),     // Keep JSON.stringify to save as string
                    edges: JSON.stringify(data.edges)      // Keep JSON.stringify to save as string
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
            const response = await fetch(`/api/witchcraft/diagrams/${id}`, { // Updated route
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    name: data.name,
                    description: data.description,
                    nodes: JSON.stringify(data.nodes),     // Keep JSON.stringify to save as string
                    edges: JSON.stringify(data.edges)      // Keep JSON.stringify to save as string
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
            const response = await fetch(`/api/witchcraft/diagrams/${id}`, { // Updated route
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
