<template>
  <div class="dashboard-page">
    <Head title="Dashboard" />
    <PageHeader title="Dashboard" pageTitle="Home" />

    <section class="dashboard-hero card border-0 overflow-hidden mb-4">
      <div class="card-body p-4 p-xl-5">
        <div class="row g-4 align-items-center">
          <div class="col-xl-7">
            <span class="dashboard-kicker">Operations Workspace</span>
            <h2 class="text-white">Welcome back, {{ firstName }}.</h2>
            <p class="dashboard-lead">
              Keep your daily tools close, check what needs attention, and jump
              straight into the modules you use most.
            </p>

            <div class="dashboard-role-row">
              <span v-for="role in roleChips" :key="role" class="dashboard-role-chip">
                {{ role }}
              </span>
            </div>

            <div class="dashboard-hero-actions">
              <Link href="/requests" class="btn btn-light dashboard-hero-btn">
                Open Requests
              </Link>
              <Link
                v-if="hasInventoryAccess"
                href="/inventory-stocks"
                class="btn dashboard-outline-btn"
              >
                Open Inventory
              </Link>
              <Link
                v-else-if="hasApprovalsAccess"
                href="/approvals"
                class="btn dashboard-outline-btn"
              >
                Review Approvals
              </Link>
            </div>
          </div>

          <div class="col-xl-5">
            <div class="dashboard-stat-grid">
              <article
                v-for="card in statusCards"
                :key="card.label"
                class="dashboard-stat-card"
              >
                <div class="dashboard-stat-icon" :style="{ background: card.iconBg, color: card.iconColor }">
                  <i :class="card.icon"></i>
                </div>
                <div>
                  <span class="dashboard-stat-label">{{ card.label }}</span>
                  <strong class="dashboard-stat-value">{{ card.value }}</strong>
                  <p class="dashboard-stat-note mb-0">{{ card.note }}</p>
                </div>
              </article>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div class="row g-4">
      <div class="col-xl-8">
        <section class="card border-0 shadow-sm h-100">
          <div class="card-body p-4">
            <div class="section-heading mb-4">
              <div>
                <span class="section-kicker">Quick Access</span>
                <h4 class="section-title mb-1">Jump into your workspaces</h4>
                <p class="section-copy mb-0">
                  Shortcuts are tailored to the roles already available on your account.
                </p>
              </div>
            </div>

            <div class="workspace-grid">
              <Link
                v-for="link in quickLinks"
                :key="link.title"
                :href="link.href"
                class="workspace-card"
                :style="{ '--workspace-accent': link.accent }"
              >
                <div class="workspace-icon">
                  <i :class="link.icon"></i>
                </div>
                <div>
                  <span class="workspace-kicker">{{ link.kicker }}</span>
                  <h5 class="workspace-title">{{ link.title }}</h5>
                  <p class="workspace-copy mb-0">{{ link.description }}</p>
                </div>
              </Link>
            </div>
          </div>
        </section>
      </div>

      <div class="col-xl-4">
        <div class="dashboard-side-stack">
          <section class="card border-0 shadow-sm">
            <div class="card-body p-4">
              <span class="section-kicker">Focus</span>
              <h4 class="section-title mb-3">What needs attention</h4>

              <div class="focus-list">
                <article
                  v-for="item in attentionItems"
                  :key="item.title"
                  class="focus-item"
                >
                  <div class="focus-badge" :class="item.tone">{{ item.badge }}</div>
                  <h5 class="focus-title">{{ item.title }}</h5>
                  <p class="focus-copy mb-0">{{ item.detail }}</p>
                  <Link v-if="item.href" :href="item.href" class="focus-link">
                    {{ item.cta }}
                  </Link>
                </article>
              </div>
            </div>
          </section>

          <section class="card border-0 shadow-sm">
            <div class="card-body p-4">
              <span class="section-kicker">Account</span>
              <h4 class="section-title mb-3">Signed in profile</h4>

              <div class="profile-panel">
                <div class="profile-avatar">
                  {{ initials }}
                </div>
                <div>
                  <h5 class="profile-name mb-1">{{ userName }}</h5>
                  <p class="profile-copy mb-1">{{ positionLabel }}</p>
                  <p class="profile-copy muted mb-0">{{ emailLabel }}</p>
                </div>
              </div>

              <div class="profile-status-grid">
                <div class="profile-status-card">
                  <span>Profile</span>
                  <strong>{{ profileStatus }}</strong>
                </div>
                <div class="profile-status-card">
                  <span>Security</span>
                  <strong>{{ securityStatus }}</strong>
                </div>
              </div>
            </div>
          </section>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/Shared/Components/PageHeader.vue';

