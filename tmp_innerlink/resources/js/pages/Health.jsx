import { useEffect, useState, useCallback } from 'react';
import { checkLive, checkReady } from '../api/health';

const statusConfig = {
    alive: { label: 'Alive', bg: 'bg-success/10', text: 'text-success', dot: 'bg-success' },
    down: { label: 'Down', bg: 'bg-danger/10', text: 'text-danger', dot: 'bg-danger' },
    healthy: { label: 'Healthy', bg: 'bg-success/10', text: 'text-success', dot: 'bg-success' },
    degraded: { label: 'Degraded', bg: 'bg-warning/10', text: 'text-warning', dot: 'bg-warning' },
    unhealthy: { label: 'Unhealthy', bg: 'bg-danger/10', text: 'text-danger', dot: 'bg-danger' },
    up: { label: 'Up', bg: 'bg-success/10', text: 'text-success', dot: 'bg-success' },
};

function StatusBadge({ status }) {
    const cfg = statusConfig[status] || { label: status, bg: 'bg-primary/5', text: 'text-secondary', dot: 'bg-muted' };
    return (
                <span className={`inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-md ${cfg.bg} ${cfg.text}`}>
            <span className={`h-1.5 w-1.5 ${cfg.dot}`} />
            {cfg.label}
        </span>
    );
}

