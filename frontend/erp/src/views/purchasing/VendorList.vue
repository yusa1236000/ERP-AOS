<!-- src/views/purchasing/VendorList.vue -->
<template>
    <div class="vendor-list-container">
      <div class="page-header">
        <h1>Vendor Management</h1>
        <button @click="openCreateForm" class="btn btn-primary">
          <i class="fas fa-plus"></i> Add Vendor
        </button>
      </div>

      <div class="filter-section">
        <SearchFilter
          v-model:value="searchQuery"
          placeholder="Search vendors..."
          @search="fetchVendors"
          @clear="clearSearch"
        >
          <template v-slot:filters>
            <div class="filter-group">
              <label for="status-filter">Status</label>
              <select id="status-filter" v-model="filters.status" @change="fetchVendors">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </template>
        </SearchFilter>
      </div>

      <div class="data-container">
        <DataTable
          :columns="columns"
          :items="vendors || []"
          :is-loading="isLoading"
          :key-field="'vendor_id'"
          :initial-sort-key="'name'"
          :initial-sort-order="'asc'"
          :empty-title="'No Vendors Found'"
          :empty-message="'Try adjusting your search or filters, or add a new vendor.'"
          :empty-icon="'fas fa-users'"
          @sort="handleSort"
        >
          <template v-slot:status="{ value }">
            <span :class="['status-badge', getStatusClass(value)]">
              {{ value === 'active' ? 'Active' : 'Inactive' }}
            </span>
          </template>

          <template v-slot:actions="{ item }">
            <div class="action-buttons">
              <button @click="viewVendor(item)" class="action-btn view-btn" title="View Details">
                <i class="fas fa-eye"></i>
              </button>
              <button @click="editVendor(item)" class="action-btn edit-btn" title="Edit Vendor">
                <i class="fas fa-edit"></i>
              </button>
              <button @click="confirmDelete(item)" class="action-btn delete-btn" title="Delete Vendor">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </template>
        </DataTable>

        <div v-if="totalPages > 1" class="pagination-wrapper">
          <PaginationComponent
            :current-page="currentPage"
            :total-pages="totalPages"
            :from="paginationFrom"
            :to="paginationTo"
            :total="totalVendors"
            @page-changed="changePage"
          />
        </div>
      </div>

      <!-- Confirmation Modal for Delete -->
      <ConfirmationModal
        v-if="showDeleteModal"
        :title="'Delete Vendor'"
        :message="'Are you sure you want to delete vendor <strong>' + (selectedVendor?.name || '') + '</strong>? This action cannot be undone.'"
        :confirm-button-text="'Delete'"
        :confirm-button-class="'btn btn-danger'"
        @confirm="deleteVendor"
        @close="closeDeleteModal"
      />
    </div>
  </template>

  <script>
  import { ref, reactive, computed, onMounted } from 'vue';
  import { useRouter } from 'vue-router';
  import VendorService from '@/services/VendorService';
  import DataTable from '@/components/common/DataTable.vue';

  export default {
    name: 'VendorList',
    components: {
      DataTable
    },
    setup() {
      const router = useRouter();
      const vendors = ref([]); // Initialize as an empty array
      const isLoading = ref(true);
      const currentPage = ref(1);
      const totalPages = ref(1);
      const totalVendors = ref(0);
      const itemsPerPage = ref(10);
      const searchQuery = ref('');
      const showDeleteModal = ref(false);
      const selectedVendor = ref(null);

      const filters = reactive({
        status: 'all'
      });

      const sortKey = ref('name');
      const sortOrder = ref('asc');

      // Table columns definition
      const columns = [
        { key: 'vendor_code', label: 'Vendor Code', sortable: true },
        { key: 'name', label: 'Name', sortable: true },
        { key: 'contact_person', label: 'Contact Person', sortable: true },
        { key: 'email', label: 'Email', sortable: true },
        { key: 'phone', label: 'Phone', sortable: false },
        { key: 'status', label: 'Status', sortable: true, template: 'status' }
      ];

      // Computed pagination values
      const paginationFrom = computed(() => {
        return (currentPage.value - 1) * itemsPerPage.value + 1;
      });

      const paginationTo = computed(() => {
        return Math.min(currentPage.value * itemsPerPage.value, totalVendors.value);
      });

      // Fetch vendors from API
      const fetchVendors = async () => {
        isLoading.value = true;

        try {
          const params = {
            page: currentPage.value,
            per_page: itemsPerPage.value,
            search: searchQuery.value,
            status: filters.status === 'all' ? undefined : filters.status,
            sort_field: sortKey.value,
            sort_direction: sortOrder.value
          };

          const response = await VendorService.getAllVendors(params);

          // Make sure we have valid data before assigning it
          if (response.data && Array.isArray(response.data)) {
            vendors.value = response.data;
          } else if (response && response.data && response.data.data && Array.isArray(response.data.data)) {
            // Handle Laravel's typical pagination response structure
            vendors.value = response.data.data;

            if (response.data.meta) {
              totalVendors.value = response.data.meta.total || 0;
              totalPages.value = response.data.meta.last_page || 1;
              currentPage.value = response.data.meta.current_page || 1;
            }
          } else {
            // Fallback to empty array if no valid data
            vendors.value = [];
          }
        } catch (error) {
          console.error('Error fetching vendors:', error);
        } finally {
          isLoading.value = false;
        }
      };

      const clearSearch = () => {
        searchQuery.value = '';
        fetchVendors();
      };

      const handleSort = ({ key, order }) => {
        sortKey.value = key;
        sortOrder.value = order;
        fetchVendors();
      };

      const changePage = (page) => {
        currentPage.value = page;
        fetchVendors();
      };

      const getStatusClass = (status) => {
        return status === 'active' ? 'status-active' : 'status-inactive';
      };

      const openCreateForm = () => {
        router.push('/purchasing/vendors/create');
      };

      const viewVendor = (vendor) => {
        router.push(`/purchasing/vendors/${vendor.vendor_id}`);
      };

      const editVendor = (vendor) => {
        router.push(`/purchasing/vendors/${vendor.vendor_id}/edit`);
      };

      const confirmDelete = (vendor) => {
        selectedVendor.value = vendor;
        showDeleteModal.value = true;
      };

      const closeDeleteModal = () => {
        showDeleteModal.value = false;
        selectedVendor.value = null;
      };

      const deleteVendor = async () => {
        if (!selectedVendor.value) return;

        try {
          await VendorService.deleteVendor(selectedVendor.value.vendor_id);
          fetchVendors();
          closeDeleteModal();
        } catch (error) {
          if (error.response && error.response.status === 400) {
            // Show error message if vendor has related records
            alert(error.response.data.message || 'This vendor cannot be deleted because it has related records.');
          } else {
            console.error('Error deleting vendor:', error);
          }
          closeDeleteModal();
        }
      };

      // Initialize
      onMounted(() => {
        fetchVendors();
      });

      return {
        vendors,
        isLoading,
        columns,
        currentPage,
        totalPages,
        totalVendors,
        paginationFrom,
        paginationTo,
        searchQuery,
        filters,
        showDeleteModal,
        selectedVendor,
        fetchVendors,
        clearSearch,
        handleSort,
        changePage,
        getStatusClass,
        openCreateForm,
        viewVendor,
        editVendor,
        confirmDelete,
        closeDeleteModal,
        deleteVendor
      };
    }
  };
  </script>

  <style scoped>
  .vendor-list-container {
    padding: 1rem;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .page-header h1 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--gray-800);
  }

  .filter-section {
    margin-bottom: 1.5rem;
  }

  .data-container {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }

  .pagination-wrapper {
    padding: 0.5rem;
    background-color: white;
    border-top: 1px solid var(--gray-200);
  }

  .action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
  }

  .action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.375rem;
    border: none;
    background: none;
    cursor: pointer;
    transition: background-color 0.2s;
  }

  .view-btn {
    color: var(--primary-color);
  }

  .view-btn:hover {
    background-color: var(--primary-bg);
  }

  .edit-btn {
    color: var(--warning-color);
  }

  .edit-btn:hover {
    background-color: var(--warning-bg);
  }

  .delete-btn {
    color: var(--danger-color);
  }

  .delete-btn:hover {
    background-color: var(--danger-bg);
  }

  .status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
  }

  .status-active {
    background-color: var(--success-bg);
    color: var(--success-color);
  }

  .status-inactive {
    background-color: var(--gray-100);
    color: var(--gray-700);
  }

  .btn {
    padding: 0.625rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    border: none;
    transition: background-color 0.2s, color 0.2s;
  }

  .btn-primary {
    background-color: var(--primary-color);
    color: white;
  }

  .btn-primary:hover {
    background-color: var(--primary-dark);
  }

  .btn-danger {
    background-color: var(--danger-color);
    color: white;
  }

  .btn-danger:hover {
    background-color: #b91c1c;
  }
  </style>
