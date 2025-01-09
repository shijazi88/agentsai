<template>
  <div class="agent-flow-editor">
    <div class="toolbar bg-gray-100 p-4 flex gap-2">
      <button
        v-for="type in nodeTypes"
        :key="type.id"
        @click="addNode(type.id)"
        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
      >
        Add {{ type.label }}
      </button>
      <button
        @click="addNewFlow"
        class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600"
      >
        New Flow
      </button>
      <button
        @click="saveFlow"
        class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
      >
        Save Flow
      </button>
      <button
        @click="executeFlow"
        class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600"
      >
        Execute
      </button>
    </div>

    <div class="flex h-[calc(100vh-80px)]">
      <div class="saved-flows bg-gray-100 p-4 w-[250px] overflow-auto">
        <h2 class="text-xl mb-2">Saved Flows</h2>
        <ul>
            <li
                v-for="flow in flows"
                :key="flow.id"
                @click="loadFlow(flow)"
                :class="{
                'bg-gray-200': flow.id === currentFlow?.id,
                'bg-[#f7f7f7]': flow.id !== currentFlow?.id,
                }"
                class="cursor-pointer p-2 hover:bg-gray-200 rounded"
            >
                {{ flow.name + ' ' + flow.id }}
            </li>
        </ul>
      </div>

      <div ref="container" class="flow-container h-[100%] w-[100%] bg-gray-50"></div>

      <div v-if="executionResult" class="execution-result w-[500px] bg-gray-100 p-4 overflow-auto">
        <h2 class="text-lg font-bold">Execution Result:</h2>
        <pre class="whitespace-pre-wrap break-words">{{ executionResult }}</pre>
      </div>
    </div>

  </div>
</template>

<script setup>
  import { ref, onMounted, nextTick } from 'vue'
  import { useFlowStore } from '../store/flow'
  import { createEditor } from '../utils/editor'
  import { APICallNode } from './nodes/APICallNode'
  import { ChatGPTNode } from './nodes/ChatGPTNode'
  import { EmailNode } from './nodes/EmailNode'
  import { toast } from 'vue3-toastify';
  import 'vue3-toastify/dist/index.css';

  const container = ref(null)
  const flowStore = useFlowStore()
  const editor = ref(null)
  const flows = ref([])
  const currentFlow = ref(null)
  const executionResult = ref(null)

  const nodeTypes = [
    { id: 'api', label: 'API Call', class: APICallNode },
    { id: 'chatgpt', label: 'ChatGPT', class: ChatGPTNode },
    { id: 'email', label: 'Email', class: EmailNode },
  ]

  onMounted(async () => {
    await nextTick()
    if (container.value) {
      try {
        const editorInstance = await createEditor(container.value)
        editor.value = editorInstance
      } catch (error) {
        console.error('Error initializing editor:', error)
      }
    }
    await flowStore.fetchFlows()
    flows.value = flowStore.flows
  })

  const addNewFlow = () => {
    editor.value.clear();
    currentFlow.value = null;
    flowStore.currentFlow = null;
    executionResult.value = null;
  }

  const addNode = (type) => {
    const nodeType = nodeTypes.find((t) => t.id === type)
    if (nodeType) {
      const node = new nodeType.class()
      editor.value.addNode(node)
    }
  }

  const saveFlow = async () => {
    const flow = {
      id: currentFlow.value?.id || null,
      name: currentFlow.value?.name || `Flow`,
      nodes: editor.value.toJSON().nodes,
      connections: editor.value.toJSON().connections,
    }

    try {
      if (currentFlow.value?.id) {
        const updatedFlow = await flowStore.updateFlow(flow)
        flowStore.currentFlow = updatedFlow;
        toast(`${flow.name} is updated`, {
            autoClose: 1000,
        });
      } else {
        const savedFlow = await flowStore.saveFlow(flow)
        console.log(savedFlow);
        currentFlow.value = savedFlow;
        flowStore.currentFlow = savedFlow;
        toast(`${flow.name} is saved`, {
            autoClose: 1000,
        });
      }
    } catch (error) {
        console.log(error);
        toast.error(`Error saving flow`, {
            autoClose: 1000,
        });
    }
  }

  const executeFlow = async () => {
    await saveFlow();

    const flow = {
      id: currentFlow.value.id,
      nodes: editor.value.toJSON().nodes,
      connections: editor.value.toJSON().connections,
    }

    try {
      const result = await flowStore.executeFlow(flow)
      executionResult.value = result.results;
      console.log('Execution Results:', result)
    } catch (error) {
        console.log(error);
        toast.error(`Error executing flow`, {
            autoClose: 1000,
        });
    }
  }

  const loadFlow = async (flowData) => {
    try {
      editor.value.clear()
      currentFlow.value = flowData
        console.log(JSON.parse(JSON.stringify(currentFlow.value)).id);
      for (const nodeId in flowData.nodes) {
        const nodeData = flowData.nodes[nodeId]
        const nodeType = nodeTypes.find((t) => t.label === nodeData.label)

        if (nodeType) {
          const node = new nodeType.class()
          node.id = nodeData.id
          for (const [key, value] of Object.entries(nodeData.controls)) {
            node.controls[key]?.setValue(JSON.parse(JSON.stringify(value)).value)
          }
          await editor.value.addNode(node)
        }
      }

      for (const connection of flowData.connections) {
        editor.value.addConnection({
          source: connection.source,
          target: connection.target,
          sourceOutput: connection.sourceOutput,
          targetInput: connection.targetInput,
        })
      }

      flowStore.currentFlow = flowData;
      console.log('Flow loaded successfully')
    } catch (error) {
        console.log(error);
        toast.error(`Error loading flow`, {
            autoClose: 1000,
        });
    }
  }
  </script>
