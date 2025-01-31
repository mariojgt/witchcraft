// src/lib/workflowEngine.ts
import { WorkflowNode } from './nodeTypes';

export class WorkflowEngine {
 private variables: Record<string, any> = {};

 async executeWorkflow(nodes: WorkflowNode[], startNodeId: string) {
   let currentNode = nodes.find(n => n.id === startNodeId);

   while (currentNode) {
     const result = await this.executeNode(currentNode);
     currentNode = this.getNextNode(nodes, currentNode, result);
   }
 }

 private async executeNode(node: WorkflowNode) {
   switch (node.type) {
     case 'if':
       return this.evaluateCondition(node.data);
     case 'api':
       return this.executeApiRequest(node.data);
     case 'notification':
       return this.showNotification(node.data);
   }
 }

 private evaluateCondition(data: any) {
   const value = this.variables[data.variable];
   switch (data.condition) {
     case 'equals':
       return value === data.value;
     case 'greater':
       return value > data.value;
     case 'less':
       return value < data.value;
   }
 }

 private async executeApiRequest(data: any) {
   try {
     const response = await fetch(data.url, {
       method: data.method,
       headers: data.headers,
       body: JSON.stringify(data.body)
     });
     return { success: response.ok, data: await response.json() };
   } catch (error) {
     return { success: false, error };
   }
 }

 private showNotification(data: any) {
   // Implement notification system integration
   console.log(`${data.type} notification: ${data.message}`);
   return true;
 }

 setVariable(name: string, value: any) {
   this.variables[name] = value;
 }
}
