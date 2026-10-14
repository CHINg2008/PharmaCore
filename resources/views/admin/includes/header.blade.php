<!-- Header -->
<div class="header">
			
	<!-- Logo -->
	<div class="header-left">
		<a href="{{route('dashboard')}}" class="logo">
			<img src="@if(!empty(AppSettings::get('logo'))) {{asset('storage/'.AppSettings::get('logo'))}} @else{{asset('assets/img/pharmacore_logo.jpg')}} @endif" alt="Logo">
		</a>
		<a href="{{route('dashboard')}}" class="logo logo-small">
			<img src="{{asset('assets/img/pharmacore_favicon.jpg')}}" alt="Logo" width="30" height="30">
		</a>
	</div>
	<!-- /Logo -->
	
	<a href="javascript:void(0);" id="toggle_btn">
		<i class="fe fe-text-align-left"></i>
	</a>
	
	<!-- Mobile Menu Toggle -->
	<a class="mobile_btn" id="mobile_btn">
		<i class="fa fa-bars"></i>
	</a>
	<!-- /Mobile Menu Toggle -->
	
	<!-- Header Right Menu -->
	<ul class="nav user-menu">
		<li class="nav-item dropdown">
			<a href="#" data-target="#add_sales" title="make a sale" data-toggle="modal" class="dropdown-toggle nav-link">
				<i class="fas fa-clipboard"></i>
			</a>
		</li>

		@php
			if (function_exists('syncInventoryAlertNotifications')) {
				syncInventoryAlertNotifications();
			}
			$unreadNotifications = auth()->check() ? auth()->user()->unreadNotifications : collect([]);
			$unreadCount = $unreadNotifications->count();
			$displayNotifications = $unreadCount > 0 
				? $unreadNotifications 
				: (auth()->check() ? auth()->user()->notifications()->take(10)->get() : collect([]));
		@endphp

		<!-- Interactive Responsive Notifications Dropdown -->
		<li class="nav-item dropdown noti-dropdown">
			<a href="#" class="dropdown-toggle nav-link noti-bell-container" data-toggle="dropdown" aria-expanded="false" title="Notifications">
				<i class="fe fe-bell @if($unreadCount > 0) noti-bell-active text-warning @endif"></i>
				@if($unreadCount > 0)
					<span class="noti-badge-pill">{{ $unreadCount }}</span>
				@endif
			</a>
			<div class="dropdown-menu notifications dropdown-menu-right">
				<div class="topnav-dropdown-header d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
					<div class="d-flex align-items-center">
						<span class="notification-title font-weight-bold text-dark mb-0">System Alerts</span>
						@if($unreadCount > 0)
							<span class="badge badge-pill badge-danger ml-2 noti-unread-counter">{{ $unreadCount }} Unread</span>
						@elseif($displayNotifications->count() > 0)
							<span class="badge badge-pill badge-secondary ml-2 noti-unread-counter">Recent Alerts</span>
						@endif
					</div>
					@if($unreadCount > 0)
						<a href="javascript:void(0)" onclick="markNotificationsAsRead(event)" class="clear-noti text-primary" style="font-size: 0.8rem; font-weight: 600;">
							<i class="fe fe-check mr-1"></i>Mark Read
						</a>
					@endif
				</div>

				<div class="noti-content">
					<ul class="notification-list">
						@forelse ($displayNotifications as $notification)
							@php
								$notifData = is_array($notification->data) ? $notification->data : (json_decode($notification->data, true) ?? []);
								$pName = $notifData['product_name'] ?? 'Medicine';
								$qty = $notifData['quantity'] ?? 0;
								$alertType = $notifData['alert_type'] ?? ($qty <= 0 ? 'Out of Stock' : 'Low Stock');
								$isRead = !is_null($notification->read_at);
								
								if (stripos($alertType, 'expired') !== false) {
									$redirectTarget = 'expired';
									$badgeColor = 'danger';
									$icon = 'fe-alert-octagon';
									$headline = 'Expired Product';
									$subtext = $pName . ' has reached or passed expiration date.';
								} elseif ($qty <= 0 || stripos($alertType, 'out') !== false) {
									$redirectTarget = 'outstock';
									$badgeColor = 'danger';
									$icon = 'fe-package';
									$headline = 'Out of Stock';
									$subtext = $pName . ' inventory is completely depleted (0 left).';
								} else {
									$redirectTarget = 'purchases';
									$badgeColor = 'warning';
									$icon = 'fe-alert-triangle';
									$headline = 'Low Stock Warning';
									$subtext = $pName . ' has only ' . $qty . ' units remaining in stock.';
								}
							@endphp
							<li class="notification-message @if($isRead) opacity-75 @endif">
								<a href="{{ route('read', ['id' => $notification->id, 'redirect' => $redirectTarget]) }}" class="d-flex align-items-start py-2 px-3">
									<div class="media mr-2">
										<span class="avatar avatar-sm bg-{{ $badgeColor }}-light text-{{ $badgeColor }} rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; min-width: 36px;">
											<i class="fe {{ $icon }}"></i>
										</span>
									</div>
									<div class="media-body" style="font-size: 0.83rem;">
										<div class="d-flex justify-content-between align-items-center">
											<h6 class="text-{{ $badgeColor }} font-weight-bold mb-1" style="font-size: 0.82rem;">{{ $headline }}</h6>
											<div class="d-flex align-items-center">
												@if(!$isRead)
													<span class="badge badge-pill badge-danger mr-1 noti-new-pill" style="font-size: 0.6rem; padding: 2px 5px;">New</span>
												@else
													<span class="badge badge-pill badge-light border text-muted mr-1" style="font-size: 0.6rem; padding: 2px 5px;">Read</span>
												@endif
												<small class="text-muted" style="font-size: 0.7rem;">{{ $notification->created_at ? $notification->created_at->diffForHumans(null, true) : 'Active' }}</small>
											</div>
										</div>
										<p class="noti-details text-dark mb-1" style="line-height: 1.3;">
											{{ $subtext }}
										</p>
										<span class="text-primary font-weight-bold" style="font-size: 0.74rem;">
											Resolve issue <i class="fe fe-arrow-right ml-1"></i>
										</span>
									</div>
								</a>
							</li>
						@empty
							<li class="text-center py-4 px-3">
								<div class="avatar avatar-md bg-success-light text-success mx-auto mb-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
									<i class="fe fe-check-circle" style="font-size: 1.4rem;"></i>
								</div>
								<h6 class="font-weight-bold mb-1 text-dark" style="font-size: 0.9rem;">All Caught Up!</h6>
								<small class="text-muted">No pending inventory, expiry, or stock alerts right now.</small>
							</li>
						@endforelse						
					</ul>
				</div>

				<div class="topnav-dropdown-footer d-flex justify-content-between align-items-center px-3 py-2 bg-light border-top" style="font-size: 0.8rem;">
					<a href="{{ route('outstock') }}" class="text-warning font-weight-bold">
						<i class="fe fe-package mr-1"></i> Out of Stock
					</a>
					<a href="{{ route('expired') }}" class="text-danger font-weight-bold">
						<i class="fe fe-alert-octagon mr-1"></i> Expired Items
					</a>
				</div>
			</div>
		</li>
		<!-- /Notifications -->
		
		<!-- User Menu -->
		<li class="nav-item dropdown has-arrow">
			<a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">
				<span class="user-img"><img class="rounded-circle" src="{{!empty(auth()->user()->avatar) ? asset('storage/users/'.auth()->user()->avatar): asset('assets/img/avatar.png')}}" width="31" alt="avatar"></span>
			</a>
			<div class="dropdown-menu">
				<div class="user-header">
					<div class="avatar avatar-sm">
						<img src="{{!empty(auth()->user()->avatar) ? asset('storage/users/'.auth()->user()->avatar): asset('assets/img/avatar.png')}}" alt="User Image" class="avatar-img rounded-circle">
					</div>
					<div class="user-text">
						<h6>{{auth()->user()->name}}</h6>
					</div>
				</div>
				
				<a class="dropdown-item" href="{{route('profile')}}">My Profile</a>
				@can('view-settings')<a class="dropdown-item" href="{{route('settings')}}">Settings</a>@endcan
				
				<a href="javascript:void(0)" class="dropdown-item">
					<form action="{{route('logout')}}" method="post">
					@csrf
					<button type="submit" class="btn">Logout</button>
				</form>
				</a>
			</div>
		</li>
		<!-- /User Menu -->
		
	</ul>
	<!-- /Header Right Menu -->
	
