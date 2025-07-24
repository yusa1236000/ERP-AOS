<!-- src/views/inventory/UnitOfMeasureList.vue -->
<template>
    <div class="uom-list">
      <!-- Search and Filter Section -->
      <div class="page-actions">
        <div class="search-box">
          <i class="fas fa-search search-icon"></i>
          <input
            type="text"
            v-model="searchQuery"
            placeholder="Search units of measure..."
            @input="applyFilters"
          />
          <button v-if="searchQuery" @click="clearSearch" class="clear-search">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <button class="btn btn-primary" @click="openAddUomModal">
          <i class="fas fa-plus"></i> Add Unit of Measure
        </button>
      </div>

      <!-- UOM Table -->
      <div class="uom-table-container">
        <div v-if="isLoading" class="loading-indicator">
          <i class="fas fa-spinner fa-spin"></i> Loading units of measure...
        </div>

        <div v-else-if="filteredUoms.length === 0" class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-ruler"></i>
          </div>
          <h3>No units of measure found</h3>
          <p>Try adjusting your search or add a new unit of measure.</p>
        </div>

        <table v-else class="data-table">
          <thead>
            <tr>
              <th @click="sortBy('uom_id')" class="sortable">
                ID
                <i v-if="sortColumn === 'uom_id'" :class="sortIconClass"></i>
              </th>
              <th @click="sortBy('name')" class="sortable">
                Name
                <i v-if="sortColumn === 'name'" :class="sortIconClass"></i>
              </th>
              <th @click="sortBy('symbol')" class="sortable">
                Symbol
                <i v-if="sortColumn === 'symbol'" :class="sortIconClass"></i>
              </th>
              <th>Description</th>
              <th>Used By</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="uom in paginatedUoms" :key="uom.uom_id">
              <td>{{ uom.uom_id }}</td>
              <td>{{ uom.name }}</td>
              <td><code>{{ uom.symbol }}</code></td>
              <td>{{ uom.description || '-' }}</td>
              <td>{{ uom.items_count || 0 }} item(s)</td>
              <td class="actions">
                <button class="action-btn" title="Edit Unit" @click="editUom(uom)">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="action-btn" title="Delete Unit" @click="confirmDelete(uom)" :disabled="uom.items_count > 0">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination">
        <div class="pagination-info">
          Showing {{ paginationInfo.from }} to {{ paginationInfo.to }} of {{ filteredUoms.length }} units
        </div>
        <div class="pagination-controls">
          <button
            class="pagination-btn"
            :disabled="currentPage === 1"
            @click="goToPage(currentPage - 1)"
          >
            <i class="fas fa-chevron-left"></i>
          </button>

          <template v-for="page in displayedPages" :key="page">
            <button
              v-if="page !== '...'"
              class="pagination-btn"
              :class="{ active: page === currentPage }"
              @click="goToPage(page)"
            >
              {{ page }}
            </button>
            <span v-else class="pagination-ellipsis">...</span>
          </template>

          <button
            class="pagination-btn"
            :disabled="currentPage === totalPages"
            @click="goToPage(currentPage + 1)"
          >
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </div>

      <!-- Add/Edit UOM Modal -->
      <div v-if="showUomModal" class="modal">
        <div class="modal-backdrop" @click="closeUomModal"></div>
        <div class="modal-content">
          <div class="modal-header">
            <h2>{{ isEditMode ? 'Edit Unit of Measure' : 'Add Unit of Measure' }}</h2>
            <button class="close-btn" @click="closeUomModal">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveUom">
              <div class="form-group">
                <label for="name">Name*</label>
                <input
                  type="text"
                  id="name"
                  v-model="uomForm.name"
                  required
                  maxlength="50"
                  placeholder="e.g., Kilogram, Meter, Each"
                />
                <small>The full name of the unit of measure.</small>
              </div>

              <div class="form-group">
                <label for="symbol">Symbol*</label>
                <input
                  type="text"
                  id="symbol"
                  v-model="uomForm.symbol"
                  required
                  maxlength="10"
                  placeholder="e.g., kg, m, ea"
                />
                <small>The abbreviation or symbol used to represent this unit.</small>
              </div>

              <div class="form-group">
                <label for="description">Description</label>
                <textarea
                  id="description"
                  v-model="uomForm.description"
                  rows="3"
                  placeholder="Optional description"
                ></textarea>
              </div>

              <div class="form-actions">
                <button type="button" class="btn btn-secondary" @click="closeUomModal">
                  Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                  {{ isEditMode ? 'Update' : 'Add' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div v-if="showDeleteModal" class="modal">
        <div class="modal-backdrop" @click="closeDeleteModal"></div>
        <div class="modal-content modal-sm">
          <div class="modal-header">
            <h2>Confirm Delete</h2>
            <button class="close-btn" @click="closeDeleteModal">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete the <strong>{{ uomToDelete.name }} ({{ uomToDelete.symbol }})</strong> unit of measure?</p>
            <p class="text-danger">This action cannot be undone.</p>

            <div v-if="uomToDelete.items_count > 0" class="warning-message">
              <i class="fas fa-exclamation-triangle"></i>
              This unit of measure is used by {{ uomToDelete.items_count }} item(s) and cannot be deleted.
            </div>

            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="closeDeleteModal">
                Cancel
              </button>
              <button
                type="button"
                class="btn btn-danger"
                @click="deleteUom"
                :disabled="uomToDelete.items_count > 0"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, computed, onMounted, watch } from 'vue';
  import axios from 'axios';

  export default {
    name: 'UnitOfMeasureList',
    setup() {
      // Data
      const uoms = ref([]);
      const isLoading = ref(true);
      const searchQuery = ref('');

      // Sorting
      const sortColumn = ref('uom_id');
      const sortDirection = ref('asc');

      // Pagination
      const currentPage = ref(1);
      const itemsPerPage = ref(10);

      // Modals
      const showUomModal = ref(false);
      const showDeleteModal = ref(false);
      const isEditMode = ref(false);
      const uomForm = ref({
        name: '',
        symbol: '',
        description: '',
      });
      const uomToDelete = ref({});

      // Computed properties
      const filteredUoms = computed(() => {
        let result = [...uoms.value];

        // Apply search filter
        if (searchQuery.value) {
          const query = searchQuery.value.toLowerCase();
          result = result.filter(uom =>
            uom.name.toLowerCase().includes(query) ||
            uom.symbol.toLowerCase().includes(query) ||
            (uom.description && uom.description.toLowerCase().includes(query))
          );
        }

        // Apply sorting
        result.sort((a, b) => {
          let comparison = 0;

          if (a[sortColumn.value] < b[sortColumn.value]) {
            comparison = -1;
          } else if (a[sortColumn.value] > b[sortColumn.value]) {
            comparison = 1;
          }

          return sortDirection.value === 'asc' ? comparison : -comparison;
        });

        return result;
      });

      // Pagination logic
      const totalPages = computed(() => {
        return Math.ceil(filteredUoms.value.length / itemsPerPage.value);
      });

      const paginatedUoms = computed(() => {
        const startIndex = (currentPage.value - 1) * itemsPerPage.value;
        const endIndex = startIndex + itemsPerPage.value;
        return filteredUoms.value.slice(startIndex, endIndex);
      });

      const paginationInfo = computed(() => {
        const total = filteredUoms.value.length;
        const from = total === 0 ? 0 : (currentPage.value - 1) * itemsPerPage.value + 1;
        const to = Math.min(currentPage.value * itemsPerPage.value, total);

        return { from, to, total };
      });

      const displayedPages = computed(() => {
        const total = totalPages.value;
        const current = currentPage.value;
        const pages = [];

        if (total <= 7) {
          // Show all pages
          for (let i = 1; i <= total; i++) {
            pages.push(i);
          }
        } else {
          // Always include first page
          pages.push(1);

          // Show ellipsis if current page is more than 3
          if (current > 3) {
            pages.push('...');
          }

          // Add pages around current page
          const startPage = Math.max(2, current - 1);
          const endPage = Math.min(total - 1, current + 1);

          for (let i = startPage; i <= endPage; i++) {
            pages.push(i);
          }

          // Show ellipsis if current page is less than total - 2
          if (current < total - 2) {
            pages.push('...');
          }

          // Always include last page
          pages.push(total);
        }

        return pages;
      });

      const sortIconClass = computed(() => {
        return sortDirection.value === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
      });

      // Methods
      const fetchUoms = async () => {
        isLoading.value = true;

        try {
          const response = await axios.get('/inventory/uom');
          uoms.value = response.data.data.map(uom => ({
            ...uom,
            items_count: uom.items_count || 0
          }));
        } catch (error) {
          console.error('Error fetching units of measure:', error);
          // You could show an error message to the user here
        } finally {
          isLoading.value = false;
        }
      };

      const applyFilters = () => {
        currentPage.value = 1;  // Reset to first page when filters change
      };

      const clearSearch = () => {
        searchQuery.value = '';
        applyFilters();
      };

      const sortBy = (column) => {
        if (sortColumn.value === column) {
          sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
        } else {
          sortColumn.value = column;
          sortDirection.value = 'asc';
        }
      };

      const goToPage = (page) => {
        currentPage.value = page;
      };

      const openAddUomModal = () => {
        isEditMode.value = false;
        uomForm.value = {
          name: '',
          symbol: '',
          description: '',
        };
        showUomModal.value = true;
      };

      const editUom = (uom) => {
        isEditMode.value = true;
        uomForm.value = {
          uom_id: uom.uom_id,
          name: uom.name,
          symbol: uom.symbol,
          description: uom.description || '',
        };
        showUomModal.value = true;
      };

      const closeUomModal = () => {
        showUomModal.value = false;
      };

      const saveUom = async () => {
        try {
          if (isEditMode.value) {
            // Update existing UOM
            await axios.put(`/inventory/uom/${uomForm.value.uom_id}`, uomForm.value);
            // Update local state
            const index = uoms.value.findIndex(item => item.uom_id === uomForm.value.uom_id);
            if (index !== -1) {
              uoms.value[index] = { ...uoms.value[index], ...uomForm.value };
            }
            // Show success notification
            alert('Unit of measure updated successfully!');
          } else {
            // Create new UOM
            const response = await axios.post('/inventory/uom', uomForm.value);
            uoms.value.push({ ...response.data.data, items_count: 0 });
            // Show success notification
            alert('Unit of measure created successfully!');
          }

          // Close modal and reset form
          closeUomModal();
          uomForm.value = {
            name: '',
            symbol: '',
            description: ''
          };
        } catch (error) {
          console.error('Error saving unit of measure:', error);

          if (error.response && error.response.data && error.response.data.errors) {
            // Handle validation errors
            const validationErrors = error.response.data.errors;
            let errorMessage = 'Validation errors:\n';
            for (const field in validationErrors) {
              errorMessage += `${field}: ${validationErrors[field].join(', ')}\n`;
            }
            alert(errorMessage);
          } else {
            // Handle other errors
            let errorMessage = 'Failed to save unit of measure.\n';
            if (error.response) {
              errorMessage += `Status: ${error.response.status}\n`;
              if (error.response.data && error.response.data.message) {
                errorMessage += `Error: ${error.response.data.message}`;
              } else {
                errorMessage += 'No additional error details available';
              }
            } else {
              errorMessage += 'Network error or server unavailable';
            }
            alert(errorMessage);

          }
        }
      };


      const confirmDelete = (uom) => {
        uomToDelete.value = uom;
        showDeleteModal.value = true;
      };

      const closeDeleteModal = () => {
        showDeleteModal.value = false;
      };

      const deleteUom = async () => {
        try {
          if (uomToDelete.value.items_count > 0) {
            // Don't allow deletion if UOM is in use
            return;
          }

          await axios.delete(`/inventory/uom/${uomToDelete.value.uom_id}`);

          // Remove from local state
          uoms.value = uoms.value.filter(item => item.uom_id !== uomToDelete.value.uom_id);

          // Close modal and show success notification
          closeDeleteModal();
          // You could show a success message here
        } catch (error) {
          console.error('Error deleting unit of measure:', error);

          if (error.response && error.response.status === 422) {
            // Handle the case where UOM is being used
            // You could show a specific error message
          } else {
            // Handle other errors
            // You could show a generic error message
          }
        }
      };

      // Watch for filtered results changes to adjust pagination
      watch(filteredUoms, (newUoms, oldUoms) => {
        if (Math.abs(newUoms.length - oldUoms.length) > itemsPerPage.value) {
          currentPage.value = 1; // Reset to first page when results change significantly
        }
      });

      // Load data on component mount
      onMounted(() => {
        fetchUoms();
      });

      return {
        uoms,
        isLoading,
        searchQuery,
        sortColumn,
        sortDirection,
        sortIconClass,
        currentPage,
        filteredUoms,
        paginatedUoms,
        totalPages,
        paginationInfo,
        displayedPages,
        showUomModal,
        showDeleteModal,
        isEditMode,
        uomForm,
        uomToDelete,
        applyFilters,
        clearSearch,
        sortBy,
        goToPage,
        openAddUomModal,
        editUom,
        closeUomModal,
        saveUom,
        confirmDelete,
        closeDeleteModal,
        deleteUom
      };
    }
  };
  </script>

  <style scoped>
  .uom-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .page-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
  }

  .search-box {
    position: relative;
    width: 100%;
    max-width: 400px;
  }

  .search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
  }

  .search-box input {
    width: 100%;
    padding: 0.625rem 2.5rem 0.625rem 2.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
  }

  .clear-search {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem;
  }

  .uom-table-container {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }

  .data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
  }

  .data-table th {
    text-align: left;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e2e8f0;
    background-color: #f8fafc;
    font-weight: 500;
    color: #64748b;
  }

  .data-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
  }

  .data-table tr:hover td {
    background-color: #f8fafc;
  }

  .data-table code {
    background-color: #f1f5f9;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-family: monospace;
    font-size: 0.875rem;
  }

  .sortable {
    cursor: pointer;
    position: relative;
  }

  .sortable i {
    margin-left: 0.5rem;
    font-size: 0.75rem;
  }

  .actions {
    display: flex;
    gap: 0.5rem;
  }

  .action-btn {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s, color 0.2s;
  }

  .action-btn:hover:not(:disabled) {
    background-color: #f1f5f9;
    color: #0f172a;
  }

  .action-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .btn {
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    transition: background-color 0.2s, color 0.2s;
  }

  .btn-primary {
    background-color: #2563eb;
    color: white;
  }

  .btn-primary:hover {
    background-color: #1d4ed8;
  }

  .btn-secondary {
    background-color: #e2e8f0;
    color: #1e293b;
  }

  .btn-secondary:hover {
    background-color: #cbd5e1;
  }

  .btn-danger {
    background-color: #dc2626;
    color: white;
  }

  .btn-danger:hover:not(:disabled) {
    background-color: #b91c1c;
  }

  .btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }

  .pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
  }

  .pagination-info {
    color: #64748b;
    font-size: 0.875rem;
  }

  .pagination-controls {
    display: flex;
    gap: 0.25rem;
  }

  .pagination-btn {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.375rem;
    background: none;
    border: 1px solid #e2e8f0;
    color: #64748b;
    cursor: pointer;
  }

  .pagination-btn:hover:not(:disabled) {
    background-color: #f1f5f9;
    color: #0f172a;
  }

  .pagination-btn.active {
    background-color: #2563eb;
    color: white;
    border-color: #2563eb;
  }

  .pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .pagination-ellipsis {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 2rem;
    height: 2rem;
    color: #64748b;
  }

  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 0;
    text-align: center;
    color: #64748b;
  }

  .empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #cbd5e1;
  }

  .empty-state h3 {
    font-size: 1.25rem;
    margin: 0 0 0.5rem 0;
    color: #1e293b;
  }

  .empty-state p {
    margin: 0;
    font-size: 0.875rem;
  }

  .loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 4rem 0;
    color: #64748b;
    font-size: 1rem;
  }

  .loading-indicator i {
    margin-right: 0.5rem;
  }

  /* Modal styles */
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
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 50;
  }

  .modal-content {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 500px;
    z-index: 60;
    overflow: hidden;
  }

  .modal-sm {
    max-width: 400px;
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .modal-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: #1e293b;
  }

  .close-btn {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem;
    border-radius: 0.25rem;
  }

  .close-btn:hover {
    background-color: #f1f5f9;
    color: #0f172a;
  }

  .modal-body {
    padding: 1.5rem;
  }

  .form-group {
    margin-bottom: 1.5rem;
  }

  .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #1e293b;
  }

  .form-group input,
  .form-group textarea {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
  }

  .form-group textarea {
    resize: vertical;
  }

  .form-group small {
    display: block;
    margin-top: 0.25rem;
    color: #64748b;
    font-size: 0.75rem;
  }

  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1.5rem;
  }

  .text-danger {
    color: #dc2626;
  }

  .warning-message {
    background-color: #fef3c7;
    color: #d97706;
    padding: 0.75rem;

    .search-box {
      max-width: none;
    }

    .data-table th:nth-child(4),
    .data-table td:nth-child(4) {
      display: none;
    }
  }
  </style>
