import { useState } from 'react';
import { createExport } from '../api/exports';

export default function ExportForm({ onCreated }) {
    const [format, setFormat] = useState('CSV');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');
        try {
            const result = await createExport(format);
            onCreated?.(result);
            setFormat('CSV');
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to create export');
        } finally {
            setLoading(false);
        }
    };

    return (
        <form onSubmit={handleSubmit} className="bg-surface border border-border rounded-lg p-6">
            <h3 className="text-lg font-medium text-primary mb-4">Create New Export</h3>
            {error && <div className="mb-4 text-sm text-danger bg-danger/10 rounded-md p-3">{error}</div>}
            <div className="flex flex-col sm:flex-row items-stretch sm:items-end gap-3 sm:gap-4">
                <div className="flex-1">
                    <label className="block text-sm font-medium text-secondary mb-1">Format</label>
                    <select value={format} onChange={(e) => setFormat(e.target.value)} className="w-full border border-border bg-surface pl-3 pr-10 py-2 text-sm text-primary focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent rounded-md">
                        <option value="CSV">CSV</option>
                        <option value="XLSX">XLSX</option>
                        <option value="PDF">PDF</option>
                    </select>
                </div>
                <button type="submit" disabled={loading} className="px-4 py-2 bg-accent text-white text-sm font-medium hover:opacity-90 disabled:opacity-50 rounded-md">
                    {loading ? 'Creating...' : 'Create Export'}
                </button>
            </div>
        </form>
    );
}
