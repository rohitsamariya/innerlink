import { useEffect, useState } from 'react';
import { fetchUsers, toggleUserStatus, updateUser } from '../api/admin';
import { formatIST, formatRelativeTime, isUserOnline } from '../utils/formatDate';
import Skeleton from './Skeleton';
import RoleSelect from './RoleSelect';

export default function AdminUserList() {
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [editing, setEditing] = useState(null);
    const [editName, setEditName] = useState('');
    const [editEmail, setEditEmail] = useState('');
    const [editRole, setEditRole] = useState('');
    const [editPassword, setEditPassword] = useState('');
    const [saving, setSaving] = useState(false);
    const [editError, setEditError] = useState('');
    const [resetModal, setResetModal] = useState(null);
    const [newPassword, setNewPassword] = useState('');
    const [resetSaving, setResetSaving] = useState(false);
    const [resetError, setResetError] = useState('');
    const [resetDone, setResetDone] = useState('');

    const loadUsers = () => {
        setLoading(true);
        fetchUsers()
            .then((data) => setUsers(Array.isArray(data) ? data : data.data || []))
            .catch(() => {})
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        loadUsers();
    }, []);

    const handleToggle = async (userId) => {
        try {
            await toggleUserStatus(userId);
            loadUsers();
        } catch {
            // silently fail
        }
    };

    const openEdit = (u) => {
        setEditing(u);
        setEditName(u.full_name || '');
        setEditEmail(u.email || '');
        setEditRole(u.role || 'EMPLOYEE');
        setEditPassword('');
        setEditError('');
    };

    const closeEdit = () => {
        setEditing(null);
        setEditPassword('');
        setEditError('');
    };

    const handleSave = async (e) => {
        e.preventDefault();
        setEditError('');
        setSaving(true);
        try {
            const payload = { full_name: editName, email: editEmail, role: editRole };
            if (editPassword.trim()) {
                payload.password = editPassword;
            }
            await updateUser(editing.id, payload);
            closeEdit();
            loadUsers();
        } catch (err) {
            const msg = err.response?.data?.message || err.response?.data?.error || 'Failed to update user.';
            setEditError(msg);
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return <div className="space-y-3"><Skeleton className="h-12 w-full" count={8} /></div>;
    }

    return (
        <>
            {/* Desktop table */}
            <div className="hidden md:block bg-surface border border-border rounded-lg overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-border">
                        <thead className="bg-primary/[0.02]">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-muted uppercase">ID</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-muted uppercase">Name</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-muted uppercase">Email</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-muted uppercase">Role</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-muted uppercase">Last Seen</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-muted uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {users.map((u) => (
                                <tr key={u.id}>
                                    <td className="px-6 py-4 text-sm text-primary whitespace-nowrap">#{u.id}</td>
                                    <td className="px-6 py-4 text-sm font-medium text-primary whitespace-nowrap">{u.full_name}</td>
                                    <td className="px-6 py-4 text-sm text-secondary whitespace-nowrap">{u.email}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-md ${u.role === 'ADMIN' ? 'bg-accent/10 text-accent' : u.role === 'MANAGER' ? 'bg-accent/10 text-accent' : 'bg-primary/5 text-secondary'}`}>
                                            {u.role}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-sm text-secondary whitespace-nowrap">
                                        {isUserOnline(u) ? (
                                            <span className="inline-flex items-center gap-1.5 text-success font-medium">
                                                <span className="h-2 w-2 bg-success inline-block rounded-full" />
                                                Online
                                            </span>
                                        ) : u.last_seen_at ? (
                                            <span className="inline-flex items-center gap-1 text-muted" title={formatIST(u.last_seen_at)}>
                                                <svg className="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5"><path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                {formatRelativeTime(u.last_seen_at)}
                                            </span>
                                        ) : '-'}
                                    </td>
                                    <td className="px-6 py-4 text-right whitespace-nowrap">
                                        <div className="flex items-center justify-end gap-2">
                                            <button
                                                onClick={() => handleToggle(u.id)}
                                                className={`inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold border transition-colors rounded-md ${
                                                    u.is_enabled
                                                        ? 'bg-success/10 text-success border-success/30 hover:bg-success/20'
                                                        : 'bg-danger/10 text-danger border-danger/30 hover:bg-danger/20'
                                                }`}
                                            >
                                                {u.is_enabled ? 'Enabled' : 'Disabled'}
                                            </button>
                                            <button
                                                onClick={() => { setResetModal(u); setNewPassword(''); setResetDone(''); setResetError(''); }}
                                                className="px-3 py-1 text-xs font-medium text-warning bg-warning/10 hover:bg-warning/20 rounded-md"
                                            >
                                                Reset
                                            </button>
                                            <button
                                                onClick={() => openEdit(u)}
                                                className="px-3 py-1 text-xs font-medium text-accent bg-accent/10 hover:bg-accent/20 rounded-md"
                                            >
                                                Edit
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Mobile cards */}
            <div className="md:hidden space-y-3">
                {users.map((u) => (
                    <div key={u.id} className="bg-surface border border-border rounded-lg p-4">
                        <div className="flex items-center gap-2 mb-2">
                            <p className="text-sm font-semibold text-primary truncate flex-1 min-w-0">{u.full_name}</p>
                            <span className={`shrink-0 inline-flex px-2 py-0.5 text-xs font-medium rounded-md ${u.role === 'ADMIN' ? 'bg-accent/10 text-accent' : u.role === 'MANAGER' ? 'bg-accent/10 text-accent' : 'bg-primary/5 text-secondary'}`}>
                                {u.role}
                            </span>
                        </div>
                        <p className="text-xs text-secondary truncate mb-3">{u.email}</p>
                        <div className="flex items-center gap-1.5 flex-wrap">
                            <button
                                onClick={() => handleToggle(u.id)}
                                className={`inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium border transition-colors rounded-md ${
                                    u.is_enabled
                                        ? 'bg-success/10 text-success border-success/30'
                                        : 'bg-danger/10 text-danger border-danger/30'
                                }`}
                            >
                                {u.is_enabled ? 'Enabled' : 'Disabled'}
                            </button>
                            <button
                                onClick={() => { setResetModal(u); setNewPassword(''); setResetDone(''); setResetError(''); }}
                                className="px-2.5 py-1 text-xs font-medium text-warning bg-warning/10 hover:bg-warning/20 rounded-md"
                            >
                                Reset
                            </button>
                            <button
                                onClick={() => openEdit(u)}
                                className="px-2.5 py-1 text-xs font-medium text-accent bg-accent/10 hover:bg-accent/20 rounded-md"
                            >
                                Edit
                            </button>
                            {isUserOnline(u) ? (
                                <span className="inline-flex items-center gap-1 text-xs text-success font-medium ml-1">
                                    <span className="h-1.5 w-1.5 bg-success inline-block rounded-full" />
                                    Online
                                </span>
                            ) : u.last_seen_at ? (
                                <span className="inline-flex items-center gap-1 text-xs text-muted ml-1" title={formatIST(u.last_seen_at)}>
                                    <svg className="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5"><path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {formatRelativeTime(u.last_seen_at)}
                                </span>
                            ) : null}
                        </div>
                    </div>
                ))}
            </div>

            {resetModal && (
                <div className="fixed inset-0 bg-black/50 flex items-end sm:items-center justify-center z-50">
                    <div className="bg-surface border border-border rounded-xl p-6 w-full sm:max-w-sm sm:mx-4 max-h-[90vh] overflow-y-auto">
                        <h3 className="text-lg font-semibold text-primary mb-1">Reset Password</h3>
                        <p className="text-sm text-secondary mb-4">User: <strong>{resetModal.full_name}</strong></p>

                        {resetError && (
                            <div className="mb-4 text-sm text-danger bg-danger/10 border border-danger/20 rounded-md px-4 py-3">{resetError}</div>
                        )}

                        {resetDone ? (
                            <div>
                                <div className="mb-4 text-sm text-success bg-success/10 border border-success/20 rounded-md px-4 py-3">
                                    Password reset successfully.
                                </div>
                                <div className="bg-primary/[0.02] border border-border rounded-lg p-4 mb-4 text-center">
                                    <p className="text-xs text-muted mb-1">New Password</p>
                                    <p className="text-lg font-mono font-bold text-primary select-all">{resetDone}</p>
                                </div>
                                <button
                                    onClick={() => setResetModal(null)}
                                    className="w-full px-4 py-2 bg-accent text-white text-sm font-medium hover:opacity-90 rounded-md"
                                >
                                    Close
                                </button>
                            </div>
                        ) : (
                            <form onSubmit={async (e) => {
                                e.preventDefault();
                                setResetError('');
                                if (!newPassword.trim() || newPassword.length < 12 || !/[A-Z]/.test(newPassword) || !/[a-z]/.test(newPassword) || !/[0-9]/.test(newPassword)) {
                                    setResetError('Password must be at least 12 characters with upper, lower, and a digit.');
                                    return;
                                }
                                setResetSaving(true);
                                try {
                                    await updateUser(resetModal.id, { password: newPassword });
                                    setResetDone(newPassword);
                                } catch (err) {
                                    const msg = err.response?.data?.message || 'Failed to reset password.';
                                    setResetError(msg);
                                } finally {
                                    setResetSaving(false);
                                }
                            }} className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-secondary mb-1">New Password</label>
                                    <input
                                        type="text"
                                        value={newPassword}
                                        onChange={(e) => setNewPassword(e.target.value)}
                                        placeholder="Enter new password"
                                        className="w-full border border-border bg-transparent px-3 py-2 text-sm text-primary placeholder:text-muted focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent rounded-md"
                                        autoFocus
                                    />
                                </div>
                                <div className="flex justify-end gap-3">
                                    <button
                                        type="button"
                                        onClick={() => setResetModal(null)}
                                        className="px-4 py-2 text-sm font-medium text-secondary hover:text-primary"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={resetSaving}
className="px-4 py-2 bg-accent text-white text-sm font-medium hover:opacity-90 disabled:opacity-50 rounded-md"
                                    >
                                        {resetSaving ? 'Resetting...' : 'Reset Password'}
                                    </button>
                                </div>
                            </form>
                        )}
                    </div>
                </div>
            )}

            {editing && (
                <div className="fixed inset-0 bg-black/50 flex items-end sm:items-center justify-center z-50">
                    <div className="bg-surface border border-border rounded-xl p-6 w-full sm:max-w-md sm:mx-4 max-h-[90vh] overflow-y-auto">
                        <h3 className="text-lg font-semibold text-primary mb-4">Edit User</h3>

                        {editError && (
                            <div className="mb-4 text-sm text-danger bg-danger/10 border border-danger/20 rounded-md px-4 py-3">{editError}</div>
                        )}

                        <form onSubmit={handleSave} className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-secondary mb-1">Name</label>
                                <input
                                    type="text"
                                    value={editName}
                                    onChange={(e) => setEditName(e.target.value)}
                                    className="w-full border border-border bg-transparent px-3 py-2 text-sm text-primary focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent rounded-md"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-secondary mb-1">Email</label>
                                <input
                                    type="email"
                                    value={editEmail}
                                    onChange={(e) => setEditEmail(e.target.value)}
                                    className="w-full border border-border bg-transparent px-3 py-2 text-sm text-primary focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent rounded-md"
                                />
                            </div>
                            <RoleSelect
                                label="Role"
                                value={editRole}
                                onChange={setEditRole}
                                includeAdmin={editing?.role === 'ADMIN'}
                            />
                            <div>
                                <label className="block text-sm font-medium text-secondary mb-1">
                                    Password <span className="text-muted font-normal">(leave blank to keep current)</span>
                                </label>
                                <input
                                    type="text"
                                    value={editPassword}
                                    onChange={(e) => setEditPassword(e.target.value)}
                                    placeholder="New password"
                                    className="w-full border border-border bg-transparent px-3 py-2 text-sm text-primary placeholder:text-muted focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent rounded-md"
                                />
                            </div>
                            <div className="flex justify-end gap-3 pt-2">
                                <button
                                    type="button"
                                    onClick={closeEdit}
                                    className="px-4 py-2 text-sm font-medium text-secondary hover:text-primary"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={saving}
                                    className="px-4 py-2 bg-accent text-white text-sm font-medium hover:opacity-90 disabled:opacity-50 rounded-md"
                                >
                                    {saving ? 'Saving...' : 'Save'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </>
    );
}
