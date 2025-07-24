<!-- src/components/manufacturing/BOMLineFormModal.vue -->
<template>
  <div class="bom-line-form-modal">
    <div class="modal-backdrop" @click="$emit('close')"></div>
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">{{ isEditMode ? 'Edit Component' : 'Add Component' }}</h3>
        <button type="button" class="close-btn" @click="$emit('close')">
          <i class="fas fa-times"></i>
        </button>
      </div>
      
      <div class="modal-body">
        <form @submit.prevent="submitForm">
          <div class="form-group">
            <label for="item_id">Item <span class="required">*</span></label>
            <select 
              id="item_id" 
              v-model="form.item_id" 
              class="form-control" 
              :class="{ 'is-invalid': errors.item_id }"
              required
            >
              <option value="">Select Item</option>
              <option v-for="item in items" :key="item.item_id" :value="item.item_id">
                {{ item.name }} ({{ item.item_code }})
              </option>
            </select>
            <div v-if="errors.item_id" class="invalid-feedback">{{ errors.item_id }}</div>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="quantity">Quantity <span class="required">*</span></label>
                <input 
                  type="number" 
                  id="quantity" 
                  v-model="form.quantity" 
                  class="form-control" 
                  :class="{ 'is-invalid': errors.quantity }"
                  required
                  step="0.0001"
                  min="0.0001"
                  placeholder="e.g., 1.00"
                />
                <div v-if="errors.quantity" class="invalid-feedback">{{ errors.quantity }}</div>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="form-group">
                <label for="uom_id">Unit of Measure <span class="required">*</span></label>
                <select 
                  id="uom_id" 
                  v-model="form.uom_id" 
                  class="form-control" 
                  :class="{ 'is-invalid': errors.uom_id }"
                  required
                >
                  <option value="">Select UOM</option>
                  <option v-for="uom in uoms" :key="uom.uom_id" :value="uom.uom_id">
                    {{ uom.name }} ({{ uom.symbol }})
                  </option>
                </select>
                <div v-if="errors.uom_id" class="invalid-feedback">{{ errors.uom_id }}</div>
              </div>
            </div>
          </div>
          
          <div class="option-toggles">
            <div class="form-group form-check">
              <input 
                type="checkbox" 
                id="is_critical" 
                v-model="form.is_critical" 
                class="form-check-input" 
              />
              <label for="is_critical" class="form-check-label">Critical Component</label>
              <small class="form-text text-muted">
                Mark as critical if this component is essential for production
              </small>
            </div>
            
            <div class="form-group form-check">
              <input 
                type="checkbox" 
                id="is_yield_based" 
                v-model="form.is_yield_based" 
                class="form-check-input" 
              />
              <label for="is_yield_based" class="form-check-label">Yield-Based Component</label>
              <small class="form-text text-muted">
                Enable if this component's quantity affects the final yield of the product
              </small>
            </div>
          </div>
          
          <div v-if="form.is_yield_based" class="yield-based-fields">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="yield_ratio">Yield Ratio <span class="required">*</span></label>
                  <input 
                    type="number" 
                    id="yield_ratio" 
                    v-model="form.yield_ratio" 
                    class="form-control" 
                    :class="{ 'is-invalid': errors.yield_ratio }"
                    required
                    step="0.0001"
                    min="0.0001"
                    placeholder="e.g., 0.80"
                  />
                  <small class="form-text text-muted">
                    How many units of finished product can be produced per unit of this material
                  </small>
                  <div v-if="errors.yield_ratio" class="invalid-feedback">{{ errors.yield_ratio }}</div>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="form-group">
                  <label for="shrinkage_factor">Shrinkage Factor</label>
                  <div class="input-group">
                    <input 
                      type="number" 
                      id="shrinkage_factor" 
                      v-model="form.shrinkage_factor" 
                      class="form-control" 
                      :class="{ 'is-invalid': errors.shrinkage_factor }"
                      step="0.01"
                      min="0"
                      max="0.99"
                      placeholder="e.g., 0.05"
                    />
                    <div class="input-group-append">
                      <span class="input-group-text">or {{ (form.shrinkage_factor * 100) || 0 }}%</span>
                    </div>
                  </div>
                  <small class="form-text text-muted">
                    Factor to account for material waste/shrinkage (0-99%)
                  </small>
                  <div v-if="errors.shrinkage_factor" class="invalid-feedback">{{ errors.shrinkage_factor }}</div>
                </div>
              </div>
            </div>
            
            <div class="yield-example" v-if="form.yield_ratio > 0">
              <h4>Example Calculation</h4>
              <p>
                With a quantity of <strong>{{ form.quantity }}</strong> {{ getUomSymbol(form.uom_id) }}
                and a yield ratio of <strong>{{ form.yield_ratio }}</strong>:
              </p>
              
              <div class="calculation">
                <p>
                  <strong>{{ form.quantity }} {{ getUomSymbol(form.uom_id) }}</strong> of material
                  can produce approximately <strong>{{ calculateYield() }}</strong> units of finished product.
                </p>
                
                <p v-if="form.shrinkage_factor > 0" class="shrinkage-note">
                  <i class="fas fa-info-circle"></i> 
                  This includes a {{ (form.shrinkage_factor * 100).toFixed(1) }}% reduction for shrinkage/waste.
                </p>
              </div>
            </div>
          </div>
          
          <div class="form-group">
            <label for="notes">Notes</label>
            <textarea 
              id="notes" 
              v-model="form.notes" 
              class="form-control" 
              :class="{ 'is-invalid': errors.notes }"
              rows="3"
              placeholder="Enter any special instructions or notes about this component"
            ></textarea>
            <div v-if="errors.notes" class="invalid-feedback">{{ errors.notes }}</div>
          </div>
          
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="$emit('close')">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
              <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-1"></i>
              {{ isEditMode ? 'Update' : 'Add' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import axios from 'axios';

export default {
  name: 'BOMLineFormModal',
  props: {
    isEditMode: {
      type: Boolean,
      default: false
    },
    lineData: {
      type: Object,
      default: () => ({})
    },
    bomId: {
      type: Number,
      required: true
    }
  },
  emits: ['close', 'save'],
  setup(props, { emit }) {
    // State
    const isSubmitting = ref(false);
    const errors = ref({});
    const items = ref([]);
    const uoms = ref([]);
    
    // Form data
    const form = reactive({
      line_id: props.lineData.line_id,
      item_id: props.lineData.item_id || '',
      quantity: props.lineData.quantity || 1,
      uom_id: props.lineData.uom_id || '',
      is_critical: props.lineData.is_critical || false,
      is_yield_based: props.lineData.is_yield_based || false,
      yield_ratio: props.lineData.yield_ratio || 1,
      shrinkage_factor: props.lineData.shrinkage_factor || 0,
      notes: props.lineData.notes || ''
    });
    
    // Watch for changes in isYieldBased to set default values
    watch(() => form.is_yield_based, (newValue) => {
      if (newValue) {
        form.yield_ratio = form.yield_ratio === 0 ? 1 : form.yield_ratio;
      }
    });
    
    // Watch for prop changes (when editing different items)
    watch(() => props.lineData, (newData) => {
      form.line_id = newData.line_id;
      form.item_id = newData.item_id || '';
      form.quantity = newData.quantity || 1;
      form.uom_id = newData.uom_id || '';
      form.is_critical = newData.is_critical || false;
      form.is_yield_based = newData.is_yield_based || false;
      form.yield_ratio = newData.yield_ratio || 1;
      form.shrinkage_factor = newData.shrinkage_factor || 0;
      form.notes = newData.notes || '';
    }, { deep: true });
    
    // Fetch reference data
    const fetchReferenceData = async () => {
      try {
        // Fetch items
        const itemsResponse = await axios.get('/inventory/items');
        items.value = itemsResponse.data.data || itemsResponse.data;
        
        // Fetch UOMs
        const uomsResponse = await axios.get('inventory/uom');
        uoms.value = uomsResponse.data.data || uomsResponse.data;
      } catch (error) {
        console.error('Error fetching reference data:', error);
      }
    };
    
    // Get UOM symbol for display
    const getUomSymbol = (uomId) => {
      if (!uomId) return '';
      const uom = uoms.value.find(u => u.uom_id === uomId);
      return uom ? uom.symbol : '';
    };
    
    // Calculate yield for example display
    const calculateYield = () => {
      if (!form.is_yield_based || form.yield_ratio <= 0) return '0';
      
      const shrinkageFactor = form.shrinkage_factor || 0;
      const effectiveFactor = 1 - shrinkageFactor;
      const yield_qty = form.quantity * form.yield_ratio * effectiveFactor;
      
      return yield_qty.toFixed(2);
    };
    
    // Form submission
    const submitForm = () => {
      errors.value = {}; // Clear previous errors
      
      // Validate form
      if (!form.item_id) errors.value.item_id = 'Please select an item';
      if (!form.quantity || form.quantity <= 0) errors.value.quantity = 'Quantity must be greater than zero';
      if (!form.uom_id) errors.value.uom_id = 'Please select a unit of measure';
      
      if (form.is_yield_based) {
        if (!form.yield_ratio || form.yield_ratio <= 0) {
          errors.value.yield_ratio = 'Yield ratio must be greater than zero';
        }
        
        if (form.shrinkage_factor < 0 || form.shrinkage_factor >= 1) {
          errors.value.shrinkage_factor = 'Shrinkage factor must be between 0 and 0.99';
        }
      }
      
      // Return if we have errors
      if (Object.keys(errors.value).length > 0) return;
      
      isSubmitting.value = true;
      
      // Create form data to send
      const formData = {
        item_id: form.item_id,
        quantity: form.quantity,
        uom_id: form.uom_id,
        is_critical: form.is_critical,
        is_yield_based: form.is_yield_based,
        notes: form.notes
      };
      
      // Add yield-based fields if needed
      if (form.is_yield_based) {
        formData.yield_ratio = form.yield_ratio;
        formData.shrinkage_factor = form.shrinkage_factor;
      }
      
      // Add line_id if editing
      if (props.isEditMode && form.line_id) {
        formData.line_id = form.line_id;
      }
      
      // Emit save event with form data
      emit('save', formData);
      isSubmitting.value = false;
    };
    
    onMounted(() => {
      fetchReferenceData();
    });
    
    return {
      form,
      errors,
      items,
      uoms,
      isSubmitting,
      getUomSymbol,
      calculateYield,
      submitForm
    };
  }
};
</script>

<style scoped>
.bom-line-form-modal {
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
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 50;
  backdrop-filter: blur(2px);
}

.modal-content {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  width: 100%;
  max-width: 650px;
  z-index: 60;
  overflow: hidden;
  animation: modalFadeIn 0.3s ease-out;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  background-color: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--gray-800);
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  color: var(--gray-500);
  cursor: pointer;
  font-size: 1.25rem;
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  transition: all 0.2s;
}

.close-btn:hover {
  background-color: rgba(0, 0, 0, 0.05);
  color: var(--gray-700);
}

.modal-body {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  font-weight: 600;
  color: var(--gray-700);
  margin-bottom: 0.5rem;
  display: block;
}

.required {
  color: var(--danger-color);
}

.form-control {
  border-radius: 0.375rem;
  border: 1px solid var(--gray-300);
  padding: 0.75rem;
  width: 100%;
  font-size: 0.95rem;
  transition: all 0.2s;
  background-color: white;
}

.form-control:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  outline: none;
}

