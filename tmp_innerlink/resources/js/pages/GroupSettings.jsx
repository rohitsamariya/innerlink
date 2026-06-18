import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import client from '../api/client';
import Skeleton from '../components/Skeleton';
import GroupMemberManager from '../components/GroupMemberManager';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export default function GroupSettings() {
    const { groupId } = useParams();
    const navigate = useNavigate();

    const [group, setGroup] = useState(null);
    const [loading, setLoading] = useState(true);
    const [name, setName] = useState('');
    const [isEnabled, setIsEnabled] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    useEffect(() => {
        client.get(`/groups/${groupId}`)
            .then((res) => {
                const g = unwrap(res);
                setGroup(g);
                setName(g.name || '');
                setIsEnabled(g.is_enabled !== false);
            })
            .catch(() => navigate('/groups'))
            .finally(() => setLoading(false));
    }, [groupId, navigate]);

    const handleSave = async (e) => {
        e.preventDefault();
        setError('');
        setSuccess('');
        setSaving(true);
        try {
            const res = await client.patch(`/groups/${groupId}/settings`, { name, is_enabled: isEnabled });
            const updated = unwrap(res);
            setGroup(updated);
            setSuccess('Settings saved.');
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to save settings.');
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return (
            <div className="flex-1 p-4 sm:p-6 space-y-4">
                <Skeleton className="h-8 w-64" />
                <Skeleton className="h-4 w-96" />
                <Skeleton className="h-32 w-full" />
                <Skeleton className="h-24 w-full" />
            </div>
        );
    }

    return (
        <div className="flex-1 flex flex-col">
            <div className="border-b border-border bg-header px-4 sm:px-6 py-4 flex items-center gap-3">
                <button onClick={() => navigate(`/chat/${groupId}`)} className="text-secondary hover:text-primary">
                    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </button>
                <h1 className="text-xl font-semibold text-primary">
                    {group?.name || 'Group'} — Settings
                </h1>
            </div>

            <div className="flex-1 overflow-y-auto p-4 sm:p-6">
                <div className="max-w-2xl mx-auto space-y-6">
                    {error && (
                        <div className="text-sm text-danger bg-danger/10 border border-danger/20 rounded-md px-4 py-3">{error}</div>
                    )}
                    {success && (
                        <div className="text-sm text-success bg-success/10 border border-success/20 rounded-md px-4 py-3">{success}</div>
                    )}

                    <div className="bg-surface border border-border rounded-lg">
                        <div className="px-6 py-4 border-b border-border">
                            <h3 className="text-base font-semibold text-primary">General</h3>
                            <p className="text-sm text-muted mt-0.5">Manage the group name and availability.</p>
                        </div>

                        <form onSubmit={handleSave} className="px-6 py-5 space-y-6">
                            <div>
                                <label className="block text-sm font-medium text-secondary mb-1.5">Group Name</label>
                                <input
                                    type="text"
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    maxLength={100}
                                    className="w-full border border-border bg-transparent px-3.5 py-2.5 text-sm text-primary focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent rounded-md"
                                />
                            </div>

                            <div className="flex items-center justify-between border border-border rounded-lg p-4">
                                <div>
                                    <p className="text-sm font-medium text-primary">{isEnabled ? 'Group Enabled' : 'Group Disabled'}</p>
                                    <p className="text-xs text-muted mt-0.5">{isEnabled ? 'Members can send messages.' : 'When disabled, members cannot send messages.'}</p>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setIsEnabled(!isEnabled)}
                                    className={`relative inline-flex h-6 w-11 items-center shrink-0 transition-colors rounded-full ${isEnabled ? 'bg-accent' : 'bg-border'}`}
                                >
                                    <span className={`inline-block h-4 w-4 bg-white transition-transform rounded-full ${isEnabled ? 'translate-x-6' : 'translate-x-1'}`} />
                                </button>
                            </div>

                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-border">
                                <button
                                    type="button"
                                    onClick={() => navigate(`/chat/${groupId}`)}
                                    className="px-4 py-2 text-sm font-medium text-secondary border border-border hover:bg-primary/[0.03] rounded-md"
                                >
                                    Back to Chat
                                </button>
                                <button
                                    type="submit"
                                    disabled={saving || !name.trim()}
                                    className="px-5 py-2 bg-accent text-white text-sm font-medium hover:opacity-90 disabled:opacity-50 rounded-md"
                                >
                                    {saving ? 'Saving...' : 'Save Changes'}
                                </button>
                            </div>
                        </form>
                    </div>

                    <div className="bg-surface border border-border rounded-lg">
                        <div className="px-6 py-4 border-b border-border">
                            <h3 className="text-base font-semibold text-primary">Members</h3>
                            <p className="text-sm text-muted mt-0.5">View, add, or remove group participants.</p>
                        </div>
                        <div className="px-6 py-5">
                            <GroupMemberManager groupId={groupId} />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