export default function Health() {
    const [live, setLive] = useState(null);
    const [ready, setReady] = useState(null);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    const fetchHealth = useCallback(async (silent) => {
        if (!silent) setLoading(true);
        else setRefreshing(true);
        try {
            const [l, r] = await Promise.all([
                checkLive().catch(() => ({ status: 'down' })),
                checkReady().catch(() => ({ status: 'unhealthy' })),
            ]);
            setLive(l);
            setReady(r);
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, []);

    useEffect(() => {
        fetchHealth();
    }, [fetchHealth]);

    if (loading) {
        return (
            <div className="flex-1 flex items-center justify-center">
                <div className="animate-spin h-8 w-8 border-2 border-primary border-t-transparent rounded-full" />
            </div>
        );
    }

    const overallHealthy = live?.status === 'alive' && ready?.status === 'healthy';
    const overallDegraded = ready?.status === 'degraded';
    const serviceList = ready?.services ? Object.entries(ready.services) : [];
    const healthyCount = serviceList.filter(([, s]) => s === 'up').length;
    const totalCount = serviceList.length;

    return (
        <div className="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
            <div className="max-w-3xl mx-auto">
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h2 className="text-xl sm:text-2xl font-bold text-primary">System Health</h2>
                        <p className="text-sm text-muted mt-0.5">Monitoring all services and dependencies</p>
                    </div>
                    <button
                        onClick={() => fetchHealth(true)}
                        disabled={refreshing}
                        className="flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-secondary border border-border hover:bg-primary/[0.03] disabled:opacity-50 rounded-md"
                    >
                        <svg className={`h-4 w-4 ${refreshing ? 'animate-spin' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
                        </svg>
                        Refresh
                    </button>
                </div>

                {overallHealthy && (
                    <div className="mb-6 flex items-center gap-3 px-4 py-3 bg-success/10 border border-success/20 rounded-md">
                        <div className="w-8 h-8 bg-success/10 flex items-center justify-center shrink-0">
                            <svg className="h-5 w-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p className="text-sm font-medium text-success">All systems are operational</p>
                    </div>
                )}

                {overallDegraded && (
                    <div className="mb-6 flex items-center gap-3 px-4 py-3 bg-warning/10 border border-warning/20 rounded-md">
                        <div className="w-8 h-8 bg-warning/10 flex items-center justify-center shrink-0">
                            <svg className="h-5 w-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                        </div>
                        <p className="text-sm font-medium text-warning">Some services are degraded</p>
                    </div>
                )}

                {!overallHealthy && !overallDegraded && (
                    <div className="mb-6 flex items-center gap-3 px-4 py-3 bg-danger/10 border border-danger/20 rounded-md">
                        <div className="w-8 h-8 bg-danger/10 flex items-center justify-center shrink-0">
                            <svg className="h-5 w-5 text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <p className="text-sm font-medium text-danger">System issues detected</p>
                    </div>
                )}

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <div className="bg-surface border border-border rounded-lg p-5">
                        <div className="flex items-center justify-between mb-3">
                            <div className="flex items-center gap-2.5">
                                <div className="w-9 h-9 bg-success/10 flex items-center justify-center rounded-lg">
                                    <svg className="h-5 w-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                    </svg>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-primary">Liveness</p>
                                    <p className="text-xs text-muted">Application heartbeat</p>
                                </div>
                            </div>
                            <StatusBadge status={live?.status} />
                        </div>
                    </div>

                    <div className="bg-surface border border-border rounded-lg p-5">
                        <div className="flex items-center justify-between mb-3">
                            <div className="flex items-center gap-2.5">
                                <div className={`w-9 h-9 flex items-center justify-center rounded-lg ${overallHealthy ? 'bg-success/10' : overallDegraded ? 'bg-warning/10' : 'bg-danger/10'}`}>
                                    <svg className={`h-5 w-5 ${overallHealthy ? 'text-success' : overallDegraded ? 'text-warning' : 'text-danger'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                    </svg>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-primary">Readiness</p>
                                    <p className="text-xs text-muted">Service dependencies</p>
                                </div>
                            </div>
                            <StatusBadge status={ready?.status} />
                        </div>
                        {ready?.response_time_ms && (
                            <p className="text-xs text-muted mt-2">
                                Response: <span className="font-medium text-secondary">{ready.response_time_ms}ms</span>
                            </p>
                        )}
                    </div>
                </div>

                {serviceList.length > 0 && (
                    <div className="bg-surface border border-border rounded-lg overflow-hidden mb-6">
                        <div className="px-5 py-4 border-b border-border flex items-center justify-between">
                            <div>
                                <h3 className="text-sm font-semibold text-primary">Services</h3>
                                <p className="text-xs text-muted mt-0.5">{healthyCount}/{totalCount} healthy</p>
                            </div>
                        </div>
                        <div className="divide-y divide-border">
                            {serviceList.map(([name, status]) => (
                                <div key={name} className="flex items-center justify-between px-5 py-3.5 hover:bg-primary/[0.02] transition-colors">
                                    <div className="flex items-center gap-3">
                                        <span className={`h-2 w-2 ${status === 'up' ? 'bg-success' : status === 'degraded' ? 'bg-warning' : 'bg-danger'}`} />
                                        <span className="text-sm font-medium text-primary capitalize">{name.replace(/_/g, ' ')}</span>
                                    </div>
                                    <span className={`text-xs font-semibold ${status === 'up' ? 'text-success' : status === 'degraded' ? 'text-warning' : 'text-danger'}`}>
                                        {status === 'up' ? 'Operational' : status === 'degraded' ? 'Degraded' : 'Down'}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {ready?.metrics && (
                    <div className="bg-surface border border-border rounded-lg p-5">
                        <h3 className="text-sm font-semibold text-primary mb-4">Metrics</h3>
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div className="bg-primary/[0.02] rounded-lg p-3.5 text-center">
                                <p className="text-lg font-bold text-primary">{ready.metrics.execution_time_ms}<span className="text-xs font-normal text-muted ml-0.5">ms</span></p>
                                <p className="text-xs text-muted mt-0.5">Execution Time</p>
                            </div>
                            <div className="bg-primary/[0.02] rounded-lg p-3.5 text-center">
                                <p className="text-lg font-bold text-success">{ready.metrics.healthy_count}<span className="text-xs font-normal text-muted ml-0.5">/{ready.metrics.service_count}</span></p>
                                <p className="text-xs text-muted mt-0.5">Healthy</p>
                            </div>
                            <div className="bg-primary/[0.02] rounded-lg p-3.5 text-center">
                                <p className="text-lg font-bold text-warning">{ready.metrics.degraded_count}</p>
                                <p className="text-xs text-muted mt-0.5">Degraded</p>
                            </div>
                            <div className="bg-primary/[0.02] rounded-lg p-3.5 text-center">
                                <p className="text-lg font-bold text-danger">{ready.metrics.failed_count}</p>
                                <p className="text-xs text-muted mt-0.5">Failed</p>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
