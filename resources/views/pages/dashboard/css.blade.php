<style>
    .stat-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: 5px;
        padding: 14px;
        height: 100%;
    }

    .stat-title {
        color: #999;
        font-size: 9px;
        margin-bottom: 7px;
    }

    .stat-value {
        font-size: 19px;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 5px;
    }

    .stat-description {
        font-size: 8px;
        color: #aaa;
    }

    /* =========================
           INFO
        ========================= */

    .info-box {
        background: white;
        border: 1px solid var(--border);
        border-radius: 5px;
        padding: 11px 14px;
        margin-top: 12px;
        margin-bottom: 15px;
    }

    .info-title {
        font-size: 9px;
        font-weight: 600;
        margin-bottom: 3px;
    }

    .info-text {
        color: #999;
        font-size: 8px;
        margin: 0;
    }

    /* =========================
           PANEL
        ========================= */

    .panel {
        background: white;
        border: 1px solid var(--border);
        border-radius: 5px;
        height: 100%;
        overflow: hidden;
    }

    .panel-header {
        padding: 13px 14px 5px;
    }

    .panel-title {
        font-size: 11px;
        font-weight: 600;
        margin: 0;
    }

    .panel-subtitle {
        font-size: 8px;
        color: #999;
        margin-top: 3px;
    }

    .panel-body {
        padding: 10px 14px 15px;
    }

    /* =========================
           BAR CHART
        ========================= */

    .chart-row {
        display: flex;
        align-items: center;
        margin-bottom: 13px;
    }

    .chart-label {
        width: 72px;
        font-size: 8px;
        color: #666;
    }

    .chart-bar-wrapper {
        flex: 1;
        height: 7px;
        background: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
    }

    .chart-bar {
        height: 100%;
        background: #444;
        border-radius: 10px;
    }

    .chart-value {
        width: 25px;
        font-size: 8px;
        text-align: right;
        color: #666;
    }

    /* =========================
           TABLE
        ========================= */

    .simple-table {
        width: 100%;
        border-collapse: collapse;
    }

    .simple-table tr {
        border-bottom: 1px solid #eee;
    }

    .simple-table tr:last-child {
        border-bottom: 0;
    }

    .simple-table td {
        padding: 8px 3px;
        font-size: 8px;
        color: #777;
    }

    .simple-table td:first-child {
        width: 45px;
        color: #333;
        font-weight: 600;
    }

    .simple-table td:nth-child(2) {
        color: #555;
    }

    .table-action {
        text-align: right;
        color: #999 !important;
    }

    /* =========================
           ACTIVITY
        ========================= */

    .activity-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 9px 0;
        border-bottom: 1px solid #eee;
    }

    .activity-item:last-child {
        border-bottom: 0;
    }

    .activity-time {
        width: 42px;
        font-size: 8px;
        color: #999;
    }

    .activity-text {
        flex: 1;
        font-size: 8px;
        color: #666;
    }

    /* =========================
           MOBILE HEADER
        ========================= */

    .mobile-menu-btn {
        display: none;
        width: 34px;
        height: 34px;
        border: 1px solid #ddd;
        background: white;
        border-radius: 4px;
    }
</style>
