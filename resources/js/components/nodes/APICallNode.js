import { ClassicPreset } from 'rete';
const socket = new ClassicPreset.Socket('data');

export class APICallNode extends ClassicPreset.Node {
    constructor() {
        super('API Call');

        this.addInput('input', new ClassicPreset.Input(socket));
        this.addOutput('output', new ClassicPreset.Output(socket));

        this.addControl('url', new ClassicPreset.InputControl('text', { initial: 'https://' }));
        this.addControl('method', new ClassicPreset.InputControl('select', {
            initial: 'GET',
            options: ['GET', 'POST', 'PUT', 'DELETE']
        }));
        this.addControl('headers', new ClassicPreset.InputControl('textarea', {
            initial: '{\n  "Content-Type": "application/json"\n}'
        }));
    }
}
