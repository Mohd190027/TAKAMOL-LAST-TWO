{{--
  layouts/sidebar-admin.blade.php
  Admin sidebar — redesigned to match target UI with Takamol branding.
  Variables:
    $activeNav — 'dashboard','meetings','volunteer','orders','projects','settings'
--}}
@php
  $nav = $activeNav ?? '';
@endphp

<style>
/* ═══════════════════════════════════════════════════════════
   SIDEBAR-ADMIN — New Design matching target UI
   ═══════════════════════════════════════════════════════════ */

aside.sidebar {
  width: 260px !important;
  flex-shrink: 0;
  background: #0f1923 !important;
  display: flex !important;
  flex-direction: column !important;
  position: fixed !important;
  top: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  z-index: 50 !important;
  border-left: 1px solid rgba(255,255,255,0.06);
  overflow: hidden;
  font-family: 'Tajawal', sans-serif;
}

/* ── LOGO AREA ── */
aside.sidebar .sb-logo {
  padding: 24px 20px 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  position: relative;
  z-index: 1;
  flex-shrink: 0;
}

aside.sidebar .sb-logo-wordmark {
  font-size: 1.75rem;
  font-weight: 900;
  color: white;
  letter-spacing: -1px;
  line-height: 1;
  font-family: 'Tajawal', sans-serif;
}

aside.sidebar .sb-logo-wordmark span {
  color: #0ea5c9;
}

/* ── ADMIN PROFILE BAR ── */
aside.sidebar .sb-profile-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  flex-shrink: 0;
}

