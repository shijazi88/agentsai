import { defineStore } from 'pinia'
import axios from 'axios'

export const useFlowStore = defineStore('flow', {
  state: () => ({
    flows: [],
    currentFlow: null,
    executionResults: null,
    loading: false,
    error: null
  }),

  actions: {
    async saveFlow(flow) {
      try {
        const response = await axios.post('/api/flows', flow)
        this.flows.push(response.data)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to save flow'
        throw error
      }
    },

    async updateFlow(flow) {
        try {
          const response = await axios.put(`/api/flows/${flow.id}`, flow)
          const updatedFlowIndex = this.flows.findIndex((f) => f.id === flow.id)
          if (updatedFlowIndex !== -1) {
            this.flows[updatedFlowIndex] = response.data
          }
          return response.data
        } catch (error) {
          this.error = error.response?.data?.message || 'Failed to update flow'
          throw error
        }
    },

    async executeFlow(flow) {
      try {
        this.loading = true
        const response = await axios.post(`/api/flows/${flow.id}/execute`)
        this.executionResults = response.data
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Flow execution failed'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchFlows() {
      try {
          this.loading = true;
          const response = await axios.get('/api/flows');
          this.flows = response.data;
      } catch (error) {
          this.error = error.response?.data?.message || 'Failed to fetch flows';
          console.error(error);
      } finally {
          this.loading = false;
      }
    }
  }
})
