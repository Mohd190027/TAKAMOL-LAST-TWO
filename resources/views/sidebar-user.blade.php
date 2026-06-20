{{--
  layouts/sidebar-admin.blade.php
  Admin sidebar with full navigation and real-time badges.
  Variables:
    $activeNav — which nav-item to mark active: 'dashboard','meetings','volunteer','orders','projects'
--}}
<aside class="sidebar">
  <div class="sb-logo">
    <img src="{{ asset('images/logo.png.svg') }}" alt="تكامل" onerror="this.style.display='none'">
    <span>تكامل</span>
  </div>
  <nav class="sb-nav">
    <div class="sb-section">الرئيسية</div>
    <a href="{{ route('dashboard') }}" class="nav-item {{ ($activeNav??'')==='dashboard' ? 'active' : '' }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="7" height="7" rx="1.5"/>
        <rect x="14" y="3" width="7" height="7" rx="1.5"/>
        <rect x="3" y="14" width="7" height="7" rx="1.5"/>
        <rect x="14" y="14" width="7" height="7" rx="1.5"/>
      </svg>لوحة التحكم
    </a>

    <div class="sb-section">إدارة الأنشطة</div>
    <a class="nav-item {{ ($activeNav??'')==='meetings' ? 'active' : '' }}" id="nav-meetings"
       href="{{ route('meetings') }}" onclick="if(typeof showSection==='function'){showSection('meetings');return false;}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="4" width="18" height="18" rx="2"/>
        <path d="M16 2v4M8 2v4M3 10h18"/>
      </svg>الاجتماعات
    </a>
    <a class="nav-item {{ ($activeNav??'')==='volunteer' ? 'active' : '' }}" id="nav-volunteer" data-vol
       href="{{ route('volunteer') }}"
       onclick="if(typeof showSection==='function'){showSection('volunteer');return false;}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
      </svg>فرص التطوع
    </a>
    <a class="nav-item {{ ($activeNav??'')==='orders' ? 'active' : '' }}" id="nav-orders"
       href="{{ route('orders') }}"
       onclick="if(typeof showSection==='function'){showSection('orders');return false;}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 11l3 3L22 4"/>
        <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
      </svg>الطلبات<span class="nav-badge red" id="nb-reqs"></span>
    </a>
    <a class="nav-item {{ ($activeNav??'')==='projects' ? 'active' : '' }}" id="nav-projects"
       href="{{ route('joint-projects') }}"
       onclick="if(typeof showSection==='function'){showSection('projects');return false;}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
      </svg>المشاريع
    </a>

    <div class="sb-section">النظام</div>
    <a class="nav-item {{ ($activeNav??"")==='settings' ? 'active' : '' }}" href="{{ route('settings') }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="3"/>
        <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
      </svg>الإعدادات
    </a>
  </nav>

  <div class="sb-foot">
    <form method="POST" action="{{ route('logout') }}" style="margin:0">
      @csrf
      <button type="submit" class="nav-item logout-btn"
        style="width:100%;cursor:pointer;text-align:right;font-family:inherit;font-size:inherit">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
        </svg>تسجيل الخروج
      </button>
    </form>
  </div>
</aside>
