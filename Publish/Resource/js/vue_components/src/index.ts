// src/index.ts
import WorkflowEditor from './components/WorkflowEditor.vue';
import { WorkflowEngine } from './lib/workflowEngine';
import type { WorkflowNode } from './lib/nodeTypes';

// Register nodes
const nodeTypes = {
 if: 'if',
 api: 'api',
 notification: 'notification'
};

// Export components and types
export {
 WorkflowEditor,
 WorkflowEngine,
 nodeTypes
};

export type {
 WorkflowNode,
 IfNodeData,
 ApiNodeData,
 NotificationNodeData
} from './lib/nodeTypes';

// Plugin installation
export default {
 install: (app: any) => {
   app.component('WorkflowEditor', WorkflowEditor);
 }
};