export default {
  components: { Head, Link, PageHeader },
  computed: {
    user() {
      return this.$page.props.user || {};
    },
    roles() {
      return this.$page.props.roles || [];
    },
    hasApprovalsAccess() {
      return Boolean(this.$page.props.approvals?.has_access);
    },
    hasInventoryAccess() {
      return this.hasAnyRole(['Supply Officer', 'Employee', 'Administrator']);
    },
    firstName() {
      return this.user.firstname || this.user.name?.split(' ')[0] || 'there';
    },
    userName() {
      return this.user.name || this.user.username || 'Unknown User';
    },
    positionLabel() {
      return this.user.position || 'No position assigned';
    },
    emailLabel() {
      return this.user.email || 'No email on file';
    },
    initials() {
      const parts = String(this.userName)
        .split(' ')
        .filter(Boolean)
        .slice(0, 2);

      return parts.map((part) => part[0]).join('').toUpperCase() || 'U';
    },
    roleChips() {
      return this.roles.length ? this.roles.slice(0, 4) : ['Standard Access'];
    },
    profileStatus() {
      return this.$page.props.updateRequired ? 'Needs update' : 'Ready';
    },
    securityStatus() {
      return this.user.two_factor_enabled ? '2FA on' : '2FA off';
    },
    statusCards() {
      return [
        {
          label: 'Active Roles',
          value: this.roles.length || 1,
          note: 'Current account permissions',
          icon: 'ri-shield-user-line',
          iconBg: 'rgba(255, 255, 255, 0.18)',
          iconColor: '#ffffff',
        },
        {
          label: 'Workspaces',
          value: this.quickLinks.length,
          note: 'Fast launch shortcuts',
          icon: 'ri-apps-2-line',
          iconBg: 'rgba(255, 255, 255, 0.18)',
          iconColor: '#ffffff',
        },
        {
          label: 'Approvals',
          value: this.hasApprovalsAccess ? 'Enabled' : 'Limited',
          note: this.hasApprovalsAccess ? 'Review tasks available' : 'No approval queue right now',
          icon: 'ri-checkbox-circle-line',
          iconBg: 'rgba(255, 255, 255, 0.18)',
          iconColor: '#ffffff',
        },
        {
          label: 'Security',
          value: this.securityStatus,
          note: 'Account protection status',
          icon: 'ri-lock-password-line',
          iconBg: 'rgba(255, 255, 255, 0.18)',
          iconColor: '#ffffff',
        },
      ];
    },
    quickLinks() {
      const links = [
        {
          title: 'My Requests',
          kicker: 'Portal',
          description: 'Track submissions, continue drafts, and review recent workflow updates.',
          href: '/requests',
          icon: 'ri-file-list-3-line',
          accent: '#4b5b93',
        },
        {
          title: 'Daily Time Record',
          kicker: 'Portal',
          description: 'Check attendance logs and manage your daily time entries.',
          href: '/dtr',
          icon: 'ri-time-line',
          accent: '#0f766e',
        },
        this.hasApprovalsAccess
          ? {
              title: 'For Approval',
              kicker: 'Portal',
              description: 'Open requests waiting for your review and decision.',
              href: '/approvals',
              icon: 'ri-checkbox-circle-line',
              accent: '#ca8a04',
            }
          : null,
        this.hasInventoryAccess
          ? {
              title: 'Inventory',
              kicker: 'Supply',
              description: 'Manage stocks, receivings, and withdrawals from a single place.',
              href: '/inventory-stocks',
              icon: 'ri-stack-line',
              accent: '#4b5b93',
            }
          : null,
        this.hasAnyRole(['Supply Officer', 'Employee', 'Administrator'])
          ? {
              title: 'Inventory Dashboard',
              kicker: 'Supply',
              description: 'Review current stock levels, movement, and alerts.',
              href: '/inventory-dashboard',
              icon: 'ri-dashboard-line',
              accent: '#7c8fd1',
            }
          : null,
        this.hasAnyRole(['Procurement Staff', 'Procurement Officer', 'Administrator'])
          ? {
              title: 'Procurement',
              kicker: 'FAIMS',
              description: 'Open procurement dashboards, requests, and supplier tools.',
              href: '/faims/procurement-dashboard',
              icon: 'ri-briefcase-4-line',
              accent: '#8b5cf6',
            }
          : null,
        this.hasAnyRole(['Finance Staff', 'Finance Officer', 'Administrator'])
          ? {
              title: 'Finance',
              kicker: 'FAIMS',
              description: 'Monitor request flow, disbursements, and finance references.',
              href: '/faims/finance-dashboard',
              icon: 'ri-bank-card-line',
              accent: '#0891b2',
            }
          : null,
        this.hasAnyRole(['Human Resource Officer'])
          ? {
              title: 'Human Resource',
              kicker: 'People',
              description: 'Jump to employee records, DTR, payroll, and calendar tools.',
              href: '/humanresource',
              icon: 'ri-team-line',
              accent: '#db2777',
            }
          : null,
        this.hasAnyRole(['Administrator'])
          ? {
              title: 'User Management',
              kicker: 'Executive',
              description: 'Manage users, references, and administrator-only settings.',
              href: '/users',
              icon: 'ri-settings-3-line',
              accent: '#475569',
            }
          : null,
      ];

      return links.filter(Boolean);
    },
    attentionItems() {
      const items = [];

      if (this.$page.props.updateRequired) {
        items.push({
          badge: 'Action',
          title: 'Profile update required',
          detail: 'Some employee information still needs to be completed before everything is fully aligned.',
          tone: 'warning',
        });
      }

      if (this.$page.props.surveyRequired) {
        items.push({
          badge: 'Survey',
          title: 'Morale survey waiting',
          detail: 'A survey is available for you to answer from the shared workspace prompts.',
          tone: 'info',
          href: '/surveys',
          cta: 'Open survey',
        });
      }

      if (this.hasApprovalsAccess) {
        items.push({
          badge: 'Queue',
          title: 'Approval tools are available',
          detail: 'If new requests arrive, your review workspace is ready to go.',
          tone: 'success',
          href: '/approvals',
          cta: 'Review approvals',
        });
      }

      if (!this.user.two_factor_enabled) {
        items.push({
          badge: 'Security',
          title: 'Two-factor authentication is off',
          detail: 'Account access is working, but it could be better protected with an extra sign-in step.',
          tone: 'neutral',
        });
      }

      if (!items.length) {
        items.push({
          badge: 'Clear',
          title: 'Everything looks settled',
          detail: 'Your workspace is in a good state. Use the quick links to pick up where you left off.',
          tone: 'success',
        });
      }

      return items;
    },
  },
  methods: {
    hasAnyRole(roleNames) {
      return roleNames.some((role) => this.roles.includes(role));
    },
  },
};
</script>

