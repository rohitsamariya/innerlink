import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { fetchPrivateContacts } from '../api/privateMessages';
import { useAuth } from '../context/AuthContext';

export default function ComposeModal({ onClose }) {
    const { user } = useAuth();
    const navigate = useNavigate();
    const [contacts, setContacts] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchPrivateContacts()
            .then((data) => setContacts(Array.isArray(data) ? data : data.data || []))
            .catch(() => {})
            .finally(() => setLoading(false));
    }, []);

    const handleSelect = (contactId) => {
        navigate(`/private-chat/${contactId}`);
        onClose();
    };

    return (
        <div className="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50" onClick={onClose}>
            <div className="bg-surface border border-border rounded-xl max-w-md w-full sm:mx-4 max-h-[90vh] overflow-y-auto" onClick={(e) => e.stopPropagation()}>
                <div className="flex items-center justify-between px-4 py-3 border-b border-border">
                    <h3 className="text-sm font-semibold text-primary">New Message</h3>
                    <button onClick={onClose} className="text-muted hover:text-primary text-lg leading-none">&times;</button>
                </div>
                <div className="p-4 max-h-80 overflow-y-auto">
                    {loading ? (
                        <p className="text-sm text-muted text-center py-4">Loading...</p>
                    ) : contacts.length === 0 ? (
                        <p className="text-sm text-muted text-center py-4">No contacts available.</p>
                    ) : (
                        <ul className="space-y-1">
                            {contacts.map((c) => (
                                <li key={c.id}>
                                    <button
                                        onClick={() => handleSelect(c.id)}
                                        className="w-full flex items-center gap-3 px-3 py-2 hover:bg-accent/5 text-left transition-colors"
                                    >
                                        <div className="w-8 h-8 bg-accent/10 flex items-center justify-center text-accent text-sm font-medium rounded-lg">
                                            {c.full_name?.charAt(0)?.toUpperCase()}
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium text-primary">{c.full_name}</p>
                                            <p className="text-xs text-secondary">{c.email}</p>
                                        </div>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </div>
    );
}
