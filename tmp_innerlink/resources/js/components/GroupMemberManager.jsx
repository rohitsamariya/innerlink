import { useEffect, useState } from 'react';
import { fetchUsers, fetchGroupMembers, addGroupMember, removeGroupMember } from '../api/admin';
import { formatRelativeTime, isUserOnline } from '../utils/formatDate';

export default function GroupMemberManager({ groupId }) {
    const [members, setMembers] = useState([]);
    const [allUsers, setAllUsers] = useState([]);
    const [selectedUserId, setSelectedUserId] = useState('');
    const [loading, setLoading] = useState(true);
    const [message, setMessage] = useState({ type: '', text: '' });

    const load = async () => {
        setLoading(true);
        try {
            const [membersData, usersData] = await Promise.all([
                fetchGroupMembers(groupId),
                fetchUsers(),
            ]);
            setMembers(Array.isArray(membersData) ? membersData : membersData.data || []);
            setAllUsers(Array.isArray(usersData) ? usersData : usersData.data || []);
        } catch {
            setMessage({ type: 'error', text: 'Failed to load data.' });
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => { load(); }, [groupId]);

    const memberIds = new Set(members.map((m) => m.id));
    const availableUsers = allUsers.filter((u) => !memberIds.has(u.id));

    const handleAdd = async () => {
        if (!selectedUserId) return;
        setMessage({ type: '', text: '' });
        try {
            await addGroupMember(groupId, parseInt(selectedUserId));
            setMessage({ type: 'success', text: 'User added to group.' });
            setSelectedUserId('');
            await load();
        } catch (err) {
            setMessage({ type: 'error', text: err.response?.data?.message || 'Failed to add user.' });
        }
    };

    const handleRemove = async (userId) => {
        if (!confirm('Remove this user from the group?')) return;
        setMessage({ type: '', text: '' });
        try {
            await removeGroupMember(groupId, userId);
            setMessage({ type: 'success', text: 'User removed.' });
            await load();
        } catch (err) {
            setMessage({ type: 'error', text: err.response?.data?.message || 'Failed to remove user.' });
        }
    };

    if (loading) {
        return <div className="text-sm text-muted py-2">Loading...</div>;
    }

    return (
        <div>
            {message.text && (
                <div className={`mb-3 px-3 py-2 text-sm rounded-md ${message.type === 'error' ? 'bg-danger/10 text-danger' : 'bg-success/10 text-success'}`}>
                    {message.text}
                </div>
            )}

            <div className="flex gap-2 mb-4">
                <select
                    value={selectedUserId}
                    onChange={(e) => setSelectedUserId(e.target.value)}
                    className="flex-1 border border-border bg-surface pl-3 pr-10 py-2 text-sm text-primary focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent rounded-md"
                >
                    <option value="">Select a user...</option>
                    {availableUsers.map((u) => (
                        <option key={u.id} value={u.id}>{u.full_name} ({u.email}){isUserOnline(u) ? ' — Online' : ''}</option>
                    ))}
                </select>
                <button
                    onClick={handleAdd}
                    disabled={!selectedUserId}
                    className="px-4 py-2 bg-accent text-white text-sm font-medium hover:opacity-90 disabled:opacity-50 rounded-md"
                >
                    Add
                </button>
            </div>

            <details className="text-sm">
                <summary className="text-secondary cursor-pointer hover:text-primary">
                    Current members ({members.length})
                </summary>
                <div className="mt-2 space-y-1">
                    {members.map((u) => (
                        <div key={u.id} className="flex items-center justify-between py-1 px-2 hover:bg-primary/[0.02]">
                            <span className="flex items-center gap-1.5 min-w-0">
                                {isUserOnline(u) && (
                                    <span className="h-1.5 w-1.5 bg-success rounded-full shrink-0" />
                                )}
                                <span className="text-primary truncate">{u.full_name}</span>
                                <span className="text-muted shrink-0">({u.email})</span>
                                {isUserOnline(u) ? (
                                    <span className="text-xs text-success font-medium shrink-0">Online</span>
                                ) : u.last_seen_at ? (
                                    <span className="text-xs text-muted shrink-0" title={formatRelativeTime(u.last_seen_at)}>
                                        {formatRelativeTime(u.last_seen_at)}
                                    </span>
                                ) : null}
                            </span>
                            <button onClick={() => handleRemove(u.id)} className="text-xs text-danger hover:text-danger/80 font-medium shrink-0 ml-2">
                                Remove
                            </button>
                        </div>
                    ))}
                    {members.length === 0 && (
                        <p className="text-muted">No members yet.</p>
                    )}
                </div>
            </details>
        </div>
    );
}
