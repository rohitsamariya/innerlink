import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import client from '../api/client';
import RoleSelect from '../components/RoleSelect';

export default function CreateUser() {
    const navigate = useNavigate();
    const [fullName, setFullName] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [role, setRole] = useState('EMPLOYEE');
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    const generatePassword = () => {
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        let pwd = '';
        for (let i = 0; i < 12; i++) {
            pwd += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        setPassword(pwd);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setSuccess('');

        if (!fullName.trim() || !email.trim() || !password.trim()) {
            setError('Please fill in all fields.');
            return;
        }

        setSaving(true);
        try {
            await client.post('/users', {
                full_name: fullName,
                email,
                password,
                role,
            });
            setSuccess('User created successfully!');
            setTimeout(() => navigate('/users'), 1500);
        } catch (err) {
            const msg = err.response?.data?.message || err.response?.data?.error || 'Failed to create user.';
            setError(msg);
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="flex-1 p-4 sm:p-8 overflow-y-auto">
            <div className="max-w-xl mx-auto">
                <div className="flex items-center gap-3 mb-6">
                    <button onClick={() => navigate('/users')} className="text-secondary hover:text-primary">
                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>
                    </button>
                    <h2 className="text-xl sm:text-2xl font-bold text-primary">Add User</h2>
                </div>

                {error && (
                    <div className="mb-4 text-sm text-danger bg-danger/10 border border-danger/20 rounded-md px-4 py-3">{error}</div>
                )}
                {success && (
                    <div className="mb-4 text-sm text-success bg-success/10 border border-success/20 rounded-md px-4 py-3">{success}</div>
                )}

                <div className="bg-surface border border-border rounded-lg p-6">
                    <form onSubmit={handleSubmit} className="space-y-5">
                        <div>
                            <label className="block text-sm font-medium text-secondary mb-1.5">Full Name</label>
                            <input
                                type="text"
                                value={fullName}
                                onChange={(e) => setFullName(e.target.value)}
                                placeholder="John Doe"
                                className="w-full border border-border bg-transparent px-3.5 py-2.5 text-sm text-primary placeholder:text-muted focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent rounded-md"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-secondary mb-1.5">Email Address</label>
                            <input
                                type="email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                placeholder="john@example.com"
                                className="w-full border border-border bg-transparent px-3.5 py-2.5 text-sm text-primary placeholder:text-muted focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent rounded-md"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-secondary mb-1.5">Password</label>
                            <div className="flex gap-2">
                                <input
                                    type="text"
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    placeholder="Enter or generate a password"
                                    className="flex-1 border border-border bg-transparent px-3.5 py-2.5 text-sm text-primary placeholder:text-muted focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent"
                                />
                                <button
                                    type="button"
                                    onClick={generatePassword}
                                    className="px-3 py-2.5 bg-primary/5 text-secondary text-sm font-medium border border-border hover:bg-primary/10 rounded-md"
                                >
                                    Generate
                                </button>
                            </div>
                        </div>
                        <RoleSelect
                            label="Role"
                            value={role}
                            onChange={setRole}
                        />
                        <div className="flex justify-end gap-3 pt-2">
                            <button
                                type="button"
                                onClick={() => navigate('/users')}
                                className="px-4 py-2 text-sm font-medium text-secondary border border-border hover:bg-primary/[0.03] rounded-md"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={saving}
                                className="px-5 py-2 bg-accent text-white text-sm font-medium hover:opacity-90 disabled:opacity-50 rounded-md"
                            >
                                {saving ? 'Creating...' : 'Create User'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}
