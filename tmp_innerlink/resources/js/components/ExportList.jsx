import { useEffect, useState } from 'react';
import { fetchExports } from '../api/exports';
import { useEcho } from '../context/EchoContext';
import { formatIST } from '../utils/formatDate';

const statusColors = {
    PENDING: 'bg-warning/10 text-warning',
    PROCESSING: 'bg-accent/10 text-accent',
    COMPLETED: 'bg-success/10 text-success',
    FAILED: 'bg-danger/10 text-danger',
};

export default function ExportList() {
    const [exports, setExports] = useState([]);
    const [loading, setLoading] = useState(true);
    const echo = useEcho();

    useEffect(() => {
        fetchExports()
            .then((data) => setExports(Array.isArray(data) ? data : data.data || []))
            .catch(() => {})
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        if (!echo) return;
        const channel = echo.private('admin.dashboard');
        channel.listen('.export.completed', (e) => {
            setExports((prev) =>
                prev.map((exp) =>
                    exp.id === e.exportId ? { ...exp, status: 'COMPLETED', file_path: e.filePath } : exp
                )
            );
        });
        return () => {
            echo.leave('admin.dashboard');
        };
    }, [echo]);

    if (loading) {
        return <div className="text-center py-8 text-muted">Loading exports...</div>;
    }

    if (!exports.length) {
        return <div className="text-center py-8 text-muted">No exports yet. Create one above.</div>;
    }

    return (
        <div className="bg-surface border border-border rounded-lg overflow-hidden">
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-border">
                    <thead className="bg-primary/[0.02]">
                        <tr>
                            <th className="px-4 sm:px-6 py-3 text-left text-xs font-medium text-muted uppercase whitespace-nowrap">ID</th>
                            <th className="px-4 sm:px-6 py-3 text-left text-xs font-medium text-muted uppercase whitespace-nowrap">Format</th>
                            <th className="px-4 sm:px-6 py-3 text-left text-xs font-medium text-muted uppercase whitespace-nowrap">Status</th>
                            <th className="px-4 sm:px-6 py-3 text-left text-xs font-medium text-muted uppercase whitespace-nowrap">Created</th>
                            <th className="px-4 sm:px-6 py-3 text-left text-xs font-medium text-muted uppercase whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-border">
                        {exports.map((exp) => (
                            <tr key={exp.id}>
                                <td className="px-4 sm:px-6 py-3 sm:py-4 text-sm text-primary whitespace-nowrap">#{exp.id}</td>
                                <td className="px-4 sm:px-6 py-3 sm:py-4 text-sm text-primary whitespace-nowrap">{exp.format}</td>
                                <td className="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-md ${statusColors[exp.status] || 'bg-primary/5 text-secondary'}`}>
                                        {exp.status}
                                    </span>
                                </td>
                                <td className="px-4 sm:px-6 py-3 sm:py-4 text-sm text-secondary whitespace-nowrap">{formatIST(exp.created_at)}</td>
                                <td className="px-4 sm:px-6 py-3 sm:py-4 text-sm whitespace-nowrap">
                                    {exp.file_available ? (
                                        <a href={`/admin/exports/${exp.id}/download`} className="text-accent hover:text-accent/80 font-medium">
                                            Download
                                        </a>
                                    ) : (
                                        <span className="text-muted">-</span>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
