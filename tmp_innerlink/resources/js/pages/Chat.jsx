import { useEffect, useState, useCallback, useRef } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { fetchMessages, sendMessage, sendTyping, searchMessages, markMessagesRead } from '../api/messages';
import { fetchGroup } from '../api/groups';
import { useAuth } from '../context/AuthContext';
import client from '../api/client';
import { useEcho } from '../context/EchoContext';
import { formatIST, formatRelativeTime } from '../utils/formatDate';
import MessageList from '../components/MessageList';
import MessageInput from '../components/MessageInput';
import TypingIndicator from '../components/TypingIndicator';
import GroupMemberManager from '../components/GroupMemberManager';

export default function Chat() {
    const { groupId } = useParams();
    const navigate = useNavigate();
    const { user } = useAuth();
    const echo = useEcho();
    const [messages, setMessages] = useState([]);
    const [group, setGroup] = useState(null);
    const [typingUsers, setTypingUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState(null);
    const [sendError, setSendError] = useState('');
    const [downloadError, setDownloadError] = useState('');
    const [showMembers, setShowMembers] = useState(false);
    const [showSearch, setShowSearch] = useState(false);
    const [showActions, setShowActions] = useState(false);
    const actionsRef = useRef(null);
    const tempIdRef = useRef(0);

    useEffect(() => {
        const handleClick = (e) => {
            if (actionsRef.current && !actionsRef.current.contains(e.target)) {
                setShowActions(false);
            }
        };
        document.addEventListener('mousedown', handleClick);
        return () => document.removeEventListener('mousedown', handleClick);
    }, []);

    useEffect(() => {
        setLoading(true);
        Promise.all([
            fetchGroup(groupId).then((d) => setGroup(d?.data ?? d)),
            fetchMessages(groupId).then((data) => {
                const msgs = Array.isArray(data) ? data : data.data || [];
                setMessages(msgs);
                const unreadIds = msgs.filter((m) => m.sender_id !== user?.id).map((m) => m.id);
                if (unreadIds.length > 0) {
                    markMessagesRead(groupId, unreadIds).catch(() => {});
                }
            }),
        ])
            .catch(() => navigate('/groups'))
            .finally(() => setLoading(false));
    }, [groupId, navigate]);

    useEffect(() => {
        if (echo) return;
        const interval = setInterval(() => {
            fetchMessages(groupId).then((data) => {
                const msgs = Array.isArray(data) ? data : data.data || [];
                setMessages(msgs);
            }).catch(() => {});
        }, 5000);
        return () => clearInterval(interval);
    }, [echo, groupId]);

    useEffect(() => {
        if (!echo) return;
        const channel = echo.private(`groups.${groupId}`);

        channel.listen('.message.sent', (e) => {
            setMessages((prev) => [...prev, {
                id: e.id,
                group_id: e.groupId,
                sender_id: e.senderId,
                sender_name: e.senderName,
                message_text: e.messageText,
                sent_at: e.sentAt,
            }]);
            if (e.senderId !== user?.id) {
                markMessagesRead(groupId, [e.id]).catch(() => {});
            }
        });

        channel.listen('.message.read', (e) => {
            if (e.userId !== user?.id) {
                setMessages((prev) => prev.map((m) =>
                    m.id === e.messageId ? { ...m, readers_count: (m.readers_count || 0) + 1 } : m
                ));
            }
        });

        channel.listen('.typing.started', (e) => {
            if (e.userId !== user?.id) {
                setTypingUsers((prev) => {
                    if (prev.find((u) => u.userId === e.userId)) return prev;
                    return [...prev, { userId: e.userId, user_name: e.userName }];
                });
            }
        });

        channel.listen('.typing.stopped', (e) => {
            setTypingUsers((prev) => prev.filter((u) => u.userId !== e.userId));
        });

        return () => {
            echo.leave(`groups.${groupId}`);
        };
    }, [echo, groupId, user?.id]);

    const handleSend = useCallback(async (text) => {
        setSendError('');
        if (group?.is_enabled === false) {
            setSendError('This group is disabled.');
            return;
        }
        const tempId = --tempIdRef.current;
        const tempMsg = { id: tempId, sender_id: user?.id, group_id: parseInt(groupId), message_text: text, _sending: true };
        setMessages((prev) => [...prev, tempMsg]);
        try {
            const data = await sendMessage(groupId, text);
            const msg = data?.data || data;
            if (msg?.id) {
                setMessages((prev) => prev.map((m) => (m.id === tempId ? { ...msg, id: msg.id ?? m.id } : m)));
            } else {
                setMessages((prev) => prev.filter((m) => m.id !== tempId));
            }
        } catch (err) {
            setMessages((prev) => prev.filter((m) => m.id !== tempId));
            const errMsg = err.response?.data?.message || 'Failed to send message';
            setSendError(errMsg);
        }
    }, [groupId, user?.id, group]);

    const scrollRef = useRef(null);
    const bottomRef = useRef(null);
    const isNearBottomRef = useRef(true);

    const handleScroll = useCallback(() => {
        const el = scrollRef.current;
        if (!el) return;
        isNearBottomRef.current = el.scrollHeight - el.scrollTop - el.clientHeight < 100;
    }, []);

    useEffect(() => {
        if (isNearBottomRef.current) {
            bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
        }
    }, [messages]);

    const handleTyping = useCallback((action) => {
        sendTyping(groupId, action).catch(() => {});
    }, [groupId]);

    const handleSearch = async (e) => {
        e.preventDefault();
        if (!searchQuery.trim()) {
            setSearchResults(null);
            return;
        }
        try {
            const results = await searchMessages(groupId, searchQuery);
            setSearchResults(Array.isArray(results) ? results : results.data || []);
        } catch {
            setSearchResults([]);
        }
    };

    if (loading) {
        return (
            <div className="flex-1 flex items-center justify-center">
                <div className="animate-spin h-8 w-8 border-2 border-primary border-t-transparent rounded-full" />
            </div>
        );
    }

    return (
        <div className="flex-1 flex flex-col h-screen overflow-hidden">
            {downloadError && (
                <div className="px-4 py-2 bg-danger/10 border-b border-danger/20 text-sm text-danger rounded-md">
                    {downloadError}
                </div>
            )}
            {showMembers && (
                <div className="border-b border-border bg-page px-4 py-3">
                    <h4 className="text-sm font-medium text-secondary mb-2">Add User to Group</h4>
                    <GroupMemberManager groupId={groupId} />
                </div>
            )}
            {searchResults ? (
                <div className="flex-1 overflow-y-auto p-3 sm:p-4 space-y-2 sm:space-y-3">
                    <p className="text-xs sm:text-sm text-muted mb-1 sm:mb-2">Search results ({searchResults.length})</p>
                    {searchResults.length === 0 ? (
                        <p className="text-muted text-center py-8 text-sm">No messages found.</p>
                    ) : (
                        searchResults.map((msg) => (
                            <div key={msg.id} className="bg-surface border border-border rounded-lg p-3 sm:p-4">
                                <p className="text-xs font-medium text-accent mb-0.5">{msg.sender_name || 'Unknown'}</p>
                                <p className="text-sm text-primary">{msg.message_text}</p>
                                <p className="text-xs text-muted mt-1" title={formatIST(msg.sent_at || msg.created_at)}>{formatRelativeTime(msg.sent_at || msg.created_at)}</p>
                            </div>
                        ))
                    )}
                </div>
            ) : (
                <>
                    {sendError && (
                        <div className="px-4 py-2 bg-danger/10 border-b border-danger/20 text-sm text-danger rounded-md">
                            {sendError}
                        </div>
                    )}
                    <div className="flex-1 flex flex-col min-h-0">
                        <div ref={scrollRef} onScroll={handleScroll} className="flex-1 max-w-3xl w-full mx-auto min-h-0 overflow-y-auto">
                            <div className="sticky top-0 z-10 bg-header/80 backdrop-blur-sm border-b border-border">
                                <div className="flex items-center gap-2 px-4 py-3">
                                    <button onClick={() => navigate('/groups')} className="text-secondary hover:text-primary shrink-0">
                                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                                    </button>
                                    <h2 className="text-base sm:text-lg font-semibold text-primary truncate flex-1 min-w-0">{group?.name || `Group #${groupId}`}</h2>
                                    <button
                                        onClick={() => setShowSearch(!showSearch)}
                                        className={`p-1.5 shrink-0 ${showSearch ? 'text-accent' : 'text-secondary hover:text-primary'}`}
                                        title="Search messages"
                                    >
                                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5"><path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                                    </button>
                                    {user?.role === 'ADMIN' && (
                                        <div className="relative" ref={actionsRef}>
                                            <button
                                                onClick={() => setShowActions(!showActions)}
                                                className="p-1.5 text-secondary hover:text-primary shrink-0"
                                                title="More actions"
                                            >
                                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5"><path strokeLinecap="round" strokeLinejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z" /></svg>
                                            </button>
                                            {showActions && (
                                                <div className="absolute right-0 top-full mt-1 w-44 bg-surface border border-border rounded-lg py-1 z-50 shadow-lg">
                                                    <button
                                                        onClick={() => navigate(`/chat/${groupId}/settings`)}
                                                        className="w-full flex items-center gap-2 px-4 py-2 text-sm text-secondary hover:bg-primary/[0.03] text-left"
                                                    >
                                                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5"><path strokeLinecap="round" strokeLinejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /></svg>
                                                        Settings
                                                    </button>
                                                    <button
                                                        onClick={() => { setShowMembers(!showMembers); setShowActions(false); }}
                                                        className="w-full flex items-center gap-2 px-4 py-2 text-sm text-secondary hover:bg-primary/[0.03] text-left"
                                                    >
                                                        <svg className="h-4 w-4 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5"><path strokeLinecap="round" strokeLinejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" /></svg>
                                                        {showMembers ? 'Close Members' : 'Add User'}
                                                    </button>
                                                    <button
                                                        onClick={async () => {
                                                            setShowActions(false);
                                                            setDownloadError('');
                                                            try {
                                                                const response = await client.get(`/admin/groups/${groupId}/messages/download`, { responseType: 'blob' });
                                                                const url = window.URL.createObjectURL(new Blob([response.data]));
                                                                const link = document.createElement('a');
                                                                link.href = url;
                                                                link.setAttribute('download', `group-${groupId}-messages.csv`);
                                                                document.body.appendChild(link);
                                                                link.click();
                                                                link.remove();
                                                                window.URL.revokeObjectURL(url);
                                                            } catch (err) {
                                                                const msg = err.response?.status === 403 ? 'You do not have permission to download messages.' : err.response?.data?.message || err.message || 'Download failed';
                                                                setDownloadError(msg);
                                                            }
                                                        }}
                                                        className="w-full flex items-center gap-2 px-4 py-2 text-sm text-secondary hover:bg-primary/[0.03] text-left"
                                                    >
                                                        <svg className="h-4 w-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5"><path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                                        Download
                                                    </button>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                                {showSearch && (
                                    <div className="px-4 pb-3">
                                        <form onSubmit={handleSearch} className="flex gap-1.5">
                                            <input
                                                type="text"
                                                value={searchQuery}
                                                onChange={(e) => setSearchQuery(e.target.value)}
                                                placeholder="Search messages..."
                                                className="flex-1 border border-border bg-transparent px-3 py-2 text-sm text-primary placeholder:text-muted focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent rounded-md"
                                                autoFocus
                                            />
                                            <button type="submit" className="px-3 py-2 bg-primary/5 text-secondary text-sm font-medium hover:bg-primary/10 rounded-md shrink-0">Search</button>
                                            {searchResults && (
                                                <button type="button" onClick={() => { setSearchResults(null); setSearchQuery(''); setShowSearch(false); }} className="px-3 py-2 bg-danger/10 text-danger text-sm font-medium hover:bg-danger/20 rounded-md shrink-0">Clear</button>
                                            )}
                                        </form>
                                    </div>
                                )}
                            </div>
                            <MessageList messages={messages} groupId={groupId} />
                            <div ref={bottomRef} />
                        </div>
                        <div className="max-w-3xl w-full mx-auto">
                            <TypingIndicator typingUsers={typingUsers} />
                            {group?.is_enabled === false ? (
                                <div className="border-t border-border p-4 bg-page text-center text-sm text-muted font-medium">
                                    This group has been disabled.
                                </div>
                            ) : (
                                <MessageInput onSend={handleSend} onTyping={handleTyping} />
                            )}
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}
