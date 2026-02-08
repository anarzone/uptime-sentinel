import { ref, reactive, onMounted, computed } from 'vue';

export default {
    setup() {
        const isCreateModalOpen = ref(false);
        const isAlertModalOpen = ref(false);
        const isLoading = ref(false);
        const activeMonitor = ref(null);
        const alertRules = ref([]);
        const escalationPolicies = ref([]);
        const isAddItemModalOpen = ref(false);
        const addItemType = ref(null); // 'alert' or 'policy'

        const monitorForm = reactive({
            name: '',
            url: '',
            method: 'GET',
            intervalSeconds: 60,
            timeoutSeconds: 5,
            expectedStatusCode: 200,
            headers: {},
            body: ''
        });

        const alertForm = reactive({
            channel: 'email',
            target: '',
            failureThreshold: 3,
            type: 'failure',
            cooldownInterval: 'PT1H'
        });

        const policyForm = reactive({
            level: 1,
            consecutiveFailures: 5,
            channel: 'email',
            target: ''
        });

        const errors = reactive({
            monitor: {},
            alert: {},
            policy: {}
        });

        const clearErrors = (form) => {
            errors[form] = {};
        };

        const handleApiError = async (res, form) => {
            try {
                const data = await res.json();
                if (res.status === 422) {
                    if (data.violations) {
                        Object.entries(data.violations).forEach(([propertyPath, message]) => {
                            errors[form][propertyPath] = message;
                        });
                    } else if (data.detail) {
                        errors[form]._general = data.detail;
                    } else {
                        errors[form]._general = 'Validation failed. Please check the fields.';
                    }
                } else {
                    errors[form]._general = data.detail || data.error || 'An unexpected server error occurred.';
                }
            } catch (e) {
                errors[form]._general = 'The server returned an invalid response. Please try again later.';
            }
        };

        const createMonitor = async () => {
            clearErrors('monitor');
            isLoading.value = true;
            try {
                const payload = { ...monitorForm };
                if (payload.url && !payload.url.startsWith('https://')) {
                    payload.url = 'https://' + payload.url;
                }

                const res = await fetch('/api/monitors', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                if (res.ok) {
                    closeCreateModal();
                    window.dispatchEvent(new CustomEvent('monitor-created', { detail: { message: 'Monitor successfully deployed' } }));
                } else {
                    await handleApiError(res, 'monitor');
                }
            } catch (e) {
                if (e instanceof TypeError) {
                    errors.monitor._general = 'Network error. Please check your connection.';
                } else {
                    errors.monitor._general = 'An unexpected error occurred: ' + e.message;
                }
                console.error('Create monitor failed', e);
            } finally {
                isLoading.value = false;
            }
        };

        const addAlertRule = async () => {
            clearErrors('alert');
            try {
                const res = await fetch('/api/alert-rules', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        ...alertForm,
                        monitorId: selectedMonitorIds.value
                    })
                });
                if (res.ok) {
                    // If in focused mode, just close correctly
                    if (isAddItemModalOpen.value) {
                        closeAddItemModal();
                        // If we were in a context of a specific monitor (edit page), reload.
                        // If global, maybe reload page to show in list?
                        window.location.reload();
                    } else {
                        await fetchAlertConfig();
                        alertForm.target = ''; // Reset target
                    }
                } else {
                    await handleApiError(res, 'alert');
                }
            } catch (e) {
                if (e instanceof TypeError) {
                    errors.alert._general = 'Network error. Please check your connection.';
                } else {
                    errors.alert._general = 'Failed to add alert rule.';
                }
                console.error('Add alert rule failed', e);
            }
        };

        const addPolicy = async () => {
            clearErrors('policy');
            try {
                const res = await fetch('/api/escalation-policies', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        ...policyForm,
                        monitorId: selectedMonitorIds.value
                    })
                });
                if (res.ok) {
                    // If in focused mode, just close correctly
                    if (isAddItemModalOpen.value) {
                        closeAddItemModal();
                        window.location.reload();
                    } else {
                        await fetchAlertConfig();
                        policyForm.target = ''; // Reset target
                    }
                } else {
                    await handleApiError(res, 'policy');
                }
            } catch (e) {
                if (e instanceof TypeError) {
                    errors.policy._general = 'Network error. Please check your connection.';
                } else {
                    errors.policy._general = 'Failed to add escalation policy.';
                }
                console.error('Add policy failed', e);
            }
        };

        const deleteAlertRule = async (id) => {
            if (!confirm('Are you sure?')) return;
            await fetch(`/api/alert-rules/${id}`, { method: 'DELETE' });
            await fetchAlertConfig();
        };

        const deletePolicy = async (id) => {
            if (!confirm('Are you sure?')) return;
            await fetch(`/api/escalation-policies/${id}`, { method: 'DELETE' });
            await fetchAlertConfig();
        };

        const fetchAlertConfig = async () => {
            if (!activeMonitor.value) return;
            try {
                const [rulesRes, policiesRes] = await Promise.all([
                    fetch(`/api/alert-rules/monitor/${activeMonitor.value.id}`),
                    fetch(`/api/escalation-policies/monitor/${activeMonitor.value.id}`)
                ]);

                if (rulesRes.ok) {
                    const rulesData = await rulesRes.json();
                    alertRules.value = rulesData.data || [];
                }

                if (policiesRes.ok) {
                    const policiesData = await policiesRes.json();
                    escalationPolicies.value = policiesData.data || [];
                }
            } catch (e) {
                console.error('Failed to fetch alert configuration', e);
            }
        };

        const allMonitors = ref([]);
        const selectedMonitorIds = ref([]);

        const fetchAllMonitors = async () => {
            try {
                const res = await fetch('/api/monitors');
                if (res.ok) {
                    const data = await res.json();
                    allMonitors.value = data.data || [];
                }
            } catch (e) {
                console.error('Failed to fetch monitors', e);
            }
        };

        const openCreateModal = () => {
            clearErrors('monitor');
            isCreateModalOpen.value = true;
        };

        const closeCreateModal = () => {
            isCreateModalOpen.value = false;
        };

        const openAlertModal = async (monitor) => {
            activeMonitor.value = monitor;
            clearErrors('alert');
            clearErrors('policy');
            isAlertModalOpen.value = true;
            await fetchAlertConfig();
        };

        const closeAlertModal = () => {
            isAlertModalOpen.value = false;
            activeMonitor.value = null;
        };

        const openAddRuleModal = async (monitor = null) => {
            activeMonitor.value = monitor;
            selectedMonitorIds.value = monitor ? [monitor.id] : [];
            clearErrors('alert');
            addItemType.value = 'alert';
            isAddItemModalOpen.value = true;

            if (!monitor) {
                await fetchAllMonitors();
            }
        };

        const openAddPolicyModal = async (monitor = null) => {
            activeMonitor.value = monitor;
            selectedMonitorIds.value = monitor ? [monitor.id] : [];
            clearErrors('policy');
            addItemType.value = 'policy';
            isAddItemModalOpen.value = true;

            if (!monitor) {
                await fetchAllMonitors();
            }
        };

        const closeAddItemModal = () => {
            isAddItemModalOpen.value = false;
            addItemType.value = null;
            activeMonitor.value = null;
            selectedMonitorIds.value = [];
        };

        const alertTargetPlaceholder = computed(() => {
            switch (alertForm.channel) {
                case 'slack': return 'Webhook URL or Channel Name';
                case 'webhook': return 'https://api.example.com/alerts';
                default: return 'email@example.com';
            }
        });

        const policyTargetPlaceholder = computed(() => {
            switch (policyForm.channel) {
                case 'slack': return 'Webhook URL or Channel Name';
                case 'webhook': return 'https://api.example.com/alerts';
                default: return 'email@example.com';
            }
        });

        const isMonitorDropdownOpen = ref(false);
        const toggleMonitorDropdown = () => {
            isMonitorDropdownOpen.value = !isMonitorDropdownOpen.value;
        };

        const closeMonitorDropdown = () => {
            isMonitorDropdownOpen.value = false;
        };

        const selectedMonitorSummary = computed(() => {
            if (selectedMonitorIds.value.length === 0) return 'Select Monitors...';
            if (selectedMonitorIds.value.length === allMonitors.value.length) return 'All Monitors Selected';
            if (selectedMonitorIds.value.length === 1) {
                const m = allMonitors.value.find(m => m.id === selectedMonitorIds.value[0]);
                return m ? m.name : '1 Monitor Selected';
            }
            return `${selectedMonitorIds.value.length} Monitors Selected`;
        });

        // Expose to window for Twig integration
        onMounted(() => {
            window.MonitorManager = {
                openCreateModal,
                openAlertModal,
                openAddRuleModal,
                openAddPolicyModal
            };
        });

        return {
            isCreateModalOpen,
            isAlertModalOpen,
            isLoading,
            activeMonitor,
            alertRules,
            escalationPolicies,
            monitorForm,
            alertForm,
            policyForm,
            errors,
            createMonitor,
            addAlertRule,
            addPolicy,
            deleteAlertRule,
            deletePolicy,
            closeCreateModal,
            closeAlertModal,
            openAddRuleModal,
            openAddPolicyModal,
            closeAddItemModal,
            isAddItemModalOpen,
            addItemType,
            allMonitors,
            selectedMonitorIds,
            alertTargetPlaceholder,
            policyTargetPlaceholder,
            isMonitorDropdownOpen,
            toggleMonitorDropdown,
            closeMonitorDropdown,
            selectedMonitorSummary
        };
    },
    template: `
        <div>
            <!-- Create Monitor Modal -->
            <div v-if="isCreateModalOpen" class="modal-overlay animate-fade-in" @click.self="closeCreateModal">
                <div class="glass-card modal-content animate-scale-up">
                    <div class="modal-header">
                        <h2 class="text-gradient">Add New Monitor</h2>
                        <button class="btn-close" @click="closeCreateModal">&times;</button>
                    </div>
                    <form @submit.prevent="createMonitor" class="modal-body">
                        <div v-if="errors.monitor._general" class="alert-item badge-danger" style="margin-bottom: 1rem; padding: 0.75rem; border-radius: 8px;">
                            {{ errors.monitor._general }}
                        </div>

                        <div class="form-group">
                            <label>Friendly Name</label>
                            <input v-model="monitorForm.name" placeholder="My Awesome API" required class="glass-input" :class="{ invalid: errors.monitor.name }">
                            <span v-if="errors.monitor.name" class="text-error">{{ errors.monitor.name }}</span>
                        </div>
                        <div class="form-row">
                            <div class="form-group flex-2">
                                <label>URL</label>
                                <div class="input-group">
                                    <span class="input-group-text">https://</span>
                                    <input v-model="monitorForm.url" placeholder="api.example.com/health" required class="glass-input" :class="{ invalid: errors.monitor.url }">
                                </div>
                                <span v-if="errors.monitor.url" class="text-error">{{ errors.monitor.url }}</span>
                            </div>
                            <div class="form-group flex-1">
                                <label>Method</label>
                                <select v-model="monitorForm.method" class="glass-input" :class="{ invalid: errors.monitor.method }">
                                    <option>GET</option>
                                    <option>POST</option>
                                    <option>PUT</option>
                                    <option>HEAD</option>
                                </select>
                                <span v-if="errors.monitor.method" class="text-error">{{ errors.monitor.method }}</span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Interval (sec)</label>
                                <input type="number" v-model.number="monitorForm.intervalSeconds" min="10" class="glass-input" :class="{ invalid: errors.monitor.intervalSeconds }">
                                <span v-if="errors.monitor.intervalSeconds" class="text-error">{{ errors.monitor.intervalSeconds }}</span>
                                <small class="text-muted" style="display: block; margin-top: 0.25rem;">How often to check the URL.</small>
                            </div>
                            <div class="form-group">
                                <label>Timeout (sec)</label>
                                <input type="number" v-model.number="monitorForm.timeoutSeconds" min="1" class="glass-input" :class="{ invalid: errors.monitor.timeoutSeconds }">
                                <span v-if="errors.monitor.timeoutSeconds" class="text-error">{{ errors.monitor.timeoutSeconds }}</span>
                                <small class="text-muted" style="display: block; margin-top: 0.25rem;">Max time to wait for response.</small>
                            </div>
                            <div class="form-group">
                                <label>Expected Status</label>
                                <select v-model.number="monitorForm.expectedStatusCode" class="glass-input" :class="{ invalid: errors.monitor.expectedStatusCode }">
                                    <option value="200">200 - OK</option>
                                    <option value="201">201 - Created</option>
                                    <option value="204">204 - No Content</option>
                                    <option value="301">301 - Moved Permanently</option>
                                    <option value="302">302 - Found</option>
                                    <option value="400">400 - Bad Request</option>
                                    <option value="401">401 - Unauthorized</option>
                                    <option value="403">403 - Forbidden</option>
                                    <option value="404">404 - Not Found</option>
                                    <option value="500">500 - Internal Server Error</option>
                                    <option value="502">502 - Bad Gateway</option>
                                    <option value="503">503 - Service Unavailable</option>
                                    <option value="504">504 - Gateway Timeout</option>
                                </select>
                                <span v-if="errors.monitor.expectedStatusCode" class="text-error">{{ errors.monitor.expectedStatusCode }}</span>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" @click="closeCreateModal" class="btn btn-secondary">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="isLoading">
                                <span v-if="isLoading">Creating...</span>
                                <span v-else>Create Monitor</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Alert Configuration Modal -->
            <div v-if="isAlertModalOpen" class="modal-overlay animate-fade-in" @click.self="closeAlertModal">
                <div class="glass-card modal-content wide animate-scale-up">
                    <div class="modal-header">
                        <div>
                            <h2 class="text-gradient">Alerts & Policies</h2>
                            <p v-if="activeMonitor" class="text-muted">{{ activeMonitor.name }}</p>
                        </div>
                        <button class="btn-close" @click="closeAlertModal">&times;</button>
                    </div>
                    
                    <div class="modal-body tabs-container">
                        <div class="alert-grid">
                            <!-- Left: Alert Rules -->
                            <div class="alert-section">
                                <div class="section-header">
                                    <h3>Alert Rules</h3>
                                    <p class="text-xs">Immediate notifications when limits are breached.</p>
                                </div>
                                <div class="alert-list">
                                    <div v-for="rule in alertRules" :key="rule.id" class="alert-item glass">
                                        <div class="item-info">
                                            <span class="badge" :class="'badge-' + rule.channel">{{ rule.channel }}</span>
                                            <span class="text-sm">{{ rule.target }}</span>
                                            <div class="text-xs text-muted threshold-info">Threshold: {{ rule.failureThreshold }} failures</div>
                                        </div>
                                        <button @click="deleteAlertRule(rule.id)" class="btn-icon danger">&times;</button>
                                    </div>
                                    <div v-if="alertRules.length === 0" class="empty-state">No active rules.</div>
                                </div>
                                
                                <form @submit.prevent="addAlertRule" class="add-form glass">
                                    <div v-if="errors.alert._general" class="text-error" style="margin-bottom: 0.5rem;">{{ errors.alert._general }}</div>
                                    <div class="form-row">
                                        <div class="form-group compact">
                                            <label>Channel</label>
                                            <select v-model="alertForm.channel" class="glass-input" :class="{ invalid: errors.alert.channel }">
                                                <option value="email">Email</option>
                                                <option value="slack">Slack</option>
                                                <option value="webhook">Webhook</option>
                                            </select>
                                        </div>
                                        <div class="form-group flex-1">
                                            <label>Recipient / URL</label>
                                            <input v-model="alertForm.target" :placeholder="alertTargetPlaceholder" required class="glass-input" :class="{ invalid: errors.alert.target }">
                                            <span v-if="errors.alert.target" class="text-error">{{ errors.alert.target }}</span>
                                        </div>
                                        <div class="form-group" style="display: flex; flex-direction: column;">
                                            <label>&nbsp;</label>
                                            <button type="submit" class="btn btn-primary" style="width: 100%;">Add</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Right: Escalation Policies -->
                            <div class="alert-section">
                                <div class="section-header">
                                    <h3>Escalation Policies</h3>
                                    <p class="text-xs">Advanced handling for persistent downtime.</p>
                                </div>
                                <div class="alert-list">
                                    <div v-for="policy in escalationPolicies" :key="policy.id" class="alert-item glass">
                                        <div class="item-info">
                                            <span class="badge">Level {{ policy.level }}</span>
                                            <span class="text-sm">After {{ policy.consecutiveFailures }} fails</span>
                                            <div class="text-xs text-muted">{{ policy.channel }}: {{ policy.target }}</div>
                                        </div>
                                        <button @click="deletePolicy(policy.id)" class="btn-icon danger">&times;</button>
                                    </div>
                                    <div v-if="escalationPolicies.length === 0" class="empty-state">No policies set.</div>
                                </div>

                                <form @submit.prevent="addPolicy" class="add-form glass">
                                    <div v-if="errors.policy._general" class="text-error" style="margin-bottom: 0.5rem;">{{ errors.policy._general }}</div>
                                    <div class="form-row">
                                        <div class="form-group compact-sm">
                                            <label>
                                                Fails
                                                <div class="tooltip-container">
                                                    <span class="info-icon">ⓘ</span>
                                                    <span class="tooltip-text">How many consecutive failures before this escalation kicks in.</span>
                                                </div>
                                            </label>
                                            <input type="number" v-model.number="policyForm.consecutiveFailures" class="glass-input" :class="{ invalid: errors.policy.consecutiveFailures }">
                                        </div>
                                        <div class="form-group compact">
                                            <label>Channel</label>
                                            <select v-model="policyForm.channel" class="glass-input" :class="{ invalid: errors.policy.channel }">
                                                <option value="email">Email</option>
                                                <option value="slack">Slack</option>
                                                <option value="webhook">Webhook</option>
                                            </select>
                                        </div>
                                        <div class="form-group flex-1">
                                            <label>Target</label>
                                            <input v-model="policyForm.target" :placeholder="policyTargetPlaceholder" required class="glass-input" :class="{ invalid: errors.policy.target }">
                                            <span v-if="errors.policy.target" class="text-error">{{ policyForm.target }}</span>
                                        </div>
                                        <div class="form-group" style="display: flex; flex-direction: column;">
                                            <label>&nbsp;</label>
                                            <button type="submit" class="btn btn-primary" style="width: 100%;">Add</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Focused Add Item Modal -->
            <div v-if="isAddItemModalOpen" class="modal-overlay animate-fade-in" @click.self="closeAddItemModal">
                <div class="glass-card modal-content animate-scale-up" style="max-width: 500px;">
                    <div class="modal-header">
                        <div>
                            <h2 class="text-gradient">{{ addItemType === 'alert' ? 'Add Alert Rule' : 'Add Escalation Policy' }}</h2>
                            <p v-if="activeMonitor" class="text-muted">{{ activeMonitor.name }}</p>
                        </div>
                        <button class="btn-close" @click="closeAddItemModal">&times;</button>
                    </div>
                    
                    <div class="modal-body">
                         <!-- Global Monitor Selection -->
                         <div v-if="!activeMonitor" class="form-group" style="margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color);">
                            <label>Select Monitors ({{ selectedMonitorIds.length }})</label>
                            
                            <!-- Custom Multi-Select Dropdown -->
                            <div class="custom-select-wrapper" style="position: relative;">
                                <div 
                                    class="glass-input select-trigger" 
                                    @click="toggleMonitorDropdown"
                                    style="cursor: pointer; display: flex; justify-content: space-between; align-items: center;"
                                >
                                    <span>{{ selectedMonitorSummary }}</span>
                                    <span style="font-size: 0.8rem; opacity: 0.7;">▼</span>
                                </div>
                                
                                <div v-if="isMonitorDropdownOpen" class="glass-card animate-fade-in" style="position: absolute; top: 100%; left: 0; right: 0; z-index: 50; margin-top: 5px; max-height: 250px; overflow-y: auto; padding: 0.5rem; border: 1px solid var(--border-color); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); background: #161a23 !important;">
                                    <div style="display: flex; gap: 1rem; margin-bottom: 0.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); font-size: 0.8em; position: sticky; top: 0; background: inherit; z-index: 1;">
                                        <a href="#" @click.prevent="selectedMonitorIds = allMonitors.map(m => m.id)" style="color: var(--primary-color);">Select All</a>
                                        <a href="#" @click.prevent="selectedMonitorIds = []" style="color: var(--text-muted);">Select None</a>
                                        <a href="#" @click.prevent="closeMonitorDropdown" style="margin-left: auto; color: var(--text-muted);">Close</a>
                                    </div>
                                    
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                        <label v-for="m in allMonitors" :key="m.id" style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 0.25rem;">
                                            <input type="checkbox" :value="m.id" v-model="selectedMonitorIds" style="accent-color: var(--primary-color); width: 16px; height: 16px;">
                                            <div style="overflow: hidden;">
                                                <div style="font-size: 0.9em; font-weight: 500;">{{ m.name }}</div>
                                                <div style="font-size: 0.75em; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ m.url }}</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div v-if="isMonitorDropdownOpen" @click="closeMonitorDropdown" style="position: fixed; inset: 0; z-index: 40; cursor: default;"></div>
                            </div>
                            <!-- End Custom Select -->
                         </div>

                         <!-- Alert Form -->
                         <form v-if="addItemType === 'alert'" @submit.prevent="addAlertRule" class="add-form">
                            <div v-if="errors.alert._general" class="text-error" style="margin-bottom: 0.5rem;">{{ errors.alert._general }}</div>
                            <div class="form-group">
                                <label>Channel</label>
                                <select v-model="alertForm.channel" class="glass-input" :class="{ invalid: errors.alert.channel }">
                                    <option value="email">Email</option>
                                    <option value="slack">Slack</option>
                                    <option value="webhook">Webhook</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Recipient / URL</label>
                                <input v-model="alertForm.target" :placeholder="alertTargetPlaceholder" required class="glass-input" :class="{ invalid: errors.alert.target }">
                                <span v-if="errors.alert.target" class="text-error">{{ errors.alert.target }}</span>
                            </div>
                            <div class="form-group">
                                <label>
                                    Failure Threshold
                                    <div class="tooltip-container">
                                        <span class="info-icon">ⓘ</span>
                                        <span class="tooltip-text">How many consecutive failures before this rule triggers.</span>
                                    </div>
                                </label>
                                <input type="number" v-model.number="alertForm.failureThreshold" min="1" class="glass-input">
                            </div>

                            <div class="modal-footer" style="padding-top: 1.5rem; justify-content: flex-end;">
                                <button type="button" @click="closeAddItemModal" class="btn btn-secondary">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add Rule</button>
                            </div>
                        </form>

                        <!-- Policy Form -->
                        <form v-if="addItemType === 'policy'" @submit.prevent="addPolicy" class="add-form">
                            <div v-if="errors.policy._general" class="text-error" style="margin-bottom: 0.5rem;">{{ errors.policy._general }}</div>
                            <div class="form-group">
                                <label>Level</label>
                                <input type="number" v-model.number="policyForm.level" min="1" class="glass-input" readonly>
                                <small class="text-muted">Currently fixed to Level 1 (Initial Escalation)</small>
                            </div>
                            <div class="form-group">
                                <label>
                                    Consecutive Failures
                                    <div class="tooltip-container">
                                        <span class="info-icon">ⓘ</span>
                                        <span class="tooltip-text">How many consecutive failures before this policy triggers.</span>
                                    </div>
                                </label>
                                <input type="number" v-model.number="policyForm.consecutiveFailures" min="1" class="glass-input" :class="{ invalid: errors.policy.consecutiveFailures }">
                                <small class="text-muted">How many failures before this triggers.</small>
                            </div>
                            <div class="form-group">
                                <label>Channel</label>
                                <select v-model="policyForm.channel" class="glass-input" :class="{ invalid: errors.policy.channel }">
                                    <option value="email">Email</option>
                                    <option value="slack">Slack</option>
                                    <option value="webhook">Webhook</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Target</label>
                                <input v-model="policyForm.target" :placeholder="policyTargetPlaceholder" required class="glass-input" :class="{ invalid: errors.policy.target }">
                                <span v-if="errors.policy.target" class="text-error">{{ errors.policy.target }}</span>
                            </div>

                            <div class="modal-footer" style="padding-top: 1.5rem; justify-content: flex-end;">
                                <button type="button" @click="closeAddItemModal" class="btn btn-secondary">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add Policy</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    `
};
