import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Rate, Counter } from 'k6/metrics';

// ── Métricas personalizadas ───────────────────────────────────────────────────
const responseTime = new Trend('simulate_response_time', true);
const successRate  = new Rate('simulate_success_rate');
const totalSent    = new Counter('simulate_total_sent');

// ── Configuración de la prueba ────────────────────────────────────────────────
export const options = {
    summaryTrendStats: ['med', 'p(90)', 'p(95)', 'p(99)', 'max'],
    scenarios: {
        flood: {
            executor: 'per-vu-iterations',
            vus: 1000,       // 1000 usuarios virtuales simultáneos
            iterations: 1,   // cada uno envía 1 mensaje = 1000 en total
            maxDuration: '3m',
        },
    },
    thresholds: {
        // abortOnFail: false → solo reporta, no aborta ni lanza exit 99
        http_req_duration: [{ threshold: 'p(95)<2000', abortOnFail: false }],
        simulate_success_rate: [{ threshold: 'rate>0.95', abortOnFail: false }],
        http_req_failed:       [{ threshold: 'rate<0.05', abortOnFail: false }],
    },
};

// ── Variables de entorno ──────────────────────────────────────────────────────
const BASE_URL = __ENV.BASE_URL || 'https://uniph-api.gislasas.com';
const TOKEN    = __ENV.TOKEN    || '';

// ── Request principal ─────────────────────────────────────────────────────────
export default function () {
    // Genera un teléfono único por usuario virtual (VU)
    const phone = `57300${String(__VU).padStart(7, '0')}`;

    const payload = JSON.stringify({
        phone: phone,
        text:  'hola',
    });

    const params = {
        headers: {
            'Content-Type':  'application/json',
            'Authorization': `Bearer ${TOKEN}`,
            'Accept':        'application/json',
        },
        timeout: '30s',
    };

    const res = http.post(`${BASE_URL}/api/internal/simulate-message`, payload, params);

    // ── Registrar métricas ────────────────────────────────────────────────────
    responseTime.add(res.timings.duration);
    totalSent.add(1);

    const ok = check(res, {
        'status es 202':          (r) => r.status === 202,
        'body contiene message':  (r) => r.body && r.body.includes('message'),
    });

    successRate.add(ok);

    if (!ok) {
        console.error(`[VU ${__VU}] phone=${phone} status=${res.status} body=${res.body}`);
    }
}

// ── Resumen final en consola ──────────────────────────────────────────────────
export function handleSummary(data) {
    const metrics = data.metrics;

    // Los percentiles de http_req_duration son siempre fiables (métrica built-in)
    const dur  = metrics.http_req_duration?.values ?? {};
    const p50  = dur['med']    ?? 0;
    const p90  = dur['p(90)'] ?? 0;
    const p95  = dur['p(95)'] ?? 0;
    const p99  = dur['p(99)'] ?? 0;
    const rate = ((metrics.simulate_success_rate?.values?.rate ?? 0) * 100).toFixed(1);
    const sent = metrics.simulate_total_sent?.values?.count ?? 0;
    const failed = metrics.http_req_failed?.values?.rate ?? 0;

    console.log('\n══════════════════════════════════════════');
    console.log('  RESULTADO PRUEBA DE ESTRÉS — WhatsApp Simulate');
    console.log('══════════════════════════════════════════');
    console.log(`  Mensajes enviados : ${sent}`);
    console.log(`  Tasa de éxito     : ${rate}%`);
    console.log(`  Requests fallidos : ${(failed * 100).toFixed(1)}%`);
    console.log(`  Tiempo mediana    : ${p50.toFixed(0)} ms`);
    console.log(`  Tiempo p90        : ${p90.toFixed(0)} ms`);
    console.log(`  Tiempo p95        : ${p95.toFixed(0)} ms`);
    console.log(`  Tiempo p99        : ${p99.toFixed(0)} ms`);
    console.log('══════════════════════════════════════════\n');

    return {
        stdout: '',
    };
}