.form-control.is-invalid {
  border-color: var(--danger-color);
}

.invalid-feedback {
  display: block;
  margin-top: 0.25rem;
  color: var(--danger-color);
  font-size: 0.75rem;
}

.form-check {
  padding-left: 1.75rem;
  margin-top: 0.75rem;
  margin-bottom: 0.75rem;
  position: relative;
}

.form-check-input {
  position: absolute;
  left: 0;
  top: 0.25rem;
  width: 1.125rem;
  height: 1.125rem;
  cursor: pointer;
}

.form-check-label {
  cursor: pointer;
  font-weight: 500;
  color: var(--gray-700);
}

.form-text {
  display: block;
  margin-top: 0.375rem;
  font-size: 0.75rem;
  color: var(--gray-500);
}

.row {
  display: flex;
  flex-wrap: wrap;
  margin: -0.5rem;
}

.col-md-6 {
  flex: 0 0 calc(50% - 1rem);
  padding: 0.5rem;
}

.option-toggles {
  margin-bottom: 1rem;
}

.yield-based-fields {
  border: 1px solid #bae6fd;
  background-color: #f0f9ff;
  padding: 1.25rem;
  margin-bottom: 1.5rem;
  border-radius: 0.5rem;
}

.yield-example {
  background-color: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  padding: 1rem;
  margin-top: 1rem;
}

