<!-- src/views/purchasing/PRGenerationFromMaterialPlan.vue -->
<template>
  <div class="pr-generation">
    <!-- Header -->
    <div class="page-header">
      <h1 class="page-title">Generate Purchase Requisition</h1>
      <p class="page-description">Generate purchase requisition from material plans</p>
    </div>

    <!-- Step-by-Step Wizard -->
    <div class="wizard-container">
      <div class="wizard-steps">
        <div class="wizard-step" :class="{ active: currentStep >= 1, completed: currentStep > 1 }">
          <div class="step-number">1</div>
          <div class="step-label">Select Period</div>
        </div>
        <div class="wizard-connector" :class="{ active: currentStep > 1 }"></div>
        <div class="wizard-step" :class="{ active: currentStep >= 2, completed: currentStep > 2 }">
          <div class="step-number">2</div>
          <div class="step-label">Review Items</div>
        </div>
        <div class="wizard-connector" :class="{ active: currentStep > 2 }"></div>
        <div class="wizard-step" :class="{ active: currentStep >= 3 }">
          <div class="step-number">3</div>
          <div class="step-label">Configure PR</div>
        </div>
      </div>
    </div>

    <div class="form-card">
      <!-- Step 1: Select Period -->
      <div v-if="currentStep === 1" class="step-content">
        <h2 class="step-title">Select Planning Period</h2>
        <div class="form-section">
          <div class="form-group">
            <label>Planning Period <span class="required">*</span></label>
            <select v-model="formData.selectedPeriod" class="form-control" @change="fetchMaterialPlans" required>
              <option value="">Select planning period...</option>
              <option v-for="period in availablePeriods" :key="period" :value="period">
                {{ formatPeriod(period) }}
              </option>
            </select>
          </div>

          <!-- Summary Card -->
          <transition name="fade">
            <div v-if="formData.selectedPeriod && !isLoading" class="summary-card">
              <h3>Material Plans Summary</h3>
              <div class="summary-stats">
                <div class="stat-box">
                  <div class="stat-value">{{ summary.totalPlans }}</div>
                  <div class="stat-label">Total Plans</div>
                </div>
                <div class="stat-box">
                  <div class="stat-value">{{ summary.draftPlans }}</div>
                  <div class="stat-label">Draft Plans</div>
                </div>
                <div class="stat-box">
                  <div class="stat-value">{{ summary.materialPlans }}</div>
                  <div class="stat-label">Material Plans</div>
                </div>
              </div>
            </div>
          </transition>
        </div>

        <div class="form-actions">
          <button class="btn btn-outline" @click="cancel">Cancel</button>
          <button class="btn btn-primary" :disabled="!formData.selectedPeriod || isLoading" @click="nextStep">
            Next <i class="fas fa-arrow-right"></i>
          </button>
        </div>
      </div>

      <!-- Step 2: Review Items -->
      <div v-if="currentStep === 2" class="step-content">
        <h2 class="step-title">Review Material Plans</h2>
        
        <div v-if="isLoading" class="loading-state">
          <div class="spinner"></div>
          <p>Loading material plans...</p>
        </div>

        <template v-else-if="materialPlans.length > 0">
          <div class="table-controls">
            <input 
              type="text" 
              v-model="searchQuery" 
              class="search-input" 
              placeholder="Search items..."
            />
            <div class="bulk-actions">
              <button class="btn btn-outline" @click="selectAll">
                {{ isAllSelected ? 'Deselect All' : 'Select All' }}
              </button>
              <button class="btn btn-outline" @click="removeSelected" :disabled="selectedPlans.length === 0">
                Remove Selected
              </button>
            </div>
          </div>

          <div class="table-container">
            <table class="plans-table">
              <thead>
                <tr>
                  <th class="checkbox-column">
                    <input type="checkbox" @change="toggleSelectAll" :checked="isAllSelected">
                  </th>
                  <th>Item Code</th>
                  <th>Item Name</th>
                  <th>Type</th>
                  <th>Net Requirement</th>
                  <th>Planned Order</th>
                  <th>UOM</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="plan in filteredPlans" :key="plan.plan_id" :class="{ selected: isPlanSelected(plan) }">
                  <td>
                    <input type="checkbox" :checked="isPlanSelected(plan)" @change="togglePlanSelection(plan)">
                  </td>
                  <td class="item-code">{{ plan.item?.item_code }}</td>
                  <td>{{ plan.item?.name }}</td>
                  <td>
                    <span class="type-badge" :class="plan.material_type === 'FG' ? 'badge-primary' : 'badge-secondary'">
                      {{ materialTypeLabel(plan.material_type) }}
                    </span>
                  </td>
                  <td class="text-right">{{ formatNumber(plan.net_requirement) }}</td>
                  <td class="text-right">
                    <input 
                      type="number" 
                      v-model="planQuantities[plan.plan_id]" 
                      class="quantity-input"
                      min="0"
                      :max="plan.net_requirement"
                      step="0.01"
                    />
                  </td>
                  <td>{{ plan.item?.unit_of_measure?.symbol }}</td>
                  <td>
                    <button class="btn-edit" @click="editRequirement(plan)">
                      <i class="fas fa-edit"></i>
                    </button>
                  </td>
                </tr>
                <tr v-if="filteredPlans.length === 0">
                  <td colspan="8" class="no-data">No material plans found</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="selection-summary">
            <div class="summary-text">
              Selected: {{ selectedPlans.length }} of {{ materialPlans.length }} plans
            </div>
            <div class="summary-total">
              Total Requirement: {{ formatNumber(totalRequirement) }}
            </div>
          </div>
        </template>
        
        <div v-else class="no-plans-state">
          <i class="fas fa-info-circle"></i>
          <p>No material plans available for this period</p>
        </div>

        <div class="form-actions">
          <button class="btn btn-outline" @click="previousStep">
            <i class="fas fa-arrow-left"></i> Back
          </button>
          <button class="btn btn-primary" :disabled="selectedPlans.length === 0" @click="nextStep">
            Continue <i class="fas fa-arrow-right"></i>
          </button>
        </div>
      </div>

      <!-- Step 3: Configure PR -->
      <div v-if="currentStep === 3" class="step-content">
        <h2 class="step-title">Purchase Requisition Details</h2>
        
        <div class="form-section">
          <div class="form-row">
            <div class="form-group">
              <label>Planning Period</label>
              <input type="text" :value="formatPeriod(formData.selectedPeriod)" readonly class="form-control readonly">
            </div>
            <div class="form-group">
              <label>Lead Time (days) <span class="required">*</span></label>
              <input type="number" v-model="formData.leadTimeDays" class="form-control" min="0" required>
            </div>
          </div>

          <div class="form-group">
            <label>Required By Date</label>
            <input type="text" :value="computedRequiredDate" readonly class="form-control readonly">
            <small class="form-note">Date when materials are needed</small>
          </div>

          <div class="form-group">
            <label>Notes</label>
            <textarea v-model="formData.notes" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
          </div>
        </div>

        <!-- PR Preview -->
        <div class="pr-preview">
          <h3>Purchase Requisition Preview</h3>
          <div class="preview-content">
            <div class="preview-item">
              <span class="preview-label">Planning Period:</span>
              <span class="preview-value">{{ formatPeriod(formData.selectedPeriod) }}</span>
            </div>
            <div class="preview-item">
              <span class="preview-label">Required By:</span>
              <span class="preview-value">{{ computedRequiredDate }}</span>
            </div>
            <div class="preview-item">
              <span class="preview-label">Total Items:</span>
              <span class="preview-value">{{ selectedPlans.length }}</span>
            </div>
            <div class="preview-item">
              <span class="preview-label">Total Quantity:</span>
              <span class="preview-value">{{ formatNumber(totalRequirement) }}</span>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button class="btn btn-outline" @click="previousStep">
            <i class="fas fa-arrow-left"></i> Back
          </button>
          <button class="btn btn-primary" :disabled="isGenerating" @click="generatePR">
            <i class="fas fa-file-invoice" :class="{ 'fa-spin': isGenerating }"></i>
            {{ isGenerating ? 'Generating...' : 'Generate Purchase Requisition' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Success Modal -->
    <div v-if="showSuccess" class="modal">
      <div class="modal-backdrop" @click="closeSuccess"></div>
      <div class="modal-content modal-md">
        <div class="modal-header">
          <h2>Purchase Requisition Generated</h2>
          <button class="close-btn" @click="closeSuccess">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="success-content">
            <div class="success-icon">
              <i class="fas fa-check-circle"></i>
            </div>
            <h3>Success!</h3>
            <p>Purchase Requisition {{ prResult.pr_number }} has been created successfully.</p>
            
            <div class="pr-details">
              <div class="detail-item">
                <span class="detail-label">PR Number:</span>
                <span class="detail-value">{{ prResult.pr_number }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Total Items:</span>
                <span class="detail-value">{{ prResult.total_items }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Status:</span>
                <span class="detail-value">Draft</span>
              </div>
            </div>

            <div class="success-actions">
              <button class="btn btn-outline" @click="closeSuccess">Close</button>
              <button class="btn btn-primary" @click="viewPR">View PR</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'PRGenerationFromMaterialPlan',
  data() {
    return {
      currentStep: 1,
      formData: {
        selectedPeriod: '',
        leadTimeDays: 7,
        notes: ''
      },
      availablePeriods: [],
      materialPlans: [],
      selectedPlans: [],
      planQuantities: {},
      searchQuery: '',
      isLoading: false,
      isGenerating: false,
      showSuccess: false,
      prResult: {},
      summary: {
        totalPlans: 0,
        draftPlans: 0,
        materialPlans: 0
      }
    };
  },
  computed: {
    computedRequiredDate() {
      if (!this.formData.selectedPeriod || !this.formData.leadTimeDays) return '';
      const date = new Date(this.formData.selectedPeriod + '-01');
      date.setDate(date.getDate() + parseInt(this.formData.leadTimeDays));
      return date.toLocaleDateString();
    },
    
    filteredPlans() {
      if (!this.searchQuery) return this.materialPlans;
      const query = this.searchQuery.toLowerCase();
      return this.materialPlans.filter(plan => 
        plan.item?.item_code?.toLowerCase().includes(query) ||
        plan.item?.name?.toLowerCase().includes(query)
      );
    },
    
    isAllSelected() {
      return this.materialPlans.length > 0 && 
             this.materialPlans.every(plan => this.isPlanSelected(plan));
    },
    
    totalRequirement() {
      return this.selectedPlans.reduce((total, plan) => {
        return total + (this.planQuantities[plan.plan_id] || 0);
      }, 0);
    }
  },
  mounted() {
    this.initializePeriods();
    this.fetchAvailablePeriods();
  },
  methods: {
    initializePeriods() {
      const now = new Date();
      const currentPeriod = now.toISOString().slice(0, 7);
      this.formData.selectedPeriod = currentPeriod;
    },
    
    async fetchAvailablePeriods() {
      try {
        const response = await axios.get('/inventory/material-planning/generate-pr-by-period');
        this.availablePeriods = response.data.data || [];
      } catch (error) {
        console.error('Error fetching periods:', error);
      }
    },
    
    async fetchMaterialPlans() {
      if (!this.formData.selectedPeriod) return;
      
      this.isLoading = true;
      try {
        const response = await axios.get('/inventory/material-planning', {
          params: {
            planning_period: this.formData.selectedPeriod,
            material_type: 'RM',
            status: 'Draft',
            has_requirement: true
          }
        });
        
        this.materialPlans = response.data.data || [];
        this.planQuantities = {};
        
        // Initialize quantities
        this.materialPlans.forEach(plan => {
          this.planQuantities[plan.plan_id] = plan.net_requirement;
        });
        
        // Calculate summary
        this.summary = {
          totalPlans: this.materialPlans.length,
          draftPlans: this.materialPlans.filter(p => p.status === 'Draft').length,
          materialPlans: this.materialPlans.filter(p => p.material_type === 'RM').length
        };
        
      } catch (error) {
        console.error('Error fetching material plans:', error);
        alert('Failed to fetch material plans');
      } finally {
        this.isLoading = false;
      }
    },
    
    formatPeriod(period) {
      return period ? new Date(period + '-01').toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long' 
      }) : '';
    },
    
    formatNumber(value) {
      return Number(value).toLocaleString();
    },
    
    materialTypeLabel(type) {
      return {
        'FG': 'Finished Goods',
        'RM': 'Raw Materials'
      }[type] || type;
    },
    
    isPlanSelected(plan) {
      return this.selectedPlans.some(p => p.plan_id === plan.plan_id);
    },
    
    togglePlanSelection(plan) {
      const index = this.selectedPlans.findIndex(p => p.plan_id === plan.plan_id);
      if (index === -1) {
        this.selectedPlans.push(plan);
      } else {
        this.selectedPlans.splice(index, 1);
      }
    },
    
    toggleSelectAll() {
      if (this.isAllSelected) {
        this.selectedPlans = [];
      } else {
        this.selectedPlans = [...this.materialPlans];
      }
    },
    
    selectAll() {
      this.selectedPlans = [...this.materialPlans];
    },
    
    removeSelected() {
      this.selectedPlans = [];
    },
    
    editRequirement(plan) {
      // Open edit modal or inline editing
      console.log('Edit requirement for:', plan);
    },
    
    nextStep() {
      this.currentStep++;
    },
    
    previousStep() {
      this.currentStep--;
    },
    
    cancel() {
      this.$router.push('/materials/plans');
    },
    
    async generatePR() {
      if (!this.formData.leadTimeDays) {
        alert('Please fill in all required fields');
        return;
      }
      
      this.isGenerating = true;
      try {
        const planItems = this.selectedPlans.map(plan => ({
          plan_id: plan.plan_id,
          item_id: plan.item_id,
          quantity: this.planQuantities[plan.plan_id]
        }));
        
        const payload = {
          period: this.formData.selectedPeriod + '-01',
          lead_time_days: this.formData.leadTimeDays,
          notes: this.formData.notes,
          plan_items: planItems
        };
        
        const response = await axios.post('/inventory/material-planning/generate-pr', payload);
        
        this.prResult = response.data.data;
        this.showSuccess = true;
        
      } catch (error) {
        console.error('Error generating PR:', error);
        const message = error.response?.data?.message || 'Failed to generate purchase requisition';
        alert(message);
      } finally {
        this.isGenerating = false;
      }
    },
    
    closeSuccess() {
      this.showSuccess = false;
      this.$router.push('/materials/plans');
    },
    
    viewPR() {
      this.$router.push(`/purchasing/requisitions/${this.prResult.pr_id}`);
    }
  }
};
</script>

<style scoped>
.pr-generation {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 16px;
}

.page-header {
  margin-bottom: 32px;
}

.page-title {
  font-size: 24px;
  font-weight: 600;
  color: var(--gray-800);
  margin-bottom: 8px;
}

.page-description {
  color: var(--gray-600);
  font-size: 16px;
}

.wizard-container {
  margin-bottom: 32px;
}

.wizard-steps {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 32px;
}

.wizard-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
}

