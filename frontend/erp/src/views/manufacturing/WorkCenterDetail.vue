<!-- src/views/manufacturing/WorkCenterDetail.vue -->
<template>
    <div class="work-center-detail">
      <div v-if="isLoading" class="text-center p-5">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p class="mt-2">Loading work center details...</p>
      </div>
      
      <div v-else>
        <!-- Header with actions -->
        <div class="detail-header mb-4">
          <div class="d-flex justify-content-between align-items-center">
            <h1>
              <i class="fas fa-industry mr-2"></i>
              {{ workCenter.name }}
              <span :class="['status-badge', workCenter.is_active ? 'active' : 'inactive']">
                {{ workCenter.is_active ? 'Active' : 'Inactive' }}
              </span>
            </h1>
            
            <div class="action-buttons">
              <router-link :to="`/manufacturing/work-centers/${workCenter.workcenter_id}/schedule`" class="btn btn-success mr-2">
                <i class="fas fa-calendar-alt mr-2"></i> View Schedule
              </router-link>
              <router-link :to="`/manufacturing/work-centers/${workCenter.workcenter_id}/edit`" class="btn btn-primary mr-2">
                <i class="fas fa-edit mr-2"></i> Edit
              </router-link>
              <button @click="confirmDelete" class="btn btn-danger">
                <i class="fas fa-trash mr-2"></i> Delete
              </button>
            </div>
          </div>
          
          <div class="breadcrumb-nav mt-2">
            <router-link to="/manufacturing/work-centers" class="breadcrumb-item">
              <i class="fas fa-arrow-left mr-1"></i> Back to Work Centers
            </router-link>
          </div>
        </div>
        
        <!-- Work Center Information Card -->
        <div class="card mb-4">
          <div class="card-header">
            <h2 class="card-title">Work Center Information</h2>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="detail-item">
                  <div class="detail-label">Code</div>
                  <div class="detail-value">{{ workCenter.code }}</div>
                </div>
                
                <div class="detail-item">
                  <div class="detail-label">Name</div>
                  <div class="detail-value">{{ workCenter.name }}</div>
                </div>
                
                <div class="detail-item">
                  <div class="detail-label">Status</div>
                  <div class="detail-value">
                    <span :class="['status-indicator', workCenter.is_active ? 'active' : 'inactive']"></span>
                    {{ workCenter.is_active ? 'Active' : 'Inactive' }}
                  </div>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="detail-item">
                  <div class="detail-label">Capacity</div>
                  <div class="detail-value">{{ workCenter.capacity }} units/hour</div>
                </div>
                
                <div class="detail-item">
                  <div class="detail-label">Cost per Hour</div>
                  <div class="detail-value">{{ formatCurrency(workCenter.cost_per_hour) }}</div>
                </div>
                
                <div class="detail-item">
                  <div class="detail-label">Description</div>
                  <div class="detail-value description">
                    {{ workCenter.description || 'No description available' }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Upcoming Maintenance Card -->
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="card-title">Upcoming Maintenance</h2>
            <button @click="loadMaintenanceSchedules" class="btn btn-sm btn-secondary">
              <i class="fas fa-sync mr-1"></i> Refresh
            </button>
          </div>
          <div class="card-body">
            <div v-if="isLoadingSchedules" class="text-center p-3">
              <i class="fas fa-spinner fa-spin"></i>
              <p class="mt-2">Loading maintenance schedules...</p>
            </div>
            
            <div v-else-if="maintenanceSchedules.length === 0" class="empty-state p-3">
              <p>No upcoming maintenance schedules found for this work center.</p>
            </div>
            
            <div v-else class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Type</th>
                    <th>Planned Date</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="schedule in maintenanceSchedules" :key="schedule.schedule_id">
                    <td>{{ schedule.maintenance_type }}</td>
                    <td>{{ formatDate(schedule.planned_date) }}</td>
                    <td>
                      <span :class="getStatusClass(schedule.status)">
                        {{ schedule.status }}
                      </span>
                    </td>
                    <td>{{ schedule.notes || '-' }}</td>
                    <td>
                      <div class="btn-group">
                        <button class="btn btn-sm btn-info">
                          <i class="fas fa-eye"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        
        <!-- Routing Operations Card -->
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="card-title">Routing Operations</h2>
            <button @click="loadRoutingOperations" class="btn btn-sm btn-secondary">
              <i class="fas fa-sync mr-1"></i> Refresh
            </button>
          </div>
          <div class="card-body">
            <div v-if="isLoadingOperations" class="text-center p-3">
              <i class="fas fa-spinner fa-spin"></i>
              <p class="mt-2">Loading routing operations...</p>
            </div>
            
            <div v-else-if="routingOperations.length === 0" class="empty-state p-3">
              <p>No routing operations associated with this work center.</p>
            </div>
            
            <div v-else class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Routing</th>
                    <th>Operation</th>
                    <th>Sequence</th>
                    <th>Setup Time</th>
                    <th>Run Time</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="operation in routingOperations" :key="operation.operation_id">
                    <td>{{ operation.routing?.routing_code || '-' }}</td>
                    <td>{{ operation.operation_name }}</td>
                    <td>{{ operation.sequence }}</td>
                    <td>{{ operation.setup_time }} mins</td>
                    <td>{{ operation.run_time }} mins</td>
                    <td>
                      <div class="btn-group">
                        <button class="btn btn-sm btn-info">
                          <i class="fas fa-eye"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Confirmation Modal -->
      <ConfirmationModal
        v-if="showDeleteModal"
        :title="'Delete Work Center'"
        :message="`Are you sure you want to delete the work center <strong>${workCenter.name}</strong>? This action cannot be undone.`"
        confirm-button-text="Delete"
        confirm-button-class="btn btn-danger"
        @confirm="deleteWorkCenter"
        @close="showDeleteModal = false"
      />
    </div>
  </template>
  
  <script>
  import { ref, reactive, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';
  
  export default {
    name: 'WorkCenterDetail',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const workCenterId = route.params.id;
      
      const workCenter = reactive({
        workcenter_id: '',
        code: '',
        name: '',
        capacity: 0,
        cost_per_hour: 0,
        description: '',
        is_active: true
      });
      
      const maintenanceSchedules = ref([]);
      const routingOperations = ref([]);
      
      const isLoading = ref(true);
      const isLoadingSchedules = ref(false);
      const isLoadingOperations = ref(false);
      const showDeleteModal = ref(false);
      
      const loadWorkCenter = async () => {
        isLoading.value = true;
        try {
          const response = await axios.get(`/manufacturing/work-centers/${workCenterId}`);
          const data = response.data.data;
          
          // Update the reactive object properties
          Object.keys(workCenter).forEach(key => {
            if (data[key] !== undefined) {
              workCenter[key] = data[key];
            }
          });
          
          // Load related data
          loadMaintenanceSchedules();
          loadRoutingOperations();
        } catch (error) {
          console.error('Error loading work center:', error);
          alert('Failed to load work center details. Please try again later.');
          router.push('/manufacturing/work-centers');
        } finally {
          isLoading.value = false;
        }
      };
      
      const loadMaintenanceSchedules = async () => {
        isLoadingSchedules.value = true;
        try {
          const response = await axios.get(`/manufacturing/work-centers/${workCenterId}/maintenance-schedules`);
          maintenanceSchedules.value = response.data.data;
        } catch (error) {
          console.error('Error loading maintenance schedules:', error);
        } finally {
          isLoadingSchedules.value = false;
        }
      };
      
      const loadRoutingOperations = async () => {
        isLoadingOperations.value = true;
        try {
          // This endpoint might need to be adjusted based on your actual API structure
          const response = await axios.get(`/manufacturing/routings/operations?workcenter_id=${workCenterId}`);
          routingOperations.value = response.data.data;
        } catch (error) {
          console.error('Error loading routing operations:', error);
        } finally {
          isLoadingOperations.value = false;
        }
      };
      
      const confirmDelete = () => {
        showDeleteModal.value = true;
      };
      
      const deleteWorkCenter = async () => {
        try {
          await axios.delete(`manufacturing/work-centers/${workCenterId}`);
          showDeleteModal.value = false;
          router.push('/manufacturing/work-centers');
        } catch (error) {
          if (error.response && error.response.status === 400) {
            alert(error.response.data.message || 'This work center cannot be deleted because it is in use.');
          } else {
            console.error('Error deleting work center:', error);
            alert('Failed to delete work center. Please try again later.');
          }
          showDeleteModal.value = false;
        }
      };
      
      const formatCurrency = (value) => {
        return new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: 'USD',
          minimumFractionDigits: 2
        }).format(value);
      };
      
      const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        }).format(date);
      };
      
      const getStatusClass = (status) => {
        const statusMap = {
          'Scheduled': 'badge badge-primary',
          'Completed': 'badge badge-success',
          'Pending': 'badge badge-warning',
          'Canceled': 'badge badge-danger',
          'In Progress': 'badge badge-info'
        };
        
        return statusMap[status] || 'badge badge-secondary';
      };
      
      onMounted(() => {
        loadWorkCenter();
      });
      
      return {
        workCenter,
        maintenanceSchedules,
        routingOperations,
        isLoading,
        isLoadingSchedules,
        isLoadingOperations,
        showDeleteModal,
        confirmDelete,
        deleteWorkCenter,
        formatCurrency,
        formatDate,
        getStatusClass,
        loadMaintenanceSchedules,
        loadRoutingOperations
      };
    }
  };
  </script>
  
  <style scoped>
  .work-center-detail {
    padding: 1rem 0;
  }
  
  .detail-header {
    margin-bottom: 2rem;
  }
  
  .breadcrumb-nav {
    color: var(--gray-500);
    font-size: 0.875rem;
  }
  
  .breadcrumb-item {
    color: var(--gray-600);
    text-decoration: none;
  }
  
  .breadcrumb-item:hover {
    color: var(--primary-color);
    text-decoration: underline;
  }
  
  .status-badge {
    font-size: 0.875rem;
    font-weight: normal;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    margin-left: 0.75rem;
    vertical-align: middle;
  }
  
  .status-badge.active {
    background-color: var(--success-bg);
    color: var(--success-color);
  }
  
  .status-badge.inactive {
    background-color: var(--gray-200);
    color: var(--gray-600);
  }
  
  .detail-item {
    margin-bottom: 1.25rem;
  }
  
  .detail-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-500);
    margin-bottom: 0.25rem;
  }
  
  .detail-value {
    font-size: 1rem;
  }
  
  .detail-value.description {
    white-space: pre-line;
  }
  
  .status-indicator {
    display: inline-block;
    width: 0.75rem;
    height: 0.75rem;
    border-radius: 50%;
    margin-right: 0.5rem;
  }
  
  .status-indicator.active {
    background-color: var(--success-color);
  }
  
  .status-indicator.inactive {
    background-color: var(--gray-400);
  }
  
  .empty-state {
    color: var(--gray-500);
    text-align: center;
  }
  
  .badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
  }
  </style>