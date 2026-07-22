<?php
include_once __DIR__ . '/auth.php';

function staff_nav_link($href, $label, $icon, $activeKey, $currentKey)
{
    $isActive = $activeKey === $currentKey;
    ?>
    <a class="staff-nav-link<?php echo $isActive ? ' is-active' : ''; ?>" href="<?php echo staff_escape($href); ?>" data-spa="<?php echo staff_escape($href); ?>" data-spa-link="true">
        <i class="fa <?php echo staff_escape($icon); ?>"></i>
        <span><?php echo staff_escape($label); ?></span>
    </a>
    <?php
}

function staff_layout_start($title, $activeKey, $subtitle = null)
{
    global $con;
    $currentStaff = staff_fetch_current($con);
    $csrfToken = staff_generate_csrf_token();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Staff Portal - Marie Noelle Spa and Salon Management System">
        <meta name="robots" content="noindex, nofollow">
        <title><?php echo staff_escape($title); ?> | Marie Noelle Spa and Salon</title>
        <link rel="icon" href="../panel/images/logo.png" type="image/x-icon">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <style>
            :root {
                --staff-bg: #f8f1e7;
                --staff-surface: rgba(255, 255, 255, 0.82);
                --staff-surface-strong: #ffffff;
                --staff-text: #2e221c;
                --staff-muted: #726155;
                --staff-border: rgba(62, 45, 35, 0.12);
                --staff-accent: #b18458;
                --staff-accent-dark: #8a5a3a;
                --staff-accent-soft: #f5e8da;
                --staff-green: #2b5b55;
                --staff-green-soft: rgba(43, 91, 85, 0.1);
                --staff-danger: #a63c3c;
                --staff-danger-soft: rgba(166, 60, 60, 0.1);
                --staff-warning: #b18858;
                --staff-warning-soft: rgba(177, 132, 88, 0.1);
                --staff-shadow: 0 24px 80px rgba(62, 45, 35, 0.1);
                --staff-shadow-soft: 0 14px 40px rgba(62, 45, 35, 0.08);
                --staff-radius-xl: 32px;
                --staff-radius-lg: 24px;
                --staff-radius-md: 18px;
                --staff-radius-sm: 12px;
            }

            * {
                box-sizing: border-box;
            }

            html {
                scroll-behavior: smooth;
            }

            body {
                margin: 0;
                font-family: "Manrope", sans-serif;
                color: var(--staff-text);
                background:
                    radial-gradient(circle at top left, rgba(177, 132, 88, 0.18), transparent 28%),
                    radial-gradient(circle at 85% 15%, rgba(43, 91, 85, 0.14), transparent 22%),
                    radial-gradient(circle at 60% 80%, rgba(177, 132, 88, 0.08), transparent 20%),
                    linear-gradient(180deg, #fcf7f2 0%, #f2e7db 100%);
                min-height: 100vh;
            }

            a {
                text-decoration: none;
                color: inherit;
            }

            ::selection {
                background: rgba(177, 132, 88, 0.25);
                color: var(--staff-text);
            }

            ::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }

            ::-webkit-scrollbar-track {
                background: rgba(62, 45, 35, 0.04);
                border-radius: 4px;
            }

            ::-webkit-scrollbar-thumb {
                background: rgba(62, 45, 35, 0.15);
                border-radius: 4px;
            }

            ::-webkit-scrollbar-thumb:hover {
                background: rgba(62, 45, 35, 0.25);
            }

            .staff-shell {
                min-height: 100vh;
                display: grid;
                grid-template-columns: 280px minmax(0, 1fr);
            }

            .staff-sidebar {
                position: sticky;
                top: 0;
                height: 100vh;
                padding: 24px 18px;
                padding-bottom: 40px;
                background: rgba(255, 252, 248, 0.72);
                border-right: 1px solid var(--staff-border);
                backdrop-filter: blur(20px);
                overflow-y: auto;
                overflow-x: hidden;
                display: flex;
                flex-direction: column;
            }

            .staff-sidebar::before {
                content: "";
                position: fixed;
                top: 0;
                left: 0;
                width: 280px;
                height: 100vh;
                background: linear-gradient(180deg, rgba(177, 132, 88, 0.06) 0%, transparent 60%);
                pointer-events: none;
                z-index: -1;
            }

            @media (min-width: 1200px) {
                .staff-sidebar {
                    position: sticky !important;
                    transform: none !important;
                }
            }

            .staff-brand {
                display: flex;
                align-items: center;
                gap: 14px;
                margin-bottom: 28px;
                padding: 16px;
                border-radius: var(--staff-radius-md);
                background: var(--staff-surface);
                box-shadow: var(--staff-shadow-soft);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .staff-brand:hover {
                transform: translateY(-2px);
                box-shadow: var(--staff-shadow);
            }

            .staff-brand img {
                width: 48px;
                height: 48px;
                object-fit: contain;
                border-radius: var(--staff-radius-sm);
            }

            .staff-brand-info strong {
                display: block;
                font-size: 15px;
                line-height: 1.35;
                font-family: "Libre Baskerville", serif;
                color: var(--staff-text);
            }

            .staff-brand-info span {
                display: block;
                font-size: 10px;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                color: var(--staff-muted);
                margin-top: 2px;
            }

            .staff-nav {
                display: flex;
                flex-direction: column;
                gap: 6px;
                flex: 1;
            }

            .staff-nav-link {
                display: flex;
                align-items: center;
                gap: 14px;
                padding: 14px 16px;
                border-radius: var(--staff-radius-sm);
                color: var(--staff-muted);
                font-weight: 700;
                font-size: 14px;
                transition: all 0.2s ease;
                position: relative;
                overflow: hidden;
            }

            .staff-nav-link::before {
                content: "";
                position: absolute;
                left: 0;
                top: 50%;
                transform: translateY(-50%) scaleY(0);
                width: 3px;
                height: 60%;
                background: linear-gradient(180deg, var(--staff-accent), var(--staff-accent-dark));
                border-radius: 0 3px 3px 0;
                transition: transform 0.2s ease;
            }

            .staff-nav-link:hover {
                color: var(--staff-accent-dark);
                background: rgba(177, 132, 88, 0.1);
                transform: translateX(4px);
            }

            .staff-nav-link:hover::before {
                transform: translateY(-50%) scaleY(1);
            }

            .staff-nav-link.is-active {
                color: var(--staff-accent-dark);
                background: rgba(177, 132, 88, 0.14);
                transform: translateX(4px);
            }

            .staff-nav-link.is-active::before {
                transform: translateY(-50%) scaleY(1);
            }

            .staff-nav-link i {
                width: 20px;
                text-align: center;
                font-size: 16px;
            }

            .staff-nav-divider {
                height: 1px;
                background: var(--staff-border);
                margin: 12px 0;
            }

            .staff-main {
                min-width: 0;
                padding: 28px 32px;
            }

            .staff-topbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 20px;
                margin-bottom: 28px;
                padding: 20px 24px;
                border: 1px solid rgba(255, 255, 255, 0.6);
                border-radius: var(--staff-radius-lg);
                background: var(--staff-surface);
                box-shadow: var(--staff-shadow-soft);
                backdrop-filter: blur(18px);
            }

            .staff-topbar-left {
                display: flex;
                align-items: center;
                gap: 16px;
            }

            .staff-topbar h1 {
                margin: 0;
                font-family: "Libre Baskerville", serif;
                font-size: clamp(1.5rem, 2.5vw, 2.2rem);
                font-weight: 700;
                color: var(--staff-text);
            }

            .staff-topbar-subtitle {
                margin: 4px 0 0;
                color: var(--staff-muted);
                font-size: 14px;
            }

            .staff-topbar-right {
                display: flex;
                align-items: center;
                gap: 12px;
                flex-wrap: wrap;
            }

            .staff-pill {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 10px 16px;
                border-radius: 999px;
                font-size: 13px;
                font-weight: 700;
                background: var(--staff-green-soft);
                color: var(--staff-green);
            }

            .staff-pill i {
                font-size: 12px;
            }

            .staff-button {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 12px 18px;
                border-radius: 999px;
                font-size: 13px;
                font-weight: 800;
                letter-spacing: 0.03em;
                border: 1px solid var(--staff-border);
                background: rgba(255, 255, 255, 0.85);
                color: var(--staff-text);
                cursor: pointer;
                transition: all 0.2s ease;
                text-decoration: none;
            }

            .staff-button:hover {
                background: rgba(255, 255, 255, 1);
                transform: translateY(-2px);
                box-shadow: var(--staff-shadow-soft);
            }

            .staff-button-primary {
                background: linear-gradient(135deg, var(--staff-accent), var(--staff-accent-dark));
                color: #fff;
                border: none;
                box-shadow: 0 12px 24px rgba(177, 132, 88, 0.25);
            }

            .staff-button-primary:hover {
                background: linear-gradient(135deg, var(--staff-accent-dark), var(--staff-accent));
                transform: translateY(-2px);
                box-shadow: 0 16px 32px rgba(177, 132, 88, 0.3);
            }

            .staff-button-danger {
                background: var(--staff-danger-soft);
                color: var(--staff-danger);
                border: none;
            }

            .staff-button-danger:hover {
                background: var(--staff-danger);
                color: #fff;
            }

            .staff-grid {
                display: grid;
                gap: 20px;
            }

            .staff-grid.cards-4 {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .staff-grid.cards-3 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .staff-grid.cards-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .staff-card {
                border: 1px solid rgba(255, 255, 255, 0.6);
                border-radius: var(--staff-radius-lg);
                background: var(--staff-surface);
                box-shadow: var(--staff-shadow-soft);
                backdrop-filter: blur(18px);
                padding: 24px;
                transition: transform 0.25s ease, box-shadow 0.25s ease;
            }

            .staff-card:hover {
                transform: translateY(-4px);
                box-shadow: var(--staff-shadow);
            }

            .staff-stat-label {
                margin-bottom: 14px;
                color: var(--staff-muted);
                font-size: 12px;
                font-weight: 800;
                letter-spacing: 0.1em;
                text-transform: uppercase;
            }

            .staff-stat-value {
                margin: 0;
                font-size: 2.4rem;
                font-weight: 800;
                color: var(--staff-text);
                line-height: 1;
            }

            .staff-stat-help {
                margin: 10px 0 0;
                color: var(--staff-muted);
                font-size: 13px;
                line-height: 1.5;
            }

            .staff-stat-icon {
                width: 48px;
                height: 48px;
                border-radius: var(--staff-radius-sm);
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 16px;
                font-size: 20px;
            }

            .staff-stat-icon.accent {
                background: var(--staff-warning-soft);
                color: var(--staff-accent-dark);
            }

            .staff-stat-icon.green {
                background: var(--staff-green-soft);
                color: var(--staff-green);
            }

            .staff-stat-icon.danger {
                background: var(--staff-danger-soft);
                color: var(--staff-danger);
            }

            .staff-section-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                margin-bottom: 20px;
                flex-wrap: wrap;
            }

            .staff-section-head-left {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .staff-section-head h2 {
                margin: 0;
                font-family: "Libre Baskerville", serif;
                font-size: 1.4rem;
                font-weight: 700;
            }

            .staff-section-head p {
                margin: 6px 0 0;
                color: var(--staff-muted);
                font-size: 14px;
            }

            .staff-table-card {
                border: 1px solid rgba(255, 255, 255, 0.6);
                border-radius: var(--staff-radius-lg);
                background: var(--staff-surface);
                box-shadow: var(--staff-shadow-soft);
                backdrop-filter: blur(18px);
                padding: 24px;
                overflow: hidden;
            }

            .staff-form-card {
                max-width: 800px;
                padding: 28px;
                border: 1px solid rgba(255, 255, 255, 0.6);
                border-radius: var(--staff-radius-lg);
                background: var(--staff-surface);
                box-shadow: var(--staff-shadow-soft);
            }

            .staff-page-content {
                display: grid;
                gap: 24px;
            }

            .table {
                margin: 0;
            }

            .table thead th {
                border-bottom-width: 2px;
                color: var(--staff-muted);
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.1em;
                text-transform: uppercase;
                padding: 14px 16px;
                background: rgba(62, 45, 35, 0.02);
            }

            .table tbody td {
                padding: 16px;
                vertical-align: middle;
                border-color: rgba(62, 45, 35, 0.06);
            }

            .table tbody tr {
                transition: background 0.15s ease;
            }

            .table tbody tr:hover {
                background: rgba(177, 132, 88, 0.04);
            }

            .staff-muted {
                color: var(--staff-muted);
            }

            .staff-badge {
                display: inline-flex;
                align-items: center;
                padding: 6px 12px;
                border-radius: 999px;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                background: var(--staff-warning-soft);
                color: var(--staff-accent-dark);
            }

            .staff-badge.is-success {
                background: var(--staff-green-soft);
                color: var(--staff-green);
            }

            .staff-badge.is-danger {
                background: var(--staff-danger-soft);
                color: var(--staff-danger);
            }

            .staff-badge.is-info {
                background: rgba(59, 130, 246, 0.15);
                color: #2563eb;
            }

            .staff-note {
                padding: 14px 18px;
                border-radius: var(--staff-radius-sm);
                background: var(--staff-warning-soft);
                color: var(--staff-accent-dark);
                font-size: 14px;
                line-height: 1.6;
            }

            .staff-note i {
                margin-right: 8px;
            }

            .form-label {
                margin-bottom: 8px;
                font-size: 12px;
                font-weight: 800;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                color: var(--staff-muted);
            }

            .form-control,
            .form-select {
                min-height: 52px;
                border: 1px solid var(--staff-border);
                border-radius: var(--staff-radius-sm);
                background: rgba(255, 255, 255, 0.92);
                box-shadow: none;
                color: var(--staff-text);
                padding: 12px 16px;
                font-size: 14px;
                transition: border-color 0.2s ease, box-shadow 0.2s ease;
            }

            .form-control:focus,
            .form-select:focus {
                border-color: rgba(177, 132, 88, 0.5);
                box-shadow: 0 0 0 4px rgba(177, 132, 88, 0.1);
                outline: none;
            }

            .form-control::placeholder {
                color: var(--staff-muted);
                opacity: 0.7;
            }

            .form-control.is-invalid {
                border-color: var(--staff-danger);
            }

            .form-control.is-invalid:focus {
                box-shadow: 0 0 0 4px var(--staff-danger-soft);
            }

            .form-select[multiple] {
                min-height: 160px;
            }

            .invalid-feedback {
                font-size: 12px;
                font-weight: 600;
                color: var(--staff-danger);
                margin-top: 6px;
            }

            .mobile-toggle {
                display: none;
                border: none;
                background: var(--staff-warning-soft);
                color: var(--staff-accent-dark);
                width: 44px;
                height: 44px;
                border-radius: var(--staff-radius-sm);
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .mobile-toggle:hover {
                background: rgba(177, 132, 88, 0.2);
            }

            .staff-loading {
                position: fixed;
                inset: 0;
                z-index: 9999;
                display: none;
                place-items: center;
                background: rgba(46, 34, 28, 0.2);
                backdrop-filter: blur(6px);
            }

            .staff-loading.is-visible {
                display: grid;
            }

            .staff-loading-card {
                min-width: 240px;
                padding: 24px 32px;
                border-radius: var(--staff-radius-md);
                background: rgba(255, 255, 255, 0.95);
                box-shadow: var(--staff-shadow);
                text-align: center;
            }

            .staff-loading-card i {
                font-size: 28px;
                color: var(--staff-accent);
                margin-bottom: 12px;
                animation: staff-spin 1s linear infinite;
            }

            .staff-loading-card span {
                display: block;
                font-weight: 700;
                color: var(--staff-text);
            }

            @keyframes staff-spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }

            .staff-toast {
                position: fixed;
                right: 24px;
                bottom: 24px;
                z-index: 9998;
                min-width: 280px;
                max-width: min(420px, calc(100vw - 48px));
                padding: 16px 20px;
                border-radius: var(--staff-radius-sm);
                color: #fff;
                font-weight: 600;
                opacity: 0;
                transform: translateY(20px);
                pointer-events: none;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                gap: 12px;
                box-shadow: var(--staff-shadow);
            }

            .staff-toast.is-visible {
                opacity: 1;
                transform: translateY(0);
            }

            .staff-toast i {
                font-size: 18px;
            }

            .staff-toast-success {
                background: var(--staff-green);
            }

            .staff-toast-error {
                background: var(--staff-danger);
            }

            .staff-toast-info {
                background: var(--staff-accent-dark);
            }

            .staff-overlay {
                position: fixed;
                inset: 0;
                background: rgba(46, 34, 28, 0.4);
                backdrop-filter: blur(4px);
                z-index: 50;
                opacity: 0;
                visibility: hidden;
                transition: all 0.25s ease;
            }

            .staff-overlay.is-visible {
                opacity: 1;
                visibility: visible;
            }

            .staff-empty-state {
                text-align: center;
                padding: 48px 24px;
                color: var(--staff-muted);
            }

            .staff-empty-state i {
                font-size: 48px;
                margin-bottom: 16px;
                opacity: 0.5;
            }

            .staff-empty-state p {
                margin: 0;
                font-size: 15px;
            }

            .staff-user-avatar {
                width: 36px;
                height: 36px;
                border-radius: 50%;
                background: linear-gradient(135deg, var(--staff-accent), var(--staff-accent-dark));
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 800;
                font-size: 14px;
            }

            @media (max-width: 1200px) {
                .staff-grid.cards-4 {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 1199px) {
                .staff-grid.cards-3 {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
                
                .staff-shell {
                    grid-template-columns: 1fr;
                }

                .staff-sidebar {
                    position: fixed;
                    left: 0;
                    top: 0;
                    z-index: 60;
                    width: min(88vw, 300px);
                    transform: translateX(-100%);
                    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    box-shadow: var(--staff-shadow);
                }

                .staff-sidebar.is-open {
                    transform: translateX(0);
                }

                .mobile-toggle {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                }

                .staff-topbar {
                    padding: 16px 20px;
                }

                .staff-topbar-right {
                    width: 100%;
                    justify-content: flex-start;
                    margin-top: 12px;
                }
            }

            @media (min-width: 641px) and (max-width: 1199px) {
                .staff-main {
                    padding: 20px;
                }

                .staff-card {
                    padding: 20px;
                }

                .staff-topbar {
                    padding: 18px 20px;
                }

                .staff-topbar h1 {
                    font-size: 1.5rem;
                }

                .staff-table-card {
                    padding: 20px;
                }

                .staff-form-card {
                    padding: 20px;
                }

                .staff-stat-value {
                    font-size: 1.8rem;
                }

                .staff-stat-label {
                    font-size: 11px;
                    margin-bottom: 10px;
                }

                .staff-stat-help {
                    font-size: 12px;
                }

                .staff-stat-icon {
                    width: 40px;
                    height: 40px;
                    font-size: 16px;
                    margin-bottom: 10px;
                }

                .staff-section-head h2 {
                    font-size: 1.15rem;
                }

                .staff-section-head p {
                    font-size: 12px;
                }

                .staff-brand {
                    padding: 10px;
                    margin-bottom: 18px;
                }

                .staff-brand img {
                    width: 36px;
                    height: 36px;
                }

                .staff-brand-info strong {
                    font-size: 13px;
                }

                .staff-nav-link {
                    padding: 10px 12px;
                    font-size: 13px;
                }

                .staff-grid {
                    gap: 12px;
                }

                .staff-button {
                    padding: 8px 12px;
                    font-size: 12px;
                }

                .staff-pill {
                    padding: 6px 10px;
                    font-size: 11px;
                }

                .table thead th {
                    padding: 8px 10px !important;
                    font-size: 9px;
                }

                .table tbody td {
                    padding: 10px !important;
                    font-size: 12px;
                }
            }

            @media (max-width: 640px) {
                .staff-main {
                    padding: 14px;
                }

                .staff-card {
                    padding: 18px;
                }

                .staff-grid.cards-4,
                .staff-grid.cards-3,
                .staff-grid.cards-2 {
                    grid-template-columns: 1fr;
                }

                .staff-grid {
                    gap: 8px;
                }

                .staff-topbar {
                    flex-direction: column;
                    align-items: flex-start;
                    padding: 16px 18px;
                    gap: 14px;
                }

                .staff-topbar h1 {
                    font-size: 1.3rem;
                }

                .staff-topbar-left {
                    width: 100%;
                    min-width: 0;
                }

                .staff-topbar-right {
                    width: 100%;
                    justify-content: flex-start;
                }

                .staff-section-head {
                    flex-direction: column;
                    align-items: flex-start;
                }

                .staff-section-head h2 {
                    font-size: 1rem;
                }

                .staff-table-card {
                    padding: 16px;
                }

                .staff-stat-value {
                    font-size: 1.6rem;
                }

                .staff-stat-icon {
                    width: 36px;
                    height: 36px;
                    font-size: 15px;
                    margin-bottom: 8px;
                }

                .staff-stat-label {
                    font-size: 10px;
                    margin-bottom: 6px;
                }

                .staff-brand {
                    padding: 12px;
                    margin-bottom: 14px;
                }

                .staff-brand img {
                    width: 32px;
                    height: 32px;
                }

                .staff-brand-info strong {
                    font-size: 12px;
                }

                .staff-nav-link {
                    padding: 10px 12px;
                    font-size: 12px;
                }

                .table-responsive {
                    margin: 0 -8px;
                    padding: 0 8px;
                    overflow-x: auto;
                }

                .table-responsive .table {
                    min-width: 620px;
                }

                .staff-button {
                    padding: 9px 12px;
                    font-size: 12px;
                }

                .staff-pill {
                    padding: 6px 10px;
                    font-size: 11px;
                }

                .modal-dialog {
                    margin: 12px;
                }

                .staff-toast {
                    right: 12px;
                    left: 12px;
                    bottom: 12px;
                    min-width: 0;
                    max-width: none;
                }

                .staff-form-card {
                    padding: 18px;
                }
            }

            @media (max-width: 420px) {
                .staff-main {
                    padding: 10px;
                }

                .staff-sidebar {
                    width: min(92vw, 300px);
                    padding: 18px 14px;
                }

                .staff-topbar {
                    padding: 14px;
                }

                .staff-topbar-left {
                    gap: 12px;
                }

                .staff-card,
                .staff-table-card,
                .staff-form-card {
                    padding: 14px;
                }

                .staff-pill,
                .staff-button {
                    width: 100%;
                    justify-content: center;
                }
            }
        </style>
    </head>
    <body>
        <div class="staff-overlay" id="staffOverlay"></div>
        <div class="staff-shell">
            <aside class="staff-sidebar" id="staffSidebar">
                <a class="staff-brand" href="dashboard.php">
                    <img src="../panel/images/logo.png" alt="Marie Noelle Spa and Salon">
                    <div class="staff-brand-info">
                        <span>Staff Portal</span>
                        <strong>Marie Noelle</strong>
                    </div>
                </a>
                <nav class="staff-nav">
                    <?php staff_nav_link('dashboard.php', 'Dashboard', 'fa-th-large', 'dashboard', $activeKey); ?>
                    <?php staff_nav_link('bookings.php', 'Bookings', 'fa-inbox', 'bookings', $activeKey); ?>
                    <?php staff_nav_link('appointments.php', 'Appointments', 'fa-calendar-check-o', 'appointments', $activeKey); ?>
                    <?php staff_nav_link('customers.php', 'Customers', 'fa-users', 'customers', $activeKey); ?>
                    <div class="staff-nav-divider"></div>
                    <?php staff_nav_link('manage_plan.php', 'Plans', 'fa-id-card', 'plan', $activeKey); ?>
                    <?php staff_nav_link('manage_subscribe.php', 'Subscriptions', 'fa-file-invoice', 'subscribe', $activeKey); ?>
                    <div class="staff-nav-divider"></div>
                    <?php staff_nav_link('payments.php', 'Payments', 'fa-credit-card', 'payments', $activeKey); ?>
                    <?php staff_nav_link('schedule.php', 'Schedule', 'fa-clock', 'schedule', $activeKey); ?>
                    <div class="staff-nav-divider"></div>
                    <?php staff_nav_link('audit-log.php', 'My Activity', 'fa-history', 'audit', $activeKey); ?>
                    <?php staff_nav_link('profile.php', 'My Profile', 'fa-user-circle', 'profile', $activeKey); ?>
                    <?php staff_nav_link('change-password.php', 'Security', 'fa-shield-halved', 'password', $activeKey); ?>
                    <div class="staff-nav-divider"></div>
                    <?php staff_nav_link('logout.php', 'Sign Out', 'fa-right-from-bracket', 'logout', $activeKey); ?>
                </nav>
            </aside>
            <main class="staff-main">
                <header class="staff-topbar">
                    <div class="staff-topbar-left">
                        <button class="mobile-toggle" type="button" id="staffSidebarToggle" aria-label="Toggle navigation">
                            <i class="fa fa-bars"></i>
                        </button>
                        <div>
                            <h1><?php echo staff_escape($title); ?></h1>
                            <?php if ($subtitle): ?>
                                <p class="staff-topbar-subtitle"><?php echo staff_escape($subtitle); ?></p>
                            <?php else: ?>
                                <p class="staff-topbar-subtitle">Welcome back<?php echo $currentStaff ? ', ' . staff_escape($currentStaff['name']) : ''; ?>.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="staff-topbar-right">
                        <?php if ($currentStaff): ?>
                            <div class="staff-pill">
                                <i class="fa fa-user"></i>
                                <?php echo staff_escape($currentStaff['name']); ?>
                            </div>
                            <div class="staff-user-avatar" title="<?php echo staff_escape($currentStaff['name']); ?>">
                                <?php echo strtoupper(substr($currentStaff['name'], 0, 1)); ?>
                            </div>
                            <button type="button" class="staff-button" onclick="showLogoutModal()" style="color: var(--staff-danger);">
                                <i class="fa fa-sign-out-alt"></i>
                                Logout
                            </button>
                        <?php endif; ?>
                    </div>
                </header>
                <div class="staff-page-content" id="staffPageContent">
                    <input type="hidden" id="staffCsrfToken" value="<?php echo staff_escape($csrfToken); ?>">
    <?php
}

function staff_layout_end()
{
    ?>
                </div>
            </main>
        </div>
        <div class="staff-loading" id="staffLoading" aria-hidden="true">
            <div class="staff-loading-card">
                <i class="fa fa-circle-notch"></i>
                <span id="staffLoadingText">Loading...</span>
            </div>
        </div>
        <div class="staff-toast" id="staffToast" role="alert" aria-live="polite" aria-atomic="true">
            <i class="fa fa-check-circle" id="staffToastIcon"></i>
            <span id="staffToastMessage"></span>
        </div>

        <div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content" style="border-radius: var(--staff-radius-lg); border: 0;">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h3 class="modal-title" style="font-family: 'Libre Baskerville', serif;">
                                <i class="fa fa-sign-out-alt" style="color: var(--staff-danger); margin-right: 10px;"></i>
                                Confirm Logout
                            </h3>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <p style="margin-bottom: 0;">Are you sure you want to logout?</p>
                    </div>
                    <div class="modal-footer border-0 pt-0 justify-content-center">
                        <button type="button" class="staff-button" data-bs-dismiss="modal">Cancel</button>
                        <a href="logout.php" class="staff-button" style="background: var(--staff-danger); color: white; border-color: var(--staff-danger);">
                            <i class="fa fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            (function () {
                'use strict';

                var loading = document.getElementById('staffLoading');
                var loadingText = document.getElementById('staffLoadingText');
                var toast = document.getElementById('staffToast');
                var toastMessage = document.getElementById('staffToastMessage');
                var toastIcon = document.getElementById('staffToastIcon');
                var overlay = document.getElementById('staffOverlay');
                var sidebar = document.getElementById('staffSidebar');
                var toggle = document.getElementById('staffSidebarToggle');
                var csrfTokenInput = document.getElementById('staffCsrfToken');

                function setLoading(visible, message) {
                    if (!loading) return;
                    if (message && loadingText) {
                        loadingText.textContent = message;
                    }
                    loading.classList.toggle('is-visible', visible);
                    loading.setAttribute('aria-hidden', visible ? 'false' : 'true');
                    if (overlay) {
                        overlay.classList.toggle('is-visible', visible);
                    }
                }

                function showToast(message, type) {
                    if (!toast || !toastMessage || !toastIcon) return;
                    
                    toastMessage.textContent = message;
                    toast.className = 'staff-toast is-visible staff-toast-' + (type || 'success');
                    
                    var iconClass = 'fa fa-check-circle';
                    if (type === 'error') {
                        iconClass = 'fa fa-exclamation-circle';
                    } else if (type === 'info') {
                        iconClass = 'fa fa-info-circle';
                    }
                    toastIcon.className = iconClass;

                    clearTimeout(showToast._timer);
                    showToast._timer = setTimeout(function () {
                        toast.className = 'staff-toast';
                    }, 3500);
                }

                function getCsrfToken() {
                    return csrfTokenInput ? csrfTokenInput.value : '';
                }

                async function postForm(url, formData, options) {
                    options = options || {};
                    setLoading(true, options.loadingText || 'Processing...');
                    
                    if (csrfTokenInput && formData) {
                        formData.append('csrf_token', getCsrfToken());
                    }

                    try {
                        var response = await fetch(url, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        var contentType = response.headers.get('content-type');
                        var payload;

                        if (contentType && contentType.indexOf('application/json') !== -1) {
                            payload = await response.json();
                        } else {
                            var text = await response.text();
                            try {
                                payload = JSON.parse(text);
                            } catch (e) {
                                throw new Error('Invalid server response.');
                            }
                        }

                        if (!response.ok || !payload.success) {
                            throw new Error(payload.message || 'Something went wrong. Please try again.');
                        }

                        if (options.successMessage !== false && payload.message) {
                            showToast(payload.message, 'success');
                        }

                        return payload;
                    } catch (error) {
                        showToast(error.message || 'Something went wrong.', 'error');
                        throw error;
                    } finally {
                        setLoading(false);
                    }
                }

                function initializeSidebar() {
                    var sidebarEl = document.getElementById('staffSidebar');
                    var toggleEl = document.getElementById('staffSidebarToggle');
                    var overlayEl = document.getElementById('staffOverlay');
                    
                    if (!toggleEl || !sidebarEl) return;

                    toggleEl.addEventListener('click', function () {
                        sidebarEl.classList.toggle('is-open');
                        if (overlayEl) {
                            overlayEl.classList.toggle('is-visible', sidebarEl.classList.contains('is-open'));
                        }
                    });

                    if (overlayEl) {
                        overlayEl.addEventListener('click', function () {
                            sidebarEl.classList.remove('is-open');
                            overlayEl.classList.remove('is-visible');
                        });
                    }
                }

                function initializeFormValidation(form) {
                    if (!form) return;

                    form.addEventListener('submit', function (e) {
                        var invalidFields = form.querySelectorAll('.is-invalid');
                        invalidFields.forEach(function (field) {
                            field.classList.remove('is-invalid');
                        });

                        var requiredFields = form.querySelectorAll('[required]');
                        var isValid = true;

                        requiredFields.forEach(function (field) {
                            if (!field.value.trim()) {
                                field.classList.add('is-invalid');
                                isValid = false;
                            }
                        });

                        if (!isValid) {
                            e.preventDefault();
                            showToast('Please fill in all required fields.', 'error');
                        }
                    });
                }

                function initializePage() {
                    var content = document.getElementById('staffPageContent');
                    if (content) {
                        var forms = content.querySelectorAll('form');
                        forms.forEach(initializeFormValidation);
                    }
                }

                function isStaffLink(url) {
                    var staffPages = [
                        'dashboard.php',
                        'appointments.php',
                        'payments.php',
                        'schedule.php',
                        'profile.php',
                        'change-password.php'
                    ];
                    
                    var filename = url.split('/').pop();
                    var isStaffPage = staffPages.indexOf(filename) !== -1;
                    var isExcluded = filename === 'logout.php' || filename === 'index.php';
                    
                    return isStaffPage && !isExcluded;
                }

                async function navigate(url, replaceState) {
                    setLoading(true, 'Loading page...');

                    try {
                        var response = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Failed to load page.');
                        }

                        var html = await response.text();
                        var nextDoc = new DOMParser().parseFromString(html, 'text/html');

                        var nextSidebar = nextDoc.querySelector('.staff-sidebar');
                        var nextMain = nextDoc.querySelector('.staff-main');
                        var nextTitle = nextDoc.querySelector('title');

                        if (!nextSidebar || !nextMain) {
                            window.location.href = url;
                            return;
                        }

                        var currentMain = document.querySelector('.staff-main');
                        if (currentMain && nextMain) {
                            currentMain.replaceWith(nextMain);
                        }

                        if (sidebar) {
                            sidebar.replaceWith(nextSidebar);
                            sidebar = document.getElementById('staffSidebar');
                        }

                        if (nextTitle) {
                            document.title = nextTitle.textContent;
                        }

                        initializeSidebar();
                        initializePage();
                        
                        window.StaffPortal.onPageLoad = null;
                        
                        var scripts = nextMain.querySelectorAll('script');
                        scripts.forEach(function(script) {
                            var newScript = document.createElement('script');
                            if (script.src) {
                                newScript.src = script.src;
                                newScript.async = false;
                                document.head.appendChild(newScript);
                            } else {
                                newScript.textContent = script.textContent;
                                document.head.appendChild(newScript);
                            }
                        });

                        requestAnimationFrame(function() {
                            if (window.StaffPortal && window.StaffPortal.onPageLoad) {
                                window.StaffPortal.onPageLoad();
                            }
                        });

                        if (replaceState) {
                            history.replaceState({ url: url }, '', url);
                        } else {
                            history.pushState({ url: url }, '', url);
                        }

                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    } catch (error) {
                        showToast('Unable to load page. Redirecting...', 'error');
                        window.location.href = url;
                    } finally {
                        setLoading(false);
                    }
                }

                function init() {
                    initializeSidebar();
                    initializePage();

                    document.addEventListener('click', function (e) {
                        var link = e.target.closest('a[data-spa]');
                        if (!link) return;
                        if (link.target === '_blank' || e.defaultPrevented) return;

                        var href = link.getAttribute('data-spa');
                        if (!href) return;
                        if (href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return;

                        e.preventDefault();
                        navigate(href, false);
                    });

                    window.addEventListener('popstate', function (e) {
                        if (e.state && e.state.url) {
                            navigate(e.state.url, true);
                        }
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', init);
                } else {
                    init();
                }

                window.StaffPortal = {
                    navigate: navigate,
                    postForm: postForm,
                    setLoading: setLoading,
                    showToast: showToast,
                    getCsrfToken: getCsrfToken
                };

                window.showLogoutModal = function() {
                    var modalEl = document.getElementById('logoutConfirmModal');
                    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        var modal = new bootstrap.Modal(modalEl);
                        modal.show();
                    }
                };
            })();
        </script>
    </body>
    </html>
    <?php
}