.step-number {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid var(--gray-200);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  color: var(--gray-400);
  background: white;
  margin-bottom: 8px;
  transition: all 0.3s ease;
}

.step-label {
  font-size: 14px;
  color: var(--gray-600);
  font-weight: 500;
}

.wizard-step.active .step-number {
  border-color: var(--primary-color);
  color: var(--primary-color);
  background: var(--primary-bg);
}

.wizard-step.completed .step-number {
  border-color: var(--primary-color);
  background: var(--primary-color);
  color: white;
}

.wizard-connector {
  width: 100px;
  height: 2px;
  background: var(--gray-200);
  margin: 0 24px;
  margin-top: -20px;
  transition: all 0.3s ease;
}

.wizard-connector.active {
  background: var(--primary-color);
}

.form-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  padding: 32px;
  margin-bottom: 24px;
}

.step-content {
  min-height: 300px;
}

.step-title {
  font-size: 20px;
  font-weight: 600;
  color: var(--gray-800);
  margin-bottom: 24px;
}

.form-section {
  margin-bottom: 32px;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 24px;
}

.form-group {
  margin-bottom: 24px;
}

.form-group label {
  display: block;
  font-weight: 500;
  margin-bottom: 8px;
  color: var(--gray-700);
}

.form-control {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid var(--gray-200);
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-control:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-control.readonly {
  background: var(--gray-50);
  color: var(--gray-600);
}

.form-note {
  color: var(--gray-500);
  font-size: 12px;
  margin-top: 4px;
}

.summary-card {
  background: var(--gray-50);
  border: 1px solid var(--gray-200);
  border-radius: 6px;
  padding: 24px;
  margin-top: 32px;
}

.summary-card h3 {
  font-size: 16px;
  font-weight: 600;
  color: var(--gray-800);
  margin-bottom: 16px;
}

.summary-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 16px;
}