aside.sidebar .sb-profile-av {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: linear-gradient(135deg, #6d28d9, #4f46e5);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.82rem;
  font-weight: 900;
  color: white;
  flex-shrink: 0;
}

aside.sidebar .sb-profile-info { flex: 1; min-width: 0; }

aside.sidebar .sb-profile-name {
  font-size: 0.8rem;
  font-weight: 700;
  color: white;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.2;
}

aside.sidebar .sb-profile-role {
  font-size: 0.65rem;
  color: rgba(255,255,255,0.35);
  margin-top: 1px;
  font-weight: 500;
}

/* ── NAV ── */
aside.sidebar .sb-nav {
  flex: 1;
  padding: 6px 8px;
  overflow-y: auto;
  position: relative;
  z-index: 1;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

aside.sidebar .sb-nav::-webkit-scrollbar { display: none; }

/* ── SECTION LABEL ── */
aside.sidebar .sb-section {
  font-size: 0.6rem;
  font-weight: 700;
  color: rgba(255,255,255,0.22);
  padding: 14px 12px 4px;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  user-select: none;
  font-family: 'Tajawal', sans-serif;
}

/* ── NAV ITEM ── */
aside.sidebar .nav-item {
  display: flex !important;
  align-items: center !important;
  gap: 10px !important;
  padding: 9px 12px !important;
  border-radius: 8px !important;
  margin-bottom: 1px;
  font-size: 0.875rem !important;
  font-weight: 500 !important;
  color: rgba(255,255,255,0.45) !important;
  cursor: pointer;
  transition: all 0.18s ease !important;
  text-decoration: none !important;
  background: transparent !important;
  border: none !important;
  width: 100%;
  font-family: 'Tajawal', sans-serif !important;
  text-align: right;
  position: relative;
  box-shadow: none !important;
  -webkit-appearance: none;
  appearance: none;
}

aside.sidebar .nav-item:hover {
  background: rgba(255,255,255,0.06) !important;
  color: rgba(255,255,255,0.8) !important;
  transform: none !important;
}

aside.sidebar .nav-item.active {
  background: linear-gradient(90deg, rgba(14,165,201,0.25) 0%, rgba(14,165,201,0.12) 100%) !important;
  color: #38d9f0 !important;
  font-weight: 700 !important;
}

/* Active indicator bar on right */
aside.sidebar .nav-item.active::before {
  content: '';
  position: absolute;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 60%;
  border-radius: 3px 0 0 3px;
  background: #0ea5c9;
}

/* ── NAV ICON ── */
aside.sidebar .nav-icon {
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

aside.sidebar .nav-icon svg {
  width: 16px !important;
  height: 16px !important;
  opacity: 0.55;
  transition: opacity 0.18s ease;
  flex-shrink: 0;
  filter: none !important;
}

aside.sidebar .nav-item.active .nav-icon svg {
  opacity: 1;
}

aside.sidebar .nav-item:hover .nav-icon svg {
  opacity: 0.85;
}

/* ── NAV BADGE ── */
aside.sidebar .nav-badge {
  margin-right: auto;
  background: rgba(14,165,201,0.2);
  border: 1px solid rgba(14,165,201,0.3);
  border-radius: 20px;
  padding: 1px 7px;
  font-size: 0.63rem;
  font-weight: 800;
  color: #38d9f0;
  min-width: 18px;
  text-align: center;
  font-family: 'Tajawal', sans-serif;
}

aside.sidebar .nav-badge:empty { display: none !important; }
aside.sidebar .nav-badge.red {
  background: rgba(239,68,68,0.2);
  border-color: rgba(239,68,68,0.3);
  color: #f87171;
}

/* ── DIVIDER ── */
aside.sidebar .sb-divider {
  height: 1px;
  background: rgba(255,255,255,0.05);
  margin: 4px 10px;
}

/* ── FOOTER ── */
aside.sidebar .sb-foot {
  padding: 8px 8px 14px !important;
  border-top: 1px solid rgba(255,255,255,0.06) !important;
  position: relative;
  z-index: 1;
  flex-shrink: 0;
}

/* ── LOGOUT BUTTON ── */
aside.sidebar .sb-foot .logout-btn {
  color: rgba(255,255,255,0.38) !important;
  background: transparent !important;
  border: none !important;
  -webkit-appearance: none !important;
  appearance: none !important;
  font-size: 0.875rem !important;
  font-family: 'Tajawal', sans-serif !important;
  box-shadow: none !important;
}

aside.sidebar .sb-foot .logout-btn:hover {
  background: rgba(239,68,68,0.1) !important;
  color: #f87171 !important;
  transform: none !important;
  box-shadow: none !important;
}

/* ── MAIN OFFSET ── */
.main {
  flex: 1;
  min-width: 0;
  margin-right: 260px !important;
  width: calc(100vw - 260px) !important;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  overflow-x: hidden;
}

/* ── RESPONSIVE ── */
@media (max-width: 900px) {
  aside.sidebar { width: 200px !important; }
  .main { margin-right: 200px; width: calc(100vw - 200px); }
}
</style>

<aside class="sidebar">

  {{-- ── LOGO ── --}}
  <div class="sb-logo">
    <img src="{{ asset('images/logo1.png') }}" alt="Takamol Logo" style="max-width: 210px; width: 100%; height: auto; object-fit: contain;">
  </div>


  {{-- ── NAVIGATION ── --}}
  <nav class="sb-nav">

    <div class="sb-section">الرئيسية</div>

    <a href="{{ route('dashboard') }}"
       class="nav-item {{ $nav==='dashboard' ? 'active' : '' }}"
       id="nav-dashboard">
      <div class="nav-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="3" width="7" height="7" rx="1.5"/>
          <rect x="14" y="3" width="7" height="7" rx="1.5"/>
          <rect x="3" y="14" width="7" height="7" rx="1.5"/>
          <rect x="14" y="14" width="7" height="7" rx="1.5"/>
        </svg>
      </div>
      لوحة التحكم
    </a>

    <div class="sb-section">إدارة الأنشطة</div>

    <a class="nav-item {{ $nav==='meetings' ? 'active' : '' }}" id="nav-meetings"
       href="{{ route('meetings') }}">
      <div class="nav-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="18" rx="2"/>
          <path d="M16 2v4M8 2v4M3 10h18"/>
        </svg>
      </div>
      الاجتماعات
    </a>

    <a class="nav-item {{ $nav==='volunteer' ? 'active' : '' }}" id="nav-volunteer" data-vol
       href="{{ route('volunteer') }}"
       onclick="if(typeof showSection==='function'){showSection('volunteer');return false;}">
      <div class="nav-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
        </svg>
      </div>
      فرص التطوع
      <span class="nav-badge red" id="nb-reqs"></span>
    </a>

    <a class="nav-item {{ $nav==='orders' ? 'active' : '' }}" id="nav-orders"
       href="{{ route('orders') }}"
       onclick="if(typeof showSection==='function'){showSection('orders');return false;}">
      <div class="nav-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 11l3 3L22 4"/>
          <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
        </svg>
      </div>
      الطلبات
    </a>

    <a class="nav-item {{ $nav==='projects' ? 'active' : '' }}" id="nav-projects"
       href="{{ route('joint-projects') }}"
       onclick="if(typeof showSection==='function'){showSection('projects');return false;}">
      <div class="nav-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
          <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
          <line x1="12" y1="22.08" x2="12" y2="12"/>
        </svg>
      </div>
      المشاريع
    </a>


    <div class="sb-section">النظام</div>

    <a class="nav-item {{ $nav==='settings' ? 'active' : '' }}"
       href="{{ route('settings') }}">
      <div class="nav-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="8" r="4"/>
          <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
        </svg>
      </div>
      الملف الشخصي
    </a>

  </nav>

  {{-- ── FOOTER LOGOUT ── --}}
  <div class="sb-foot">
    <form method="POST" action="{{ route('logout') }}" style="margin:0">
      @csrf
      <button type="submit" class="nav-item logout-btn" style="width:100%;cursor:pointer;">
        <div class="nav-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
          </svg>
        </div>
        تسجيل الخروج
      </button>
    </form>
  </div>

</aside>
