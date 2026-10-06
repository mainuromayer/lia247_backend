<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Privacy &amp; Data Erasure Portal | {{ $appName }}</title>
    @if(!empty($setting?->favicon))
        <link rel="icon" type="image/x-icon" href="{{ asset($setting->favicon) }}">
    @else
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: #f4f7fb;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Header */
        .header {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #0f172a;
        }

        .brand-icon {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, #10b981 0%, #0d9488 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.25);
            flex-shrink: 0;
        }

        .brand-icon img {
            max-width: 24px;
            max-height: 24px;
            object-fit: contain;
        }

        .brand-name {
            font-size: 17px;
            font-weight: 800;
            letter-spacing: -0.3px;
        }

        .app-portal-pill {
            background: #d1fae5;
            color: #065f46;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 999px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .btn-signout {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-signout:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #0f172a;
        }

        /* Container */
        .main-container {
            width: 100%;
            max-width: 860px;
            margin: 32px auto 48px;
            padding: 0 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            flex-grow: 1;
        }

        /* Card Styles */
        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.03);
            overflow: hidden;
        }

        /* Profile Banner Card */
        .profile-card {
            position: relative;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #10b981 0%, #0284c7 100%);
        }

        .profile-body {
            padding: 24px 28px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .avatar-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0d9488 0%, #047857 100%);
            color: #ffffff;
            font-size: 22px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 6px 14px rgba(13, 148, 136, 0.25);
            overflow: hidden;
        }

        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .profile-name {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.3px;
        }

        .profile-email {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13.5px;
            color: #64748b;
        }

        .role-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #e0f2fe;
            color: #0284c7;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 3px 10px;
            border-radius: 999px;
            margin-top: 4px;
            width: fit-content;
        }

        /* Stats Cards Row */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-icon.green {
            background: #ecfdf5;
            color: #10b981;
        }

        .stat-icon.blue {
            background: #eff6ff;
            color: #3b82f6;
        }

        .stat-icon.purple {
            background: #faf5ff;
            color: #a855f7;
        }

        .stat-count {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
        }

        .stat-label {
            font-size: 12.5px;
            color: #64748b;
            font-weight: 500;
            margin-top: 2px;
        }

        /* Account Details Section */
        .details-card {
            padding: 24px 28px;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 18px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .detail-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 16px;
        }

        .detail-label {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            word-break: break-all;
        }

        /* Danger Zone Card */
        .danger-card {
            background: #fff5f5;
            border: 1.5px solid #fecaca;
            border-radius: 18px;
            padding: 26px 28px;
        }

        .danger-header {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 18px;
        }

        .danger-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #fee2e2;
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .danger-title {
            font-size: 17px;
            font-weight: 800;
            color: #991b1b;
            margin-bottom: 4px;
        }

        .danger-desc {
            font-size: 13px;
            color: #7f1d1d;
            line-height: 1.5;
        }

        .danger-bullets {
            list-style: none;
            margin-bottom: 22px;
            padding-left: 50px;
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .danger-bullets li {
            font-size: 12.5px;
            color: #7f1d1d;
            position: relative;
            line-height: 1.4;
        }

        .danger-bullets li::before {
            content: '•';
            position: absolute;
            left: -14px;
            color: #dc2626;
            font-size: 16px;
            line-height: 1;
        }

        .btn-delete-trigger {
            margin-left: 50px;
            background: #dc2626;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            padding: 12px 22px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 6px 16px rgba(220, 38, 38, 0.28);
            transition: all 0.2s;
        }

        .btn-delete-trigger:hover {
            background: #b91c1c;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(220, 38, 38, 0.38);
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(6px);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 100;
            animation: fadeIn 0.2s ease;
        }

        .modal-box {
            background: #ffffff;
            border-radius: 22px;
            max-width: 480px;
            width: 100%;
            padding: 32px 28px;
            text-align: center;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.4);
            animation: scaleIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal-warning-icon {
            width: 60px;
            height: 60px;
            background: #fee2e2;
            color: #dc2626;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .modal-desc {
            font-size: 13.5px;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 22px;
        }

        .modal-form {
            text-align: left;
        }

        .modal-input-group {
            margin-bottom: 20px;
        }

        .modal-input-group label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .modal-input-group input {
            width: 100%;
            height: 44px;
            background: #f8fafc;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            padding: 0 14px;
            font-size: 14px;
            font-weight: 500;
            outline: none;
            transition: border-color 0.2s;
        }

        .modal-input-group input:focus {
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15);
        }

        .modal-actions {
            display: flex;
            gap: 12px;
        }

        .btn-modal-cancel {
            flex: 1;
            height: 44px;
            background: #f1f5f9;
            color: #475569;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-modal-cancel:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .btn-modal-confirm {
            flex: 1;
            height: 44px;
            background: #dc2626;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-modal-confirm:hover {
            background: #b91c1c;
        }

        /* Footer */
        .footer {
            margin-top: auto;
            text-align: center;
            padding: 24px 16px 36px;
            font-size: 12px;
            color: #64748b;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .details-grid {
                grid-template-columns: 1fr;
            }
            .danger-bullets {
                padding-left: 0;
            }
            .btn-delete-trigger {
                margin-left: 0;
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="header">
        <div class="header-brand">
            <div class="brand-icon">
                @if(!empty($logoUrl))
                    <img src="{{ $logoUrl }}" alt="{{ $appName }}">
                @else
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="m9 12 2 2 4-4"/>
                    </svg>
                @endif
            </div>
            <span class="brand-name">{{ $appName }}</span>
            <span class="app-portal-pill">App Portal</span>
        </div>

        <form action="{{ route('account.delete.logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn-signout">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Sign Out
            </button>
        </form>
    </header>

    <!-- Main Container -->
    <main class="main-container">

        @if(session('error'))
            <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:14px 18px; border-radius:12px; font-size:13.5px; font-weight:600; display:flex; align-items:center; gap:10px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        <!-- Card 1: User Profile Header -->
        <section class="card profile-card">
            <div class="profile-body">
                <div class="avatar-circle">
                    @if(!empty($user->avatar) && filter_var($user->avatar, FILTER_VALIDATE_URL))
                        <img src="{{ $user->avatar }}" alt="{{ $user->name }}">
                    @else
                        {{ strtoupper(substr($user->name ?? 'User', 0, 2)) }}
                    @endif
                </div>

                <div class="profile-info">
                    <h1 class="profile-name">{{ $user->name ?? 'App User' }}</h1>
                    <div class="profile-email">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="16" x="2" y="4" rx="2"/>
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                        </svg>
                        {{ $user->email }}
                    </div>
                    <div>
                        <span class="role-pill">
                            • APP USER / {{ strtoupper(str_replace('_', ' ', $user->user_type ?? 'CUSTOMER')) }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Card 2: Stats Row -->
        <section class="stats-grid">
            <!-- Stat 1 -->
            <div class="stat-card">
                <div class="stat-icon green">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                        <path d="M9 14h6"/>
                        <path d="M9 18h6"/>
                        <path d="M9 10h6"/>
                    </svg>
                </div>
                <div>
                    <div class="stat-count">{{ $stats['stat1_count'] }}</div>
                    <div class="stat-label">{{ $stats['stat1_label'] }}</div>
                </div>
            </div>

            <!-- Stat 2 -->
            <div class="stat-card">
                <div class="stat-icon blue">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="20" height="14" x="2" y="5" rx="2"/>
                        <line x1="2" x2="22" y1="10" y2="10"/>
                    </svg>
                </div>
                <div>
                    <div class="stat-count">{{ $stats['stat2_count'] }}</div>
                    <div class="stat-label">{{ $stats['stat2_label'] }}</div>
                </div>
            </div>

            <!-- Stat 3 -->
            <div class="stat-card">
                <div class="stat-icon purple">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                    </svg>
                </div>
                <div>
                    <div class="stat-count">{{ $stats['stat3_count'] }}</div>
                    <div class="stat-label">{{ $stats['stat3_label'] }}</div>
                </div>
            </div>
        </section>

        <!-- Card 3: Account Details -->
        <section class="card details-card">
            <h2 class="section-header">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                Account Details
            </h2>

            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">First Name</div>
                    <div class="detail-value">{{ $firstName ?: '-' }}</div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Last Name</div>
                    <div class="detail-value">{{ $lastName ?: '-' }}</div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Email Address</div>
                    <div class="detail-value">{{ $user->email }}</div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Phone Number</div>
                    <div class="detail-value">{{ $user->phone ?: 'Not provided' }}</div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Account Code (UUID)</div>
                    <div class="detail-value">#{{ str_pad($user->id, 6, '0', STR_PAD_LEFT) }}</div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Registration Date</div>
                    <div class="detail-value">{{ $user->created_at ? $user->created_at->format('F d, Y') : 'N/A' }}</div>
                </div>
            </div>
        </section>

        <!-- Card 4: Delete Account Danger Zone -->
        <section class="danger-card">
            <div class="danger-header">
                <div class="danger-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                <div>
                    <h3 class="danger-title">Delete Account &amp; Purge All Data</h3>
                    <p class="danger-desc">
                        In accordance with Google Play Store &amp; Apple App Store user data regulations, you may permanently purge your user profile and all connected records from our servers.
                    </p>
                </div>
            </div>

            <ul class="danger-bullets">
                <li>All order history, booking receipts, and delivery tracking data</li>
                <li>Profile details, saved addresses, and uploaded identity documents</li>
                <li>Stored payment methods, wallet transactions, and active vouchers</li>
                <li>All chat communications with support, drivers, and merchants</li>
                <li>Your user account credentials, login tokens, and notification subscriptions</li>
            </ul>

            <button type="button" class="btn-delete-trigger" onclick="openDeleteModal()">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18"/>
                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                    <line x1="10" y1="11" x2="10" y2="17"/>
                    <line x1="14" y1="11" x2="14" y2="17"/>
                </svg>
                Delete Account &amp; All Records
            </button>
        </section>

    </main>

    <!-- Footer -->
    <footer class="footer">
        {{ $appName }} Mobile Platform &bull; Account Privacy &amp; Data Erasure Center
    </footer>

    <!-- Confirmation Modal -->
    <div id="deleteModal" class="modal-overlay" onclick="handleOverlayClick(event)">
        <div class="modal-box">
            <div class="modal-warning-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18"/>
                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                    <line x1="10" y1="11" x2="10" y2="17"/>
                    <line x1="14" y1="11" x2="14" y2="17"/>
                </svg>
            </div>

            <h2 class="modal-title">Confirm Account Deletion</h2>
            <p class="modal-desc">
                This action is permanent and cannot be undone. All your personal data and account records will be wiped immediately.
            </p>

            <form action="{{ route('account.delete.confirm') }}" method="POST" class="modal-form" id="confirmDeleteForm" onsubmit="handleDeleteSubmit(event)">
                @csrf

                <div class="modal-input-group">
                    <label for="modalPassword">Enter your password to authorize:</label>
                    <input type="password" id="modalPassword" name="password" placeholder="••••••••" required>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" id="btnConfirmDelete" class="btn-modal-confirm">Permanently Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openDeleteModal() {
            document.getElementById('deleteModal').style.display = 'flex';
            setTimeout(() => {
                document.getElementById('modalPassword').focus();
            }, 100);
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        function handleOverlayClick(e) {
            if (e.target.id === 'deleteModal') {
                closeDeleteModal();
            }
        }

        function handleDeleteSubmit(e) {
            const btn = document.getElementById('btnConfirmDelete');
            btn.disabled = true;
            btn.textContent = 'Deleting...';
        }
    </script>
</body>
</html>
