@props(['title'])
<header class="page-header">
    <div class="page-header-row">
        <h2>{{ $title }}</h2>
        <div class="content-topbar-user">
            <span class="user-badge">{{ auth()->user()->user_name ?? '' }}</span>
        </div>
    </div>
    <div class="header-line"></div>
</header>
