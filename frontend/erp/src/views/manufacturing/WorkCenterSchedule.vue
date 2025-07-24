<!-- src/views/manufacturing/WorkCenterSchedule.vue -->
<template>
    <div class="work-center-schedule">
      <div v-if="isLoading" class="text-center p-5">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p class="mt-2">Loading work center schedule...</p>
      </div>
      
      <div v-else>
        <!-- Header with actions -->
        <div class="schedule-header mb-4">
          <div class="d-flex justify-content-between align-items-center">
            <h1>
              <i class="fas fa-calendar-alt mr-2"></i>
              {{ workCenter.name }} - Schedule
            </h1>
            
            <div class="action-buttons">
              <button @click="openAddMaintenanceModal" class="btn btn-primary mr-2">
                <i class="fas fa-plus mr-2"></i> Add Maintenance
              </button>
              <router-link :to="`/manufacturing/work-centers/${workCenterId}`" class="btn btn-secondary">
                <i class="fas fa-info-circle mr-2"></i> Work Center Details
              </router-link>
            </div>
          </div>
          
          <div class="breadcrumb-nav mt-2">
            <router-link to="/manufacturing/work-centers" class="breadcrumb-item">
              Work Centers
            </router-link>
            <span class="breadcrumb-separator">/</span>
            <router-link :to="`/manufacturing/work-centers/${workCenterId}`" class="breadcrumb-item">
              {{ workCenter.name }}
            </router-link>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">Schedule</span>
          </div>
        </div>
        
        <!-- Calendar Controls -->
        <div class="card mb-4">
          <div class="card-body">
            <div class="row">
              <div class="col-md-4">
                <div class="calendar-navigation">
                  <button @click="previousPeriod" class="btn btn-outline-secondary">
                    <i class="fas fa-chevron-left"></i>
                  </button>
                  <h3 class="current-period">{{ formattedPeriod }}</h3>
                  <button @click="nextPeriod" class="btn btn-outline-secondary">
                    <i class="fas fa-chevron-right"></i>
                  </button>
                </div>
              </div>
              
              <div class="col-md-4 text-center">
                <div class="btn-group" role="group">
                  <button 
                    @click="viewMode = 'month'" 
                    :class="['btn', viewMode === 'month' ? 'btn-primary' : 'btn-outline-primary']"
                  >
                    Month
                  </button>
                  <button 
                    @click="viewMode = 'week'" 
                    :class="['btn', viewMode === 'week' ? 'btn-primary' : 'btn-outline-primary']"
                  >
                    Week
                  </button>
                  <button 
                    @click="viewMode = 'day'" 
                    :class="['btn', viewMode === 'day' ? 'btn-primary' : 'btn-outline-primary']"
                  >
                    Day
                  </button>
                </div>
              </div>
              
              <div class="col-md-4 text-right">
                <button @click="goToToday" class="btn btn-outline-primary">
                  Today
                </button>
                
                <div class="event-filter ml-3 d-inline-block">
                  <div class="custom-control custom-checkbox custom-control-inline">
                    <input type="checkbox" id="show-maintenance" class="custom-control-input" v-model="showMaintenance">
                    <label class="custom-control-label" for="show-maintenance">Maintenance</label>
                  </div>
                  
                  <div class="custom-control custom-checkbox custom-control-inline">
                    <input type="checkbox" id="show-operations" class="custom-control-input" v-model="showOperations">
                    <label class="custom-control-label" for="show-operations">Operations</label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Month View Calendar -->
        <div v-if="viewMode === 'month'" class="card">
          <div class="card-body p-0">
            <div class="calendar-container">
              <div class="calendar-header">
                <div v-for="day in daysOfWeek" :key="day" class="calendar-day-header">
                  {{ day }}
                </div>
              </div>
              
              <div class="calendar-grid">
                <div 
                  v-for="(day, index) in calendarDays" 
                  :key="index" 
                  :class="[
                    'calendar-day', 
                    day.isCurrentMonth ? 'current-month' : 'other-month',
                    day.isToday ? 'today' : '',
                    day.hasEvents ? 'has-events' : ''
                  ]"
                >
                  <div class="day-number">{{ day.date.getDate() }}</div>
                  
                  <div class="day-events">
                    <div 
                      v-for="event in day.events" 
                      :key="event.id" 
                      :class="['event', event.type]"
                      @click="viewEventDetails(event)"
                    >
                      {{ event.title }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Week View Calendar -->
        <div v-else-if="viewMode === 'week'" class="card">
          <div class="card-body p-0">
            <div class="week-calendar">
              <div class="week-header">
                <div class="time-column"></div>
                <div 
                  v-for="day in weekDays" 
                  :key="day.date" 
                  :class="['day-column-header', day.isToday ? 'today' : '']"
                >
                  <div class="day-name">{{ formatDayName(day.date) }}</div>
                  <div class="day-number">{{ formatDayNumber(day.date) }}</div>
                </div>
              </div>
              
              <div class="week-body">
                <div class="time-scale">
                  <div v-for="hour in hoursOfDay" :key="hour" class="time-slot">
                    {{ formatHour(hour) }}
                  </div>
                </div>
                
                <div class="week-grid">
                  <div v-for="day in weekDays" :key="day.date.toISOString()" class="day-column">
                    <div 
                      v-for="hour in hoursOfDay" 
                      :key="`${day.date.toISOString()}-${hour}`" 
                      class="hour-cell"
                    ></div>
                    
                    <div 
                      v-for="event in day.events" 
                      :key="event.id" 
                      :class="['week-event', event.type]"
                      :style="getEventStyle(event)"
                      @click="viewEventDetails(event)"
                    >
                      {{ event.title }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Day View Calendar -->
        <div v-else class="card">
          <div class="card-body p-0">
            <div class="day-calendar">
              <div class="day-header">
                <h3>{{ formatFullDate(currentDate) }}</h3>
              </div>
              
              <div class="day-body">
                <div class="time-scale">
                  <div v-for="hour in hoursOfDay" :key="hour" class="time-slot">
                    {{ formatHour(hour) }}
                  </div>
                </div>
                
                <div class="day-grid">
                  <div 
                    v-for="hour in hoursOfDay" 
                    :key="hour" 
                    class="hour-cell"
                  ></div>
                  
                  <div 
                    v-for="event in dayEvents" 
                    :key="event.id" 
                    :class="['day-event', event.type]"
                    :style="getEventStyle(event)"
                    @click="viewEventDetails(event)"
                  >
                    <div class="event-time">{{ formatEventTime(event) }}</div>
                    <div class="event-title">{{ event.title }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Upcoming Events List -->
        <div class="card mt-4">
          <div class="card-header">
            <h3 class="card-title">Upcoming Events</h3>
          </div>
          <div class="card-body">
            <div v-if="upcomingEvents.length === 0" class="empty-state">
              <p>No upcoming events scheduled for this work center.</p>
            </div>
            
            <div v-else class="upcoming-events">
              <div 
                v-for="event in upcomingEvents" 
                :key="event.id" 
                :class="['event-item', event.type]"
                @click="viewEventDetails(event)"
              >
                <div class="event-date">{{ formatEventDate(event) }}</div>
                <div class="event-info">
                  <div class="event-title">{{ event.title }}</div>
                  <div class="event-meta">{{ event.description }}</div>
                </div>
                <div class="event-actions">
                  <button class="btn btn-sm btn-outline-primary" @click.stop="viewEventDetails(event)">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Add Maintenance Modal -->
      <div v-if="showAddMaintenanceModal" class="modal-backdrop" @click="closeAddMaintenanceModal"></div>
      <div v-if="showAddMaintenanceModal" class="modal show" tabindex="-1" style="display: block;">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Schedule Maintenance</h5>
              <button type="button" class="close" @click="closeAddMaintenanceModal">
                <span>&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="saveMaintenanceSchedule">
                <div class="form-group">
                  <label for="maintenance-type">Maintenance Type</label>
                  <select id="maintenance-type" v-model="maintenanceForm.maintenance_type" class="form-control" required>
                    <option value="">Select Maintenance Type</option>
                    <option value="Preventive">Preventive Maintenance</option>
                    <option value="Corrective">Corrective Maintenance</option>
                    <option value="Predictive">Predictive Maintenance</option>
                    <option value="Routine">Routine Inspection</option>
                    <option value="Calibration">Calibration</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label for="planned-date">Planned Date</label>
                  <input type="date" id="planned-date" v-model="maintenanceForm.planned_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                  <label for="maintenance-status">Status</label>
                  <select id="maintenance-status" v-model="maintenanceForm.status" class="form-control" required>
                    <option value="Scheduled">Scheduled</option>
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                    <option value="Canceled">Canceled</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label for="maintenance-notes">Notes</label>
                  <textarea id="maintenance-notes" v-model="maintenanceForm.notes" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" @click="closeAddMaintenanceModal">Cancel</button>
                  <button type="submit" class="btn btn-primary" :disabled="isSaving">
                    {{ isSaving ? 'Saving...' : 'Save Maintenance' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Event Details Modal -->
      <div v-if="showEventDetailsModal" class="modal-backdrop" @click="closeEventDetailsModal"></div>
      <div v-if="showEventDetailsModal" class="modal show" tabindex="-1" style="display: block;">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Event Details</h5>
              <button type="button" class="close" @click="closeEventDetailsModal">
                <span>&times;</span>
              </button>
            </div>
            <div class="modal-body" v-if="selectedEvent">
              <div class="event-detail-item">
                <div class="detail-label">Title</div>
                <div class="detail-value">{{ selectedEvent.title }}</div>
              </div>
              
              <div class="event-detail-item">
                <div class="detail-label">Type</div>
                <div class="detail-value">
                  <span :class="['badge', selectedEvent.type === 'maintenance' ? 'badge-warning' : 'badge-primary']">
                    {{ selectedEvent.type === 'maintenance' ? 'Maintenance' : 'Operation' }}
                  </span>
                </div>
              </div>
              
              <div class="event-detail-item">
                <div class="detail-label">Date</div>
                <div class="detail-value">{{ formatFullDate(new Date(selectedEvent.start)) }}</div>
              </div>
              
              <div class="event-detail-item">
                <div class="detail-label">Time</div>
                <div class="detail-value">{{ formatEventTime(selectedEvent) }}</div>
              </div>
              
              <div class="event-detail-item">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                  <span :class="getStatusClass(selectedEvent.status)">
                    {{ selectedEvent.status }}
                  </span>
                </div>
              </div>
              
              <div class="event-detail-item">
                <div class="detail-label">Description</div>
                <div class="detail-value">{{ selectedEvent.description || 'No description available' }}</div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closeEventDetailsModal">Close</button>
              
              <template v-if="selectedEvent && selectedEvent.type === 'maintenance'">
                <button type="button" class="btn btn-primary" @click="editMaintenance(selectedEvent)">
                  <i class="fas fa-edit mr-1"></i> Edit
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, reactive, computed, onMounted, watch } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';
  
  export default {
    name: 'WorkCenterSchedule',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const workCenterId = route.params.id;
      
      const workCenter = reactive({
        workcenter_id: '',
        code: '',
        name: '',
        is_active: true
      });
      
      const isLoading = ref(true);
      const isSaving = ref(false);
      const viewMode = ref('month');
      const currentDate = ref(new Date());
      const showMaintenance = ref(true);
      const showOperations = ref(true);
      
      // Modals
      const showAddMaintenanceModal = ref(false);
      const showEventDetailsModal = ref(false);
      const selectedEvent = ref(null);
      
      // Form data
      const maintenanceForm = reactive({
        maintenance_type: '',
        planned_date: '',
        status: 'Scheduled',
        notes: ''
      });
      
      // Calendar data
      const events = ref([]);
      const maintenanceSchedules = ref([]);
      const workOrderOperations = ref([]);
      
      const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
      const hoursOfDay = Array.from({ length: 24 }, (_, i) => i);
      
      // Computed properties
      const formattedPeriod = computed(() => {
        const options = { month: 'long', year: 'numeric' };
        if (viewMode.value === 'day') {
          options.day = 'numeric';
        } else if (viewMode.value === 'week') {
          const firstDay = getFirstDayOfWeek(currentDate.value);
          const lastDay = new Date(firstDay);
          lastDay.setDate(lastDay.getDate() + 6);
          
          if (firstDay.getMonth() === lastDay.getMonth()) {
            return `${firstDay.getDate()} - ${lastDay.getDate()} ${new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(firstDay)}`;
          } else if (firstDay.getFullYear() === lastDay.getFullYear()) {
            return `${new Intl.DateTimeFormat('en-US', { month: 'short' }).format(firstDay)} ${firstDay.getDate()} - ${new Intl.DateTimeFormat('en-US', { month: 'short' }).format(lastDay)} ${lastDay.getDate()}, ${lastDay.getFullYear()}`;
          } else {
            return `${new Intl.DateTimeFormat('en-US', { month: 'short', year: 'numeric' }).format(firstDay)} - ${new Intl.DateTimeFormat('en-US', { month: 'short', year: 'numeric' }).format(lastDay)}`;
          }
        }
        
        return new Intl.DateTimeFormat('en-US', options).format(currentDate.value);
      });
      
      const calendarDays = computed(() => {
        if (viewMode.value !== 'month') return [];
        
        const year = currentDate.value.getFullYear();
        const month = currentDate.value.getMonth();
        
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        
        const daysInMonth = lastDay.getDate();
        const startingDayOfWeek = firstDay.getDay();
        
        // Get last few days of previous month to fill first week
        const daysFromPrevMonth = startingDayOfWeek;
        const prevMonthLastDay = new Date(year, month, 0).getDate();
        
        // Get first few days of next month to complete last week
        const daysFromNextMonth = 42 - (daysFromPrevMonth + daysInMonth); // 42 = 6 weeks * 7 days
        
        const days = [];
        
        // Add days from previous month
        for (let i = daysFromPrevMonth - 1; i >= 0; i--) {
          const date = new Date(year, month - 1, prevMonthLastDay - i);
          days.push({
            date,
            isCurrentMonth: false,
            isToday: isSameDay(date, new Date()),
            events: getEventsForDay(date),
            hasEvents: hasEventsOnDay(date)
          });
        }
        
        // Add days from current month
        for (let i = 1; i <= daysInMonth; i++) {
          const date = new Date(year, month, i);
          days.push({
            date,
            isCurrentMonth: true,
            isToday: isSameDay(date, new Date()),
            events: getEventsForDay(date),
            hasEvents: hasEventsOnDay(date)
          });
        }
        
        // Add days from next month
        for (let i = 1; i <= daysFromNextMonth; i++) {
          const date = new Date(year, month + 1, i);
          days.push({
            date,
            isCurrentMonth: false,
            isToday: isSameDay(date, new Date()),
            events: getEventsForDay(date),
            hasEvents: hasEventsOnDay(date)
          });
        }
        
        return days;
      });
      
      const weekDays = computed(() => {
        if (viewMode.value !== 'week') return [];
        
        const firstDayOfWeek = getFirstDayOfWeek(currentDate.value);
        const days = [];
        
        for (let i = 0; i < 7; i++) {
          const date = new Date(firstDayOfWeek);
          date.setDate(date.getDate() + i);
          
          days.push({
            date,
            isToday: isSameDay(date, new Date()),
            events: getEventsForDay(date)
          });
        }
        
        return days;
      });
      
      const dayEvents = computed(() => {
        return getEventsForDay(currentDate.value);
      });
      
      const upcomingEvents = computed(() => {
        const now = new Date();
        const filteredEvents = events.value.filter(event => {
          const eventDate = new Date(event.start);
          return eventDate >= now;
        });
        
        // Sort by date (nearest first)
        return filteredEvents.sort((a, b) => {
          return new Date(a.start) - new Date(b.start);
        }).slice(0, 5); // Limit to 5 events
      });
      
      // Methods
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
          await Promise.all([
            loadMaintenanceData(),
            loadOperationsData()
          ]);
          
          processEvents();
        } catch (error) {
          console.error('Error loading work center:', error);
          alert('Failed to load work center details. Please try again later.');
          router.push('/manufacturing/work-centers');
        } finally {
          isLoading.value = false;
        }
      };
      
      const loadMaintenanceData = async () => {
        try {
          const response = await axios.get(`/manufacturing/work-centers/${workCenterId}/maintenance-schedules`);
          maintenanceSchedules.value = response.data.data;
        } catch (error) {
          console.error('Error loading maintenance schedules:', error);
        }
      };
      
      const loadOperationsData = async () => {
        try {
          // This endpoint might need adjustment based on your API structure
          const response = await axios.get(`/manufacturing/work-centers/${workCenterId}/operations`);
          workOrderOperations.value = response.data.data || [];
        } catch (error) {
          console.error('Error loading work order operations:', error);
          // Fallback to empty array if endpoint doesn't exist or returns error
          workOrderOperations.value = [];
        }
      };
      
      const processEvents = () => {
        const allEvents = [];
        
        // Process maintenance schedules
        if (maintenanceSchedules.value?.length) {
          maintenanceSchedules.value.forEach(schedule => {
            allEvents.push({
              id: `maintenance-${schedule.schedule_id}`,
              type: 'maintenance',
              title: `${schedule.maintenance_type} Maintenance`,
              start: new Date(schedule.planned_date),
              end: new Date(new Date(schedule.planned_date).getTime() + 2 * 60 * 60 * 1000), // Default 2 hours duration
              status: schedule.status,
              description: schedule.notes || `Scheduled ${schedule.maintenance_type} maintenance`,
              rawData: schedule
            });
          });
        }
        
        // Process work order operations
        if (workOrderOperations.value?.length) {
          workOrderOperations.value.forEach(operation => {
            if (operation.scheduled_start && operation.scheduled_end) {
              allEvents.push({
                id: `operation-${operation.operation_id}`,
                type: 'operation',
                title: operation.operation_name,
                start: new Date(operation.scheduled_start),
                end: new Date(operation.scheduled_end),
                status: operation.status,
                description: `Work Order: ${operation.work_order?.wo_number || 'N/A'}`,
                rawData: operation
              });
            }
          });
        }
        
        events.value = allEvents;
      };
      
      const previousPeriod = () => {
        const date = new Date(currentDate.value);
        
        if (viewMode.value === 'month') {
          date.setMonth(date.getMonth() - 1);
        } else if (viewMode.value === 'week') {
          date.setDate(date.getDate() - 7);
        } else if (viewMode.value === 'day') {
          date.setDate(date.getDate() - 1);
        }
        
        currentDate.value = date;
      };
      
      const nextPeriod = () => {
        const date = new Date(currentDate.value);
        
        if (viewMode.value === 'month') {
          date.setMonth(date.getMonth() + 1);
        } else if (viewMode.value === 'week') {
          date.setDate(date.getDate() + 7);
        } else if (viewMode.value === 'day') {
          date.setDate(date.getDate() + 1);
        }
        
        currentDate.value = date;
      };
      
      const goToToday = () => {
        currentDate.value = new Date();
      };
      
      const getFirstDayOfWeek = (date) => {
        const d = new Date(date);
        const day = d.getDay(); // 0 for Sunday, 1 for Monday, etc.
        
        // Go to previous Sunday
        d.setDate(d.getDate() - day);
        
        return d;
      };
      
      const isSameDay = (date1, date2) => {
        return date1.getFullYear() === date2.getFullYear() &&
               date1.getMonth() === date2.getMonth() &&
               date1.getDate() === date2.getDate();
      };
      
      const getEventsForDay = (date) => {
        const filteredEvents = events.value.filter(event => {
          if (!showMaintenance.value && event.type === 'maintenance') return false;
          if (!showOperations.value && event.type === 'operation') return false;
          
          const eventDate = new Date(event.start);
          return isSameDay(eventDate, date);
        });
        
        return filteredEvents;
      };
      
      const hasEventsOnDay = (date) => {
        return getEventsForDay(date).length > 0;
      };
      
      const getEventStyle = (event) => {
        const startDate = new Date(event.start);
        const endDate = new Date(event.end);
        
        // Calculate position and height based on time
        const startHour = startDate.getHours() + startDate.getMinutes() / 60;
        const endHour = endDate.getHours() + endDate.getMinutes() / 60;
        const duration = endHour - startHour;
        
        return {
          top: `${startHour * 60}px`,
          height: `${duration * 60}px`
        };
      };
      
      const formatDayName = (date) => {
        return new Intl.DateTimeFormat('en-US', { weekday: 'short' }).format(date);
      };
      
      const formatDayNumber = (date) => {
        return date.getDate();
      };
      
      const formatHour = (hour) => {
        return hour === 0 || hour === 12 ? (hour === 0 ? '12 AM' : '12 PM') : 
               hour < 12 ? `${hour} AM` : `${hour - 12} PM`;
      };
      
      const formatFullDate = (date) => {
        return new Intl.DateTimeFormat('en-US', {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        }).format(date);
      };
      
      const formatEventTime = (event) => {
        const startTime = new Intl.DateTimeFormat('en-US', {
          hour: 'numeric',
          minute: '2-digit',
          hour12: true
        }).format(new Date(event.start));
        
        const endTime = new Intl.DateTimeFormat('en-US', {
          hour: 'numeric',
          minute: '2-digit',
          hour12: true
        }).format(new Date(event.end));
        
        return `${startTime} - ${endTime}`;
      };
      
      const formatEventDate = (event) => {
        return new Intl.DateTimeFormat('en-US', {
          month: 'short',
          day: 'numeric',
          hour: 'numeric',
          minute: '2-digit',
          hour12: true
        }).format(new Date(event.start));
      };
      
      const openAddMaintenanceModal = () => {
        // Reset form
        maintenanceForm.maintenance_type = '';
        maintenanceForm.planned_date = new Date().toISOString().split('T')[0]; // Today's date in YYYY-MM-DD format
        maintenanceForm.status = 'Scheduled';
        maintenanceForm.notes = '';
        
        showAddMaintenanceModal.value = true;
      };
      
      const closeAddMaintenanceModal = () => {
        showAddMaintenanceModal.value = false;
      };
      
      const saveMaintenanceSchedule = async () => {
        isSaving.value = true;
        
        try {
          const payload = {
            workcenter_id: workCenterId,
            maintenance_type: maintenanceForm.maintenance_type,
            planned_date: maintenanceForm.planned_date,
            status: maintenanceForm.status,
            notes: maintenanceForm.notes
          };
          
          const response = await axios.post('/manufacturing/maintenance-schedules', payload);
          
          // Add the new maintenance to the list and refresh events
          maintenanceSchedules.value.push(response.data.data);
          processEvents();
          
          // Close modal
          closeAddMaintenanceModal();
        } catch (error) {
          console.error('Error saving maintenance schedule:', error);
          alert('Failed to save maintenance schedule. Please try again later.');
        } finally {
          isSaving.value = false;
        }
      };
      
      const viewEventDetails = (event) => {
        selectedEvent.value = event;
        showEventDetailsModal.value = true;
      };
      
      const closeEventDetailsModal = () => {
        showEventDetailsModal.value = false;
        selectedEvent.value = null;
      };
      
      const editMaintenance = (event) => {
        // Close details modal
        closeEventDetailsModal();
        
        // Pre-fill form with event data
        const schedule = event.rawData;
        maintenanceForm.maintenance_type = schedule.maintenance_type;
        maintenanceForm.planned_date = new Date(schedule.planned_date).toISOString().split('T')[0];
        maintenanceForm.status = schedule.status;
        maintenanceForm.notes = schedule.notes || '';
        
        // Open maintenance modal
        showAddMaintenanceModal.value = true;
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
      
      // Watch for changes in view mode or filters to refresh events
      watch([viewMode, showMaintenance, showOperations], () => {
        processEvents();
      });
      
      onMounted(() => {
        loadWorkCenter();
      });
      
      return {
        workCenter,
        workCenterId,
        isLoading,
        isSaving,
        viewMode,
        currentDate,
        showMaintenance,
        showOperations,
        formattedPeriod,
        daysOfWeek,
        hoursOfDay,
        calendarDays,
        weekDays,
        dayEvents,
        upcomingEvents,
        showAddMaintenanceModal,
        showEventDetailsModal,
        selectedEvent,
        maintenanceForm,
        previousPeriod,
        nextPeriod,
        goToToday,
        formatDayName,
        formatDayNumber,
        formatHour,
        formatFullDate,
        formatEventTime,
        formatEventDate,
        getEventStyle,
        getStatusClass,
        openAddMaintenanceModal,
        closeAddMaintenanceModal,
        saveMaintenanceSchedule,
        viewEventDetails,
        closeEventDetailsModal,
        editMaintenance
      };
    }
  };
  </script>
  
  <style scoped>
  .work-center-schedule {
    padding: 1rem 0;
  }
  
  .schedule-header {
    margin-bottom: 2rem;
  }
  
  .breadcrumb-nav {
    color: var(--gray-500);
    font-size: 0.875rem;
    margin-top: 0.5rem;
  }
  
  .breadcrumb-item {
    color: var(--gray-600);
    text-decoration: none;
  }
  
  .breadcrumb-item:hover {
    color: var(--primary-color);
    text-decoration: underline;
  }
  
  .breadcrumb-separator {
    margin: 0 0.5rem;
    color: var(--gray-400);
  }
  
  .breadcrumb-current {
    color: var(--gray-700);
    font-weight: 500;
  }
  
  .calendar-navigation {
    display: flex;
    align-items: center;
    gap: 1rem;
  }
  
  .current-period {
    margin: 0;
    min-width: 200px;
    text-align: center;
  }
  
  .calendar-container {
    width: 100%;
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    overflow: hidden;
  }
  
  .calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background-color: var(--gray-100);
    border-bottom: 1px solid var(--gray-200);
  }
  
  .calendar-day-header {
    padding: 0.75rem;
    text-align: center;
    font-weight: 600;
    color: var(--gray-700);
  }
  
  .calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    grid-template-rows: repeat(6, minmax(100px, 1fr));
  }
  
  .calendar-day {
    border-right: 1px solid var(--gray-200);
    border-bottom: 1px solid var(--gray-200);
    padding: 0.5rem;
    position: relative;
    min-height: 100px;
  }
  
  .calendar-day:nth-child(7n) {
    border-right: none;
  }
  
  .calendar-day.other-month {
    background-color: var(--gray-50);
    color: var(--gray-500);
  }
  
  .calendar-day.today {
    background-color: var(--primary-bg);
  }
  
  .calendar-day.has-events .day-number {
    font-weight: 600;
    color: var(--primary-color);
  }
  
  .day-number {
    font-size: 1rem;
    margin-bottom: 0.5rem;
  }
  
  .day-events {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }
  
  .event {
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
  }
  
  .event.maintenance {
    background-color: var(--warning-bg);
    color: var(--warning-color);
    border-left: 3px solid var(--warning-color);
  }
  
  .event.operation {
    background-color: var(--primary-bg);
    color: var(--primary-color);
    border-left: 3px solid var(--primary-color);
  }
  
  /* Week View Styles */
  .week-calendar {
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    overflow: hidden;
    height: 600px;
  }
  
  .week-header {
    display: grid;
    grid-template-columns: 60px repeat(7, 1fr);
    background-color: var(--gray-100);
    border-bottom: 1px solid var(--gray-200);
  }
  
  .day-column-header {
    padding: 0.75rem;
    text-align: center;
    border-left: 1px solid var(--gray-200);
  }
  
  .day-column-header.today {
    background-color: var(--primary-bg);
  }
  
  .day-name {
    font-weight: 600;
    color: var(--gray-700);
  }
  
  .day-number {
    font-size: 1.25rem;
  }
  
  .week-body {
    display: grid;
    grid-template-columns: 60px repeat(7, 1fr);
    height: calc(100% - 56px);
    position: relative;
  }
  
  .time-scale {
    display: flex;
    flex-direction: column;
    border-right: 1px solid var(--gray-200);
  }
  
  .time-slot {
    height: 60px;
    padding: 0.25rem;
    text-align: right;
    font-size: 0.75rem;
    color: var(--gray-600);
    position: relative;
    border-bottom: 1px solid var(--gray-100);
  }
  
  .time-slot::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 100%;
    width: 100vw;
    height: 1px;
    background-color: var(--gray-100);
  }
  
  .week-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    height: 100%;
    position: relative;
  }
  
  .day-column {
    border-right: 1px solid var(--gray-200);
    position: relative;
  }
  
  .day-column:last-child {
    border-right: none;
  }
  
  .hour-cell {
    height: 60px;
    border-bottom: 1px solid var(--gray-100);
  }
  
  .week-event {
    position: absolute;
    left: 2px;
    right: 2px;
    padding: 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    overflow: hidden;
    cursor: pointer;
    z-index: 1;
  }
  
  .week-event.maintenance {
    background-color: var(--warning-bg);
    color: var(--warning-color);
    border-left: 3px solid var(--warning-color);
  }
  
  .week-event.operation {
    background-color: var(--primary-bg);
    color: var(--primary-color);
    border-left: 3px solid var(--primary-color);
  }
  
  /* Day View Styles */
  .day-calendar {
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    overflow: hidden;
    height: 600px;
  }
  
  .day-header {
    background-color: var(--gray-100);
    border-bottom: 1px solid var(--gray-200);
    padding: 0.75rem;
    text-align: center;
  }
  
  .day-body {
    display: grid;
    grid-template-columns: 60px 1fr;
    height: calc(100% - 56px);
    position: relative;
  }
  
  .day-grid {
    position: relative;
    height: 100%;
  }
  
  .day-event {
    position: absolute;
    left: 2px;
    right: 2px;
    padding: 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    overflow: hidden;
    cursor: pointer;
    z-index: 1;
  }
  
  .day-event.maintenance {
    background-color: var(--warning-bg);
    color: var(--warning-color);
    border-left: 3px solid var(--warning-color);
  }
  
  .day-event.operation {
    background-color: var(--primary-bg);
    color: var(--primary-color);
    border-left: 3px solid var(--primary-color);
  }
  
  .event-time {
    font-weight: 600;
    margin-bottom: 0.25rem;
  }
  
  /* Upcoming Events */
  .upcoming-events {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .event-item {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: background-color 0.2s;
  }
  
  .event-item:hover {
    background-color: var(--gray-50);
  }
  
  .event-item.maintenance {
    border-left: 4px solid var(--warning-color);
  }
  
  .event-item.operation {
    border-left: 4px solid var(--primary-color);
  }
  
  .event-date {
    min-width: 120px;
    font-size: 0.875rem;
    color: var(--gray-600);
  }
  
  .event-info {
    flex: 1;
  }
  
  .event-title {
    font-weight: 500;
    margin-bottom: 0.25rem;
  }
  
  .event-meta {
    font-size: 0.875rem;
    color: var(--gray-600);
  }
  
  .event-actions {
    display: flex;
    gap: 0.5rem;
  }
  
  /* Modal Styles */
  .modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 40;
  }
  
  .modal {
    z-index: 50;
  }
  
  /* Event Details */
  .event-detail-item {
    margin-bottom: 1rem;
  }
  
  .detail-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-500);
    margin-bottom: 0.25rem;
  }
  
  .empty-state {
    color: var(--gray-500);
    text-align: center;
    padding: 2rem;
  }
  </style>