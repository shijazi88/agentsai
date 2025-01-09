import { ClassicPreset } from 'rete';
const socket = new ClassicPreset.Socket('data');

export class ChatGPTNode extends ClassicPreset.Node {
    constructor() {
        super('ChatGPT');

        this.addInput('input', new ClassicPreset.Input(socket));
        this.addOutput('output', new ClassicPreset.Output(socket));

        this.addControl('prompt', new ClassicPreset.InputControl('textarea', {
            initial: 'Your prompt here...'
        }));
        this.addControl('model', new ClassicPreset.InputControl('select', {
            initial: 'gpt-3.5-turbo',
            options: ['gpt-3.5-turbo', 'gpt-4']
        }));
        this.addControl('temperature', new ClassicPreset.InputControl('number', {
            initial: 0.7,
            min: 0,
            max: 2,
            step: 0.1
        }));
    }
}
