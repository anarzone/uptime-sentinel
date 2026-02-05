import { ref, reactive, onMounted, computed } from 'vue';

export default {
    setup() {
        const isCreateModalOpen = ref(false);
        const isAlertModalOpen = ref(false);
        const isLoading = ref(false);
        const activeMonitor = ref(null);
        const alertRules = ref([]);
        const escalationPolicies = ref([]);

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
            cooldownInterval: '1h'
        });

        const policyForm = reactive({
            level: 1,
            consecutiveFailures: 5,
            channel: 'email',
            target: ''
        });

        const openCreateModal = () => {
            isCreateModalOpen.value = true;
        };

        const closeCreateModal = () => {
            isCreateModalOpen.value = false;
        };

        const openAlertModal = async (monitor) => {
            activeMonitor.value = monitor;
            isAlertModalOpen.value = true;
            await fetchAlertConfig();
        };

        const closeAlertModal = () => {
            isAlertModalOpen.value = false;
            activeMonitor.value = null;
        };

        const fetchAlertConfig = async () => {
            if (!activeMonitor.value) return;
            isLoading.value = true;
            try {
                const [rulesRes, policiesRes] = await Promise.all([
                    fetch(`/api/alert-rules/monitor/${activeMonitor.value.id}`),
                    fetch(`/api/escalation-policies/monitor/${activeMonitor.value.id}`)
                ]);
                const rulesData = await rulesRes.json();
                const policiesData = await policiesRes.json();
                alertRules.value = rulesData.data || [];
                escalationPolicies.value = policiesData.data || [];
            } catch (e) {
                console.error('Failed to fetch alert config', e);
            } finally {
                isLoading.value = false;
            }
        };

        const createMonitor = async () => {
            isLoading.value = true;
            try {
                const res = await fetch('/api/monitors', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(monitorForm)
                });
                if (res.ok) {
                    closeCreateModal();
                    // Dispatch event for LiveComponent to refresh
                    window.dispatchEvent(new CustomEvent('monitor-created'));
                }
            } catch (e) {
                console.error('Create monitor failed', e);
            } finally {
                isLoading.value = false;
            }
        };

        const addAlertRule = async () => {
            try {
                const res = await fetch('/api/alert-rules', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        ...alertForm,
                        monitorId: activeMonitor.value.id
                    })
                });
                if (res.ok) {
                    await fetchAlertConfig();
                }
            } catch (e) {
                console.error('Add alert rule failed', e);
            }
        };

        const addPolicy = async () => {
            try {
                const res = await fetch('/api/escalation-policies', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        ...policyForm,
                        monitorId: activeMonitor.value.id
                    })
                });
                if (res.ok) {
                    await fetchAlertConfig();
                }
            } catch (e) {
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

        // Expose to window for Twig integration
        onMounted(() => {
            window.MonitorManager = {
                openCreateModal,
                openAlertModal
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
            createMonitor,
            addAlertRule,
            addPolicy,
            deleteAlertRule,
            deletePolicy,
            closeCreateModal,
            closeAlertModal
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
                        <div class="form-group">
                            <label>Friendly Name</label>
                            <input v-model="monitorForm.name" placeholder="My Awesome API" required class="glass-input">
                        </div>
                        <div class="form-row">
                            <div class="form-group flex-2">
                                <label>URL</label>
                                <input v-model="monitorForm.url" placeholder="https://api.example.com/health" required class="glass-input">
                            </div>
                            <div class="form-group flex-1">
                                <label>Method</label>
                                <select v-model="monitorForm.method" class="glass-input">
                                    <option>GET</option>
                                    <option>POST</option>
                                    <option>PUT</option>
                                    <option>HEAD</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Interval (sec)</label>
                                <input type="number" v-model.number="monitorForm.intervalSeconds" min="10" class="glass-input">
                            </div>
                            <div class="form-group">
                                <label>Timeout (sec)</label>
                                <input type="number" v-model.number="monitorForm.timeoutSeconds" min="1" class="glass-input">
                            </div>
                            <div class="form-group">
                                <label>Expected Status</label>
                                <input type="number" v-model.number="monitorForm.expectedStatusCode" class="glass-input">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" @click="closeCreateModal" class="btn">Cancel</button>
                            <button type="submit" class="btn-primary" :disabled="isLoading">
                                <span v-if="isLoading">Creating...</span>
                                <span v-else>Deploy Monitor</span>
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
                                            <div class="text-xs text-muted">Threshold: {{ rule.failureThreshold }} failures</div>
                                        </div>
                                        <button @click="deleteAlertRule(rule.id)" class="btn-icon danger">&times;</button>
                                    </div>
                                    <div v-if="alertRules.length === 0" class="empty-state">No active rules.</div>
                                </div>
                                
                                <form @submit.prevent="addAlertRule" class="add-form glass">
                                    <div class="form-row mini">
                                        <select v-model="alertForm.channel" class="glass-input small">
                                            <option value="email">Email</option>
                                            <option value="slack">Slack</option>
                                            <option value="webhook">Webhook</option>
                                        </select>
                                        <input v-model="alertForm.target" placeholder="Recipient/URL" required class="glass-input small">
                                        <button type="submit" class="btn-primary btn-sm">+</button>
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
                                    <div class="form-row mini">
                                        <input type="number" v-model.number="policyForm.consecutiveFailures" placeholder="Fails" class="glass-input small" title="Failures before escalation">
                                        <select v-model="policyForm.channel" class="glass-input small">
                                            <option value="email">Email</option>
                                            <option value="slack">Slack</option>
                                            <option value="webhook">Webhook</option>
                                        </select>
                                        <button type="submit" class="btn-primary btn-sm">+</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `
};
