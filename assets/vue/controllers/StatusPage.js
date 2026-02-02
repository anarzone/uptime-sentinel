import { ref, computed, onMounted, defineComponent } from 'vue';

export default defineComponent({
  setup() {
    const statusData = ref({
      system_status: 'loading',
      monitors: [],
      updated_at: ''
    });

    const systemStatusLabel = computed(() => {
      switch (statusData.value.system_status) {
        case 'operational': return 'All Systems Operational';
        case 'partial_outage': return 'Partial System Outage';
        case 'major_outage': return 'Major System Outage';
        case 'loading': return 'Fetching Status...';
        default: return 'System Status Unknown';
      }
    });

    const fetchStatus = async () => {
      try {
        const response = await fetch('/api/status');
        statusData.value = await response.json();
      } catch (error) {
        console.error('Failed to fetch status:', error);
        statusData.value.system_status = 'unknown';
      }
    };

    onMounted(() => {
      fetchStatus();
      setInterval(fetchStatus, 30000); // 30s auto-refresh
    });

    return {
      statusData,
      systemStatusLabel
    };
  },
  template: `
    <div class="status-page">
      <header class="status-header glass">
        <div class="container">
          <h1 class="text-gradient">SENTINEL STATUS</h1>
          <div class="overall-status" :class="statusData.system_status">
            <div class="status-dot"></div>
            {{ systemStatusLabel }}
          </div>
        </div>
      </header>

      <main class="container">
        <section class="monitors-grid">
          <div v-for="monitor in statusData.monitors" :key="monitor.id" class="monitor-card glass-card">
            <div class="monitor-info">
              <h3>{{ monitor.name }}</h3>
              <span class="badge" :class="'badge-' + monitor.status">
                {{ monitor.status.toUpperCase() }}
              </span>
            </div>
            <div class="monitor-metrics">
              <div class="metric">
                <span class="label">Avg Latency (24h)</span>
                <span class="value">{{ monitor.latency_avg_24h }}ms</span>
              </div>
            </div>
          </div>
        </section>

        <footer class="status-footer">
          <p>Last updated: {{ statusData.updated_at }}</p>
          <p>Powered by Uptime Sentinel Telemetry Engine</p>
        </footer>
      </main>
    </div>
  `
});
