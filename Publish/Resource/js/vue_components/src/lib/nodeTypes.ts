export interface WorkflowNode {
    id: string;
    type: string;
    data: any;
    position: { x: number; y: number };
  }

  export interface IfNodeData {
    variable: string;
    condition: string;
    value: any;
  }

  export interface ApiNodeData {
    url: string;
    method: string;
    headers: Record<string, string>;
    body: any;
  }

  export interface NotificationNodeData {
    message: string;
    type: 'success' | 'error' | 'warning' | 'info';
  }