.yield-example h4 {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--gray-600);
  margin-top: 0;
  margin-bottom: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.calculation {
  background-color: #f8fafc;
  padding: 1rem;
  border-radius: 0.375rem;
  margin-top: 0.5rem;
}

.calculation p {
  margin: 0 0 0.5rem;
  font-size: 0.875rem;
  color: var(--gray-700);
}

.calculation p:last-child {
  margin-bottom: 0;
}

.shrinkage-note {
  font-size: 0.75rem !important;
  color: var(--gray-500) !important;
  display: flex;
  align-items: flex-start;
  gap: 0.25rem;
}

.input-group {
  display: flex;
}

.input-group .form-control {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
  flex: 1;
}

.input-group-append {
  display: flex;
}

.input-group-text {
  border-top-right-radius: 0.375rem;
  border-bottom-right-radius: 0.375rem;
  border: 1px solid var(--gray-300);
  border-left: none;
  background-color: #f1f5f9;
  color: var(--gray-600);
  padding: 0 0.75rem;
  display: flex;
  align-items: center;
  font-size: 0.875rem;
  white-space: nowrap;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--gray-200);
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.625rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 0.375rem;
  transition: all 0.15s ease;
  cursor: pointer;
  border: 1px solid transparent;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
}

.btn-primary:hover {
  background-color: var(--primary-dark);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: white;
  color: var(--gray-700);
  border-color: var(--gray-300);
}

.btn-secondary:hover {
  background-color: var(--gray-100);
  border-color: var(--gray-400);
}

@keyframes modalFadeIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}

@media (max-width: 640px) {
  .modal-content {
    width: 95%;
    margin: 0 10px;
    max-height: 85vh;
  }
  
  .row {
    flex-direction: column;
  }
  
  .col-md-6 {
    flex: 0 0 100%;
  }
  
  .form-actions {
    flex-direction: column-reverse;
    gap: 0.75rem;
  }
  
  .form-actions .btn {
    width: 100%;
  }
}
</style>