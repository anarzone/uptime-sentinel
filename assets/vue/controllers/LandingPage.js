import { ref, onMounted, defineComponent } from 'vue';

export default defineComponent({
    setup() {
        const stats = ref({
            total_monitors: 0,
            total_integrations: 0,
            avg_latency_ms: 0,
            uptime_percentage: 100,
            loading: true
        });

        const fetchStats = async () => {
            try {
                const response = await fetch('/api/landing-stats');
                const data = await response.json();
                stats.value = { ...data, loading: false };
            } catch (error) {
                console.error('Failed to fetch landing stats:', error);
            }
        };

        onMounted(fetchStats);

        return { stats };
    },
    template: `
    <div class="landing-page">
      <!-- Hero Section -->
      <section class="hero glass">
        <div class="container hero-content">
          <div class="hero-text">
            <h1 class="text-gradient animate-float">Global Vigilance, <br>Instant Awareness.</h1>
            <p class="hero-subtitle">Sentinels are watching your infrastructure 24/7/365. Experience the next generation of uptime monitoring.</p>
            <div class="hero-actions">
              <a href="/admin" class="btn-primary">Go to Dashboard</a>
              <a href="#performance" class="btn-secondary">View Performance</a>
            </div>
          </div>
          <div class="hero-visual">
            <div class="pulse-circle"></div>
            <div class="sentinel-logo">S</div>
          </div>
        </div>
      </section>

      <!-- Stats Section -->
      <section id="performance" class="stats-section container">
        <div class="section-header text-center">
            <h2 class="text-gradient">Real-Time Performance</h2>
            <p class="text-muted">High-scale monitoring powered by our multi-tier telemetry engine.</p>
        </div>
        
        <div class="stats-grid">
          <div class="stat-card glass-card">
            <span class="stat-label">GLOBAL REACH</span>
            <div class="stat-value">{{ stats.total_monitors.toLocaleString() }}+</div>
            <p class="stat-desc">Endpoints monitored globally</p>
          </div>
          
          <div class="stat-card glass-card">
            <span class="stat-label">AVERAGE SPEED</span>
            <div class="stat-value" :class="{ 'text-success': stats.avg_latency_ms < 200 }">
                {{ stats.avg_latency_ms }}ms
            </div>
            <p class="stat-desc">Global average response time</p>
          </div>
          
          <div class="stat-card glass-card">
            <span class="stat-label">INTEGRATIONS</span>
            <div class="stat-value">{{ stats.total_integrations }}</div>
            <p class="stat-desc">Supported notification channels</p>
          </div>

          <div class="stat-card glass-card">
            <span class="stat-label">UPTIME RATE</span>
            <div class="stat-value text-success">{{ stats.uptime_percentage }}%</div>
            <p class="stat-desc">System reliability (24h)</p>
          </div>
        </div>
      </section>

      <!-- Features Section -->
      <section class="features container">
        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-icon">🚀</div>
                <h3>Ultra-Fast Checks</h3>
                <p>Distributed monitoring nodes ensuring sub-second detection of any service interruption.</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon">📊</div>
                <h3>Deep Insights</h3>
                <p>Three-tier telemetry architecture providing instant analysis and long-term trends.</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon">🔔</div>
                <h3>Smart Alerts</h3>
                <p>Multi-level escalation policies to ensure the right people are notified at the right time.</p>
            </div>
        </div>
      </section>

      <footer class="landing-footer">
        <div class="container footer-content">
            <p>&copy; 2026 Uptime Sentinel. All infrastructure secured.</p>
            <div class="footer-links">
                <a href="/admin">Admin Access</a>
                <span class="dot-separator"></span>
                <p class="powered-by">Powered by Telemetry Tiered Engine</p>
            </div>
        </div>
      </footer>
    </div>
  `
});
