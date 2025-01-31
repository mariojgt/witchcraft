// src/lib/variableEvaluator.ts
export class VariableEvaluator {
    private variables: Map<string, any> = new Map();

    evaluate(expression: string): any {
      // Handle basic comparison operations
      if (expression.includes('==')) {
        const [left, right] = expression.split('==').map(s => s.trim());
        return this.resolveValue(left) === this.resolveValue(right);
      }

      if (expression.includes('>')) {
        const [left, right] = expression.split('>').map(s => s.trim());
        return this.resolveValue(left) > this.resolveValue(right);
      }

      if (expression.includes('<')) {
        const [left, right] = expression.split('<').map(s => s.trim());
        return this.resolveValue(left) < this.resolveValue(right);
      }

      // Resolve single variable or value
      return this.resolveValue(expression);
    }

    private resolveValue(value: string): any {
      // Check if it's a variable reference
      if (this.variables.has(value)) {
        return this.variables.get(value);
      }

      // Parse numbers
      if (!isNaN(Number(value))) {
        return Number(value);
      }

      // Handle string literals
      if (value.startsWith('"') || value.startsWith("'")) {
        return value.slice(1, -1);
      }

      return value;
    }

    setVariable(name: string, value: any): void {
      this.variables.set(name, value);
    }

    getVariable(name: string): any {
      return this.variables.get(name);
    }
   }
