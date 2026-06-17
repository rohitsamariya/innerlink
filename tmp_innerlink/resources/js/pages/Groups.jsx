import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { fetchGroups, createGroup } from '../api/groups';
import { useAuth } from '../context/AuthContext';
import Skeleton from '../components/Skeleton';

export default function Groups() {
    const { user } = useAuth();
    const navigate = useNavigate();
    const [groups, setGroups] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [groupName, setGroupName] = useState('');
    const [creating, setCreating] = useState(false);
    const [error, setError] = useState('');

    const loadGroups = () => {
        setLoading(true);
        fetchGroups()
            .then((data) => setGroups(Array.isArray(data) ? data : data.data || []))
            .catch(() => {})
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        loadGroups();
        const onFocus = () => loadGroups();
        window.addEventListener('focus', onFocus);
        return () => window.removeEventListener('focus', onFocus);
    }, []);

    const handleCreate = async (e) => {
        e.preventDefault();
        setError('');
        setCreating(true);
        try {
            const data = await createGroup(groupName);
            setShowModal(false);
            setGroupName('');
            loadGroups();
            navigate(`/chat/${data.data?.id || data.id}`);
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to create group');
        } finally {
            setCreating(false);
        }
    };

    return (
        <div className="flex-1 p-4 sm:p-8">
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-xl sm:text-2xl font-bold text-primary">Groups</h2>
                {user?.role === 'ADMIN' && (
                    <button onClick={() => setShowModal(true)} className="px-4 py-2 bg-accent text-white text-sm font-medium hover:opacity-90 transition-opacity rounded-md">
                        + New Group
                    </button>
                )}
            </div>

            <div className="mb-8">
                <div className="flex items-center gap-3 mb-4">
                    <h3 className="text-lg font-medium text-primary">Your Groups</h3>
                    <span className="inline-flex items-center justify-center h-5 min-w-[1.25rem] px-1.5 bg-primary/5 text-secondary text-xs font-medium rounded-md">
                        {groups.length}
                    </span>
                </div>
                {loading ? (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <Skeleton className="h-24" count={3} />
                    </div>
                ) : groups.length === 0 ? (
                    <div className="text-muted bg-surface border border-border rounded-lg p-8 text-center">
                        <p className="text-sm">You are not a member of any groups yet.</p>
                        {user?.role === 'ADMIN' && (
                            <button onClick={() => setShowModal(true)} className="mt-3 text-sm text-accent hover:underline font-medium">
                                Create one
                            </button>
                        )}
                    </div>
                ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        {groups.map((g) => (
                            <button
                                key={g.id}
                                onClick={() => navigate(`/chat/${g.id}`)}
                                className={`group bg-surface border border-border rounded-lg p-5 text-left hover:border-accent/30 hover:shadow-sm hover:-translate-y-0.5 transition-all ${g.is_enabled === false ? 'opacity-60' : ''}`}
                            >
                                <div className="flex items-start gap-4">
                                    <div className="relative shrink-0">
                                        <div className="w-12 h-12 bg-accent/10 flex items-center justify-center text-accent text-lg font-bold rounded-xl">
                                            {(g.name || 'G').charAt(0).toUpperCase()}
                                        </div>
                                        {g.unread_count > 0 && (
                                            <span className="absolute -top-1.5 -right-1.5 flex items-center justify-center h-5 min-w-[1.25rem] px-1.5 bg-accent text-white text-xs font-bold rounded-full shadow-sm">
                                                {g.unread_count > 99 ? '99+' : g.unread_count}
                                            </span>
                                        )}
                                    </div>
                                    <div className="flex-1 min-w-0 pt-0.5">
                                        <div className="flex items-center gap-2">
                                            <h4 className="text-base font-semibold text-primary truncate group-hover:text-accent transition-colors">{g.name}</h4>
                                            {g.is_enabled === false && (
                                                <span className="shrink-0 inline-flex px-2 py-0.5 text-[10px] font-medium bg-danger/10 text-danger rounded-md">
                                                    Disabled
                                                </span>
                                            )}
                                        </div>
                                        <p className="text-xs text-muted mt-1.5">
                                            {g.unread_count > 0
                                                ? `${g.unread_count} unread message${g.unread_count !== 1 ? 's' : ''}`
                                                : 'No unread messages'}
                                        </p>
                                    </div>
                                </div>
                            </button>
                        ))}
                    </div>
                )}
            </div>

            {showModal && (
                <div className="fixed inset-0 bg-black/50 flex items-end sm:items-center justify-center z-50">
                    <div className="bg-surface border border-border rounded-xl p-6 w-full sm:max-w-md sm:mx-4 max-h-[90vh] overflow-y-auto">
                        <h3 className="text-lg font-semibold text-primary mb-4">Create New Group</h3>
                        <form onSubmit={handleCreate}>
                            {error && <div className="mb-4 text-sm text-danger bg-danger/10 rounded-md p-3">{error}</div>}
                            <div className="mb-4">
                                <label className="block text-sm font-medium text-secondary mb-1">Group Name</label>
                                <input
                                    type="text"
                                    value={groupName}
                                    onChange={(e) => setGroupName(e.target.value)}
                                    placeholder="Enter group name"
                                    maxLength={100}
                                    autoFocus
                                    className="w-full border border-border bg-transparent px-3 py-2 text-sm text-primary placeholder:text-muted focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent rounded-md"
                                />
                            </div>
                            <div className="flex justify-end gap-3">
                                <button type="button" onClick={() => { setShowModal(false); setError(''); }} className="px-4 py-2 text-sm font-medium text-secondary hover:text-primary">
                                    Cancel
                                </button>
                                <button type="submit" disabled={creating || !groupName.trim()} className="px-4 py-2 bg-accent text-white text-sm font-medium hover:opacity-90 disabled:opacity-50 rounded-md">
                                    {creating ? 'Creating...' : 'Create'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
