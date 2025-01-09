import { ClassicPreset } from 'rete';
const socket = new ClassicPreset.Socket('data');

export class EmailNode extends ClassicPreset.Node {
    constructor() {
        super('Email');

        this.addInput('input', new ClassicPreset.Input(socket));
        this.addOutput('output', new ClassicPreset.Output(socket));

        this.addControl('to', new ClassicPreset.InputControl('text'));
        this.addControl('subject', new ClassicPreset.InputControl('text'));
        this.addControl('body', new ClassicPreset.InputControl('textarea'));
        this.addControl('template', new ClassicPreset.InputControl('select', {
            initial: 'default',
            options: ['default', 'notification', 'report']
        }));
    }
}