</div>
<!-- /Header -->

<!-- Responsive Styling & Script for Notifications -->
<style>
    .noti-bell-container {
        position: relative;
        padding: 0 15px !important;
        display: flex !important;
        align-items: center;
        height: 60px;
    }

    @keyframes bell-pulse {
        0% { transform: rotate(0); }
        10% { transform: rotate(16deg); }
        20% { transform: rotate(-16deg); }
        30% { transform: rotate(12deg); }
        40% { transform: rotate(-12deg); }
        50% { transform: rotate(6deg); }
        60% { transform: rotate(-6deg); }
        70% { transform: rotate(0); }
        100% { transform: rotate(0); }
    }

    .noti-bell-active {
        animation: bell-pulse 2.5s infinite ease-in-out;
        transform-origin: top center;
    }

    .noti-badge-pill {
        position: absolute;
        top: 12px;
        right: 8px;
        background: #ef4444 !important;
        color: #ffffff !important;
        font-size: 0.65rem;
        font-weight: 800;
        min-width: 18px;
        height: 18px;
        line-height: 15px;
        padding: 0 4px;
        border-radius: 9px;
        border: 2px solid #ffffff;
        text-align: center;
        box-shadow: 0 2px 6px rgba(239, 68, 68, 0.6);
        pointer-events: none;
    }

    .dropdown-menu.notifications {
        min-width: 350px;
        max-width: 390px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 15px 35px rgba(0,0,0,0.18);
        padding: 0;
        right: 0 !important;
        left: auto !important;
        overflow: hidden;
    }

    @media (max-width: 576px) {
        .dropdown-menu.notifications {
            min-width: 300px;
            max-width: 94vw;
            right: -60px !important;
        }
    }

    .notification-list {
        max-height: 350px;
        overflow-y: auto;
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .notification-message {
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s ease;
    }

    .notification-message:hover {
        background: #f8fafc;
    }

    .bg-danger-light {
        background-color: rgba(239, 68, 68, 0.12) !important;
    }

    .bg-warning-light {
        background-color: rgba(245, 158, 11, 0.12) !important;
    }

    .bg-success-light {
        background-color: rgba(16, 185, 129, 0.12) !important;
    }
</style>

<script>
    function markNotificationsAsRead(e) {
        if(e) e.preventDefault();
        fetch("{{ route('mark-as-read') }}", {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            const badge = document.querySelector('.noti-badge-pill');
            if (badge) badge.style.display = 'none';
            const bell = document.querySelector('.noti-bell-container i');
            if (bell) {
                bell.classList.remove('noti-bell-active', 'text-warning');
            }
            const clearBtn = document.querySelector('.clear-noti');
            if (clearBtn) clearBtn.style.display = 'none';
            const counterBadge = document.querySelector('.noti-unread-counter');
            if (counterBadge) {
                counterBadge.textContent = 'Recent Alerts';
                counterBadge.className = 'badge badge-pill badge-secondary ml-2 noti-unread-counter';
            }
            document.querySelectorAll('.noti-new-pill').forEach(el => {
                el.outerHTML = '<span class="badge badge-pill badge-light border text-muted mr-1" style="font-size: 0.6rem; padding: 2px 5px;">Read</span>';
            });
            document.querySelectorAll('.notification-message').forEach(el => {
                el.classList.add('opacity-75');
            });
        })
        .catch(err => {
            window.location.href = "{{ route('mark-as-read') }}";
        });
    }
</script>