<style scoped>
.dashboard-page {
  --dashboard-brand: #4b5b93;
  --dashboard-brand-deep: #374577;
  --dashboard-brand-soft: #eef1fb;
  --dashboard-ink: #182032;
  --dashboard-muted: #667085;
}

.dashboard-hero {
  background:
    radial-gradient(circle at top right, rgba(255, 255, 255, 0.2), transparent 32%),
    linear-gradient(135deg, var(--dashboard-brand) 0%, var(--dashboard-brand-deep) 100%);
  color: #fff;
  box-shadow: 0 26px 48px rgba(56, 69, 119, 0.22);
}

.dashboard-kicker,
.section-kicker,
.workspace-kicker {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.dashboard-kicker {
  padding: 8px 12px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  margin-bottom: 16px;
}

.dashboard-title {
  font-size: clamp(2rem, 2.6vw, 3rem);
  font-weight: 800;
  line-height: 1.05;
  margin-bottom: 12px;
}

.dashboard-lead {
  max-width: 640px;
  color: rgba(255, 255, 255, 0.82);
  font-size: 15px;
  margin-bottom: 18px;
}

.dashboard-role-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 22px;
}

.dashboard-role-chip {
  border-radius: 999px;
  padding: 8px 12px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.14);
  font-size: 12px;
  font-weight: 700;
}

.dashboard-hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.dashboard-hero-btn,
.dashboard-outline-btn {
  min-width: 160px;
  border-radius: 14px;
  padding: 11px 18px;
  font-weight: 700;
}

.dashboard-outline-btn {
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: #fff;
  background: rgba(255, 255, 255, 0.06);
}

