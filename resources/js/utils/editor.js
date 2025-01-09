import { NodeEditor, ClassicPreset,  } from "rete";
import { AreaPlugin, AreaExtensions } from "rete-area-plugin";
import {
  ConnectionPlugin,
  Presets as ConnectionPresets
} from "rete-connection-plugin";
import { VuePlugin, Presets } from "rete-vue-plugin";
import { APICallNode } from "../components/nodes/APICallNode";
import { ChatGPTNode } from "../components/nodes/ChatGPTNode";
import { EmailNode } from "../components/nodes/EmailNode";
import { DataflowEngine } from 'rete-engine';

export async function createEditor(container) {
  const socket = new ClassicPreset.Socket("socket");

  const editor = new NodeEditor();
  const area = new AreaPlugin(container);
  const connection = new ConnectionPlugin();
  const render = new VuePlugin();
  const engine = new DataflowEngine();

  AreaExtensions.selectableNodes(area, AreaExtensions.selector(), {
    accumulating: AreaExtensions.accumulateOnCtrl()
  });

  render.addPreset(Presets.classic.setup());

  connection.addPreset(ConnectionPresets.classic.setup());

  editor.use(area);
  area.use(connection);
  area.use(render);
  editor.use(engine);
   // Add a custom toJSON method
   editor.toJSON = () => {
    const nodes = Array.from(editor.getNodes()).map((node) => ({
      id: node.id,
      label: node.label,
      inputs: node.inputs,
      outputs: node.outputs,
      controls: node.controls,
    }));

    const connections = Array.from(editor.getConnections()).map((connection) => ({
      source: connection.source,
      target: connection.target,
      sourceOutput: connection.sourceOutput,
      targetInput: connection.targetInput
    }));

    return { nodes, connections };
  };

  AreaExtensions.simpleNodesOrder(area);

  AreaExtensions.zoomAt(area, editor.getNodes());

  return editor;
}
