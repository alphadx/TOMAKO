<?php

return [
    'adminEmail'   => getenv('ADMIN_EMAIL')  ?: 'admin@tomako.cl',
    'senderEmail'  => getenv('SENDER_EMAIL') ?: 'noreply@tomako.cl',
    'senderName'   => 'TOMAKO',

    // ── Aplicación ────────────────────────────────────────────────────────────
    'app.name'     => 'TOMAKO',
    'app.version'  => '1.0.0',

    // ── Caché ─────────────────────────────────────────────────────────────────
    'cache.ttl'    => (int) (getenv('CACHE_TTL') ?: 300),
    'dashboard.kpi_ttl' => (int) (getenv('DASHBOARD_KPI_TTL') ?: 60),

    // ── i18n ──────────────────────────────────────────────────────────────────
    'defaultLanguage' => 'es-CL',
    'supportedLanguages' => ['es-CL', 'en-US'],
];