.dashboard-outline-btn:hover,
.dashboard-outline-btn:focus {
  color: #fff;
  background: rgba(255, 255, 255, 0.12);
}

.dashboard-stat-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.dashboard-stat-card {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 14px;
  align-items: start;
  padding: 18px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.11);
  border: 1px solid rgba(255, 255, 255, 0.14);
  backdrop-filter: blur(8px);
}

.dashboard-stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.dashboard-stat-label {
  display: block;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.7);
  margin-bottom: 6px;
}

.dashboard-stat-value {
  display: block;
  font-size: 1.2rem;
  font-weight: 800;
  margin-bottom: 4px;
}

.dashboard-stat-note {
  color: rgba(255, 255, 255, 0.72);
  font-size: 12px;
}

.section-kicker {
  color: var(--dashboard-brand);
  margin-bottom: 8px;
}

.section-title {
  color: var(--dashboard-ink);
  font-weight: 800;
}

.section-copy {
  color: var(--dashboard-muted);
  font-size: 14px;
}

.dashboard-side-stack {
  display: grid;
  gap: 16px;
}

.workspace-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.workspace-card {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 14px;
  padding: 18px;
  border-radius: 22px;
  border: 1px solid #e7ebf4;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 255, 0.98)),
    linear-gradient(135deg, color-mix(in srgb, var(--workspace-accent) 12%, white), transparent);
  color: inherit;
  text-decoration: none;
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.workspace-card:hover {
  transform: translateY(-3px);
  border-color: color-mix(in srgb, var(--workspace-accent) 24%, white);
  box-shadow: 0 18px 30px rgba(15, 23, 42, 0.08);
}

.workspace-icon {
  width: 50px;
  height: 50px;
  border-radius: 18px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: color-mix(in srgb, var(--workspace-accent) 12%, white);
  color: var(--workspace-accent);
  font-size: 20px;
}

.workspace-kicker {
  color: var(--workspace-accent);
  margin-bottom: 8px;
}

.workspace-title {
  color: var(--dashboard-ink);
  font-weight: 800;
  margin-bottom: 6px;
}

.workspace-copy,
.focus-copy,
.profile-copy {
  color: var(--dashboard-muted);
  font-size: 13px;
  line-height: 1.55;
}

.focus-list {
  display: grid;
  gap: 14px;
}

.focus-item {
  padding: 16px;
  border-radius: 18px;
  border: 1px solid #e6ebf4;
  background: #fbfcff;
}

.focus-badge {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: 12px;
}

.focus-badge.warning {
  background: #fff4db;
  color: #a16207;
}

.focus-badge.info {
  background: #e0f2fe;
  color: #0369a1;
}

.focus-badge.success {
  background: #dcfce7;
  color: #15803d;
}

.focus-badge.neutral {
  background: #eef2ff;
  color: var(--dashboard-brand);
}

.focus-title,
.profile-name {
  color: var(--dashboard-ink);
  font-weight: 800;
}

.focus-link {
  display: inline-flex;
  margin-top: 12px;
  color: var(--dashboard-brand);
  font-weight: 700;
  text-decoration: none;
}

.profile-panel {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 16px;
  align-items: center;
  padding: 16px;
  border-radius: 20px;
  background: linear-gradient(180deg, var(--dashboard-brand-soft), #ffffff);
  margin-bottom: 16px;
}

.profile-avatar {
  width: 58px;
  height: 58px;
  border-radius: 20px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--dashboard-brand);
  color: #fff;
  font-weight: 800;
  font-size: 20px;
  box-shadow: 0 16px 30px rgba(75, 91, 147, 0.24);
}

.profile-copy.muted {
  color: #7b879b;
}

.profile-status-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.profile-status-card {
  padding: 14px;
  border-radius: 16px;
  border: 1px solid #e6ebf4;
  background: #fff;
}

.profile-status-card span {
  display: block;
  font-size: 12px;
  color: var(--dashboard-muted);
  margin-bottom: 6px;
}

.profile-status-card strong {
  color: var(--dashboard-ink);
  font-weight: 800;
}

@media (max-width: 991.98px) {
  .dashboard-stat-grid,
  .workspace-grid,
  .profile-status-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 575.98px) {
  .dashboard-title {
    font-size: 1.8rem;
  }

  .dashboard-hero-btn,
  .dashboard-outline-btn {
    width: 100%;
  }
}
</style>