.stat-box {
  background: white;
  border: 1px solid var(--gray-200);
  border-radius: 6px;
  padding: 16px;
  text-align: center;
}

.stat-value {
  font-size: 24px;
  font-weight: 600;
  color: var(--gray-800);
  margin-bottom: 4px;
}

.stat-label {
  font-size: 14px;
  color: var(--gray-600);
}

.loading-state {
  text-align: center;
  padding: 48px 0;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.table-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.search-input {
  width: 300px;
  padding: 8px 12px;
  border: 1px solid var(--gray-200);
  border-radius: 6px;
  font-size: 14px;
}

.bulk-actions {
  display: flex;
  gap: 12px;
}

.table-container {
  overflow-x: auto;
  border: 1px solid var(--gray-200);
  border-radius: 6px;
}

.plans-table {
  width: 100%;
  border-collapse: collapse;
}

.plans-table th {
  text-align: left;
  padding: 12px;
  border-bottom: 2px solid var(--gray-200);
  font-weight: 600;
  color: var(--gray-700);
  background: var(--gray-50);
}

.plans-table td {
  padding: 12px;
  border-bottom: 1px solid var(--gray-200);
  color: var(--gray-800);
}

.plans-table tr:hover {
  background: var(--gray-50);
}

.plans-table tr.selected {
  background: var(--primary-bg);
}

.checkbox-column {
  width: 40px;
}

.item-code {
  font-family: monospace;
  font-weight: 500;
}

.text-right {
  text-align: right;
}

.type-badge {
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.badge-primary {
  background: var(--primary-bg);
  color: var(--primary-color);
}

.badge-secondary {
  background: var(--gray-100);
  color: var(--gray-800);
}

.quantity-input {
  width: 100px;
  padding: 4px 8px;
  border: 1px solid var(--gray-200);
  border-radius: 4px;
  text-align: right;
}

.btn-edit {
  background: none;
  border: none;
  color: var(--gray-600);
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
}

.btn-edit:hover {
  background: var(--gray-100);
  color: var(--primary-color);
}

.no-data {
  text-align: center;
  padding: 24px;
  color: var(--gray-500);
}

.selection-summary {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 16px;
  padding: 16px;
  background: var(--gray-50);
  border-radius: 6px;
}

.summary-text {
  color: var(--gray-700);
}

.summary-total {
  font-weight: 600;
  color: var(--gray-800);
}

.no-plans-state {
  text-align: center;
  padding: 48px 0;
  color: var(--gray-500);
}

.no-plans-state i {
  font-size: 48px;
  margin-bottom: 16px;
}

.pr-preview {
  background: var(--gray-50);
  border: 1px solid var(--gray-200);
  border-radius: 6px;
  padding: 24px;
  margin-bottom: 32px;
}

.pr-preview h3 {
  font-size: 16px;
  font-weight: 600;
  color: var(--gray-800);
  margin-bottom: 16px;
}

.preview-content {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

.preview-item {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
}

.preview-label {
  color: var(--gray-600);
}

.preview-value {
  font-weight: 500;
  color: var(--gray-800);
}

.form-actions {
  display: flex;
  justify-content: space-between;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid var(--gray-200);
}

.btn {
  padding: 8px 24px;
  border-radius: 6px;
  font-weight: 500;
  border: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-width: 140px;
  justify-content: center;
}

.btn-primary {
  background: var(--primary-color);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-dark);
}

.btn-outline {
  border: 1px solid var(--gray-200);
  background: white;
  color: var(--gray-700);
}

.btn-outline:hover:not(:disabled) {
  background: var(--gray-50);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.required {
  color: #dc2626;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 50;
  display: flex;
  justify-content: center;
  align-items: center;
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 50;
}

.modal-content {
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  width: 100%;
  z-index: 60;
  overflow: hidden;
}

.modal-md {
  max-width: 600px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  border-bottom: 1px solid var(--gray-200);
}

.modal-header h2 {
  font-size: 18px;
  font-weight: 600;
  color: var(--gray-800);
}

.close-btn {
  background: none;
  border: none;
  color: var(--gray-600);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 4px;
}

.close-btn:hover {
  background: var(--gray-100);
  color: var(--gray-800);
}

.modal-body {
  padding: 24px;
}

.success-content {
  text-align: center;
}

.success-icon {
  font-size: 56px;
  color: #059669;
  margin-bottom: 16px;
}

.success-content h3 {
  font-size: 18px;
  font-weight: 600;
  color: var(--gray-800);
  margin-bottom: 8px;
}

.success-content p {
  color: var(--gray-600);
  margin-bottom: 24px;
}

.pr-details {
  background: var(--gray-50);
  border-radius: 6px;
  padding: 16px;
  margin-bottom: 24px;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
}

.detail-label {
  color: var(--gray-600);
}

.detail-value {
  font-weight: 500;
  color: var(--gray-800);
}

.success-actions {
  display: flex;
  justify-content: center;
  gap: 12px;
}

.fa-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>