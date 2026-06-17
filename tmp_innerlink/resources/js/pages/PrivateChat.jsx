import { useCallback, useEffect, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { fetchPrivateMessages, sendPrivateMessage, markPrivateMessagesRead } from '../api/privateMessages';
import client from '../api/client';
import { useAuth } from '../context/AuthContext';
import { useEcho } from '../context/EchoContext';
import { formatIST, formatISTTime, formatRelativeTime, isUserOnline } from '../utils/formatDate';

export default function PrivateChat() {
    const { userId } = useParams();
    const navigate = useNavigate();
    const { user } = useAuth();
    const echo = useEcho();

    const [messages, setMessages] = useState([]);
    const [otherUser, setOtherUser] = useState(null);
    const [loading, setLoading] = useState(true);
    const [text, setText] = useState('');
    const [error, setError] = useState('');

    const listRef = useRef(null);
    const isNearBottomRef = useRef(true);
    const tempIdRef = useRef(0);
    const [expandedRead, setExpandedRead] = useState({});

    const handleScroll = useCallback(() => {
        const el = listRef.current;
        if (!el) return;
        isNearBottomRef.current = el.scrollHeight - el.scrollTop - el.clientHeight < 100;
    }, []);

    useEffect(() => {
        setLoading(true);
        Promise.all([
            client.get(`/private-messages/contact/${userId}`).then((res) => {
                setOtherUser(res.data?.data ?? res.data);
            }),
            fetchPrivateMessages(userId).then((data) => {
                const msgs = Array.isArray(data) ? data : data.data || [];
                setMessages(msgs);
                markPrivateMessagesRead(userId).catch(() => {});
            }),
        ]).catch(() => navigate('/chats')).finally(() => setLoading(false));
    }, [userId, navigate]);

    useEffect(() => {
        if (isNearBottomRef.current) {
            listRef.current?.lastElementChild?.scrollIntoView({ behavior: 'smooth' });
        }
    }, [messages]);

    useEffect(() => {
        if (echo) return;
        const interval = setInterval(() => {
            fetchPrivateMessages(userId).then((data) => {
                const msgs = Array.isArray(data) ? data : data.data || [];
                setMessages(msgs);
            }).catch(() => {});
        }, 5000);
        return () => clearInterval(interval);
    }, [echo, userId]);

    useEffect(() => {
        if (!echo) return;
        const channel = echo.private(`users.${user?.id}`);

        channel.listen('.private.message.sent', (e) => {
            if (String(e.sender_id) === String(userId)) {
                setMessages((prev) => [...prev, {
                    id: e.id,
                    sender_id: e.sender_id,
                    receiver_id: e.receiver_id,
                    sender_name: e.sender_name,
                    message_text: e.message_text,
                    sent_at: e.sent_at,
                }]);
            }
        });

        channel.listen('.private.message.read', (e) => {
            setMessages((prev) =>
                prev.map((msg) =>
                    msg.sender_id === user?.id && String(e.reader_id) === String(msg.receiver_id)
                        ? { ...msg, read_at: e.read_at }
                        : msg
                )
            );
        });

        return () => {
            channel.stopListening('.private.message.sent');
            channel.stopListening('.private.message.read');
        };
    }, [echo, userId, user?.id]);

    const handleSend = useCallback(async () => {
        const trimmed = text.trim();
        if (!trimmed) return;
        setError('');
        const tempId = --tempIdRef.current;
        const tempMsg = { id: tempId, sender_id: user?.id, receiver_id: parseInt(userId), message_text: trimmed, _sending: true };
        setMessages((prev) => [...prev, tempMsg]);
        setText('');
        try {
            const msg = await sendPrivateMessage(userId, trimmed);
            if (msg) {
                setMessages((prev) => prev.map((m) => (m.id === tempId ? { ...msg, id: msg.id ?? m.id } : m)));
            } else {
                setMessages((prev) => prev.filter((m) => m.id !== tempId));
            }
        } catch {
            setMessages((prev) => prev.filter((m) => m.id !== tempId));
            setError('Failed to send message.');
        }
    }, [text, userId, user?.id]);

    const handleKeyDown = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSend();
        }
    };

    const isOwn = (senderId) => String(senderId) === String(user?.id);

    return (
        <div className="flex-1 flex flex-col h-screen overflow-hidden">
            {error && (
                <div className="px-4 py-2 bg-danger/10 border-b border-danger/20 text-sm text-danger rounded-md">{error}</div>
            )}

            <div className="flex-1 flex flex-col min-h-0">
                <div className="flex-1 max-w-3xl w-full mx-auto min-h-0 flex flex-col">
                    <div className="sticky top-0 z-10 flex items-center gap-3 px-4 py-3 bg-header/80 backdrop-blur-sm border-b border-border">
                        <button onClick={() => navigate('/chats')} className="text-secondary hover:text-primary shrink-0">
                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                            </svg>
                        </button>
                        <div className="min-w-0 flex-1">
                            <h2 className="text-base sm:text-lg font-semibold text-primary truncate">{otherUser?.full_name || 'Private Chat'}</h2>
                            <p className="text-xs text-secondary truncate">
                                {isUserOnline(otherUser) ? (
                                    <span className="flex items-center gap-1 text-success font-medium">
                                        <span className="h-1.5 w-1.5 bg-success rounded-full" />
                                        Online
                                    </span>
                                ) : otherUser?.last_seen_at ? (
                                    <span className="inline-flex items-center gap-1 text-muted" title={formatIST(otherUser.last_seen_at)}>
                                        <svg className="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5"><path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        {formatRelativeTime(otherUser.last_seen_at)}
                                    </span>
                                ) : null}
                            </p>
                        </div>
                    </div>
                    <div className="flex-1 overflow-y-auto p-4 space-y-3" ref={listRef} onScroll={handleScroll}>
                    {loading ? (
                        <div className="flex items-center justify-center h-full">
                            <div className="animate-spin h-8 w-8 border-2 border-primary border-t-transparent rounded-full" />
                        </div>
                    ) : messages.length === 0 ? (
                        <div className="flex items-center justify-center h-full text-muted text-sm">
                            No messages yet. Say hello!
                        </div>
                    ) : (
                        messages.map((msg) => (
                            <div key={msg.id} className={`flex ${isOwn(msg.sender_id) ? 'justify-end' : 'justify-start'}`}>
                                <div className={`max-w-[85%] sm:max-w-[70%] rounded-lg px-3 sm:px-4 py-2 ${isOwn(msg.sender_id) ? 'bg-bubble-mine text-bubble-mine-text' : 'bg-bubble-other text-bubble-other-text'}`}>
                                    {!isOwn(msg.sender_id) && (
                                        <p className="text-xs font-medium text-accent mb-1">{msg.sender_name || 'Unknown'}</p>
                                    )}
                                    <p className="text-sm whitespace-pre-wrap break-words">{msg.message_text}</p>
                                    <div className={`flex items-center gap-1 mt-1 ${isOwn(msg.sender_id) ? 'text-bubble-mine-muted' : 'text-bubble-other-muted'}`}>
                                        {msg._sending ? (
                                            <span className="text-xs italic opacity-70">Sending...</span>
                                        ) : (
                                            <span className="text-xs">{formatISTTime(msg.sent_at)}</span>
                                        )}
                                        {isOwn(msg.sender_id) && msg.read_at && (
                                            <button
                                                onClick={() => setExpandedRead((prev) => ({ ...prev, [msg.id]: !prev[msg.id] }))}
                                                className="text-xs flex items-center gap-0.5 hover:text-bubble-mine-muted/80 transition-colors"
                                            >
                                                {expandedRead[msg.id] ? (
                                                    <span>{formatIST(msg.read_at)}</span>
                                                ) : (
                                                    <>
                                                        <svg className="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                        </svg>
                                                        Seen
                                                    </>
                                                )}
                                            </button>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))
                    )}
                </div>
                </div>

                <div className="max-w-3xl w-full mx-auto">
                    <div className="border-t border-border p-4 flex gap-2 bg-surface">
                        <input
                            type="text"
                            value={text}
                            onChange={(e) => setText(e.target.value)}
                            onKeyDown={handleKeyDown}
                            placeholder="Type a message..."
                            maxLength={10000}
                            className="flex-1 border border-border bg-transparent px-4 py-2 text-sm text-primary placeholder:text-muted focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent rounded-md"
                        />
                        <button
                            onClick={handleSend}
                            disabled={!text.trim()}
                            className="px-4 py-2 bg-accent text-white text-sm font-medium hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed rounded-md"
                        >
                            Send
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
