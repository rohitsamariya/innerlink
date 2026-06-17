import { useRef, useEffect, useState } from 'react';
import { useAuth } from '../context/AuthContext';
import { formatIST, formatISTTime, formatRelativeTime, isUserOnline } from '../utils/formatDate';
import SeenByPopup from './SeenByPopup';

export default function MessageList({ messages, readersMap, groupId }) {
    const { user } = useAuth();
    const bottomRef = useRef(null);
    const [seenMessageId, setSeenMessageId] = useState(null);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    if (!messages?.length) {
        return (
            <div className="flex-1 flex items-center justify-center text-muted">
                No messages yet. Start the conversation!
            </div>
        );
    }

    return (
        <div className="flex-1 overflow-y-auto p-3 sm:p-4 space-y-2 sm:space-y-3">
            {messages.map((msg) => {
                const isMine = msg.sender_id === user?.id;
                const readerCount = readersMap?.[msg.id] ?? msg.readers_count ?? 0;
                return (
                    <div key={msg.id} className={`flex ${isMine ? 'justify-end' : 'justify-start'}`}>
                        <div className={`max-w-[85%] sm:max-w-[70%] rounded-lg px-3 sm:px-4 py-2 ${isMine ? 'bg-bubble-mine text-bubble-mine-text' : 'bg-bubble-other text-bubble-other-text'}`}>
                            {!isMine && (
                                <p className="text-xs font-medium text-accent mb-1 flex items-center gap-1">
                                    <span>{msg.sender_name || 'Unknown'}</span>
                                    {isUserOnline({ presence_status: msg.sender_presence_status, last_seen_at: msg.sender_last_seen_at }) ? (
                                        <span className="inline-flex items-center gap-1 text-xs text-success font-normal">
                                            <span className="h-1.5 w-1.5 bg-success rounded-full" />
                                            Online
                                        </span>
                                    ) : msg.sender_last_seen_at ? (
                                        <span className="inline-flex items-center gap-1 text-muted font-normal" title={formatIST(msg.sender_last_seen_at)}>
                                            <svg className="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5"><path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            {formatRelativeTime(msg.sender_last_seen_at)}
                                        </span>
                                    ) : null}
                                </p>
                            )}
                            <p className="text-sm whitespace-pre-wrap break-words">{msg.message_text}</p>
                            <div className={`flex items-center gap-2 mt-1 ${isMine ? 'text-bubble-mine-muted' : 'text-bubble-other-muted'}`}>
                                {msg._sending ? (
                                    <p className="text-xs italic opacity-70">Sending...</p>
                                ) : (
                                    <p className="text-xs">
                                        {formatISTTime(msg.sent_at || msg.created_at)}
                                    </p>
                                )}
                                {isMine && readerCount > 0 && (
                                    <button
                                        onClick={() => setSeenMessageId(msg.id)}
                                        className="text-xs flex items-center gap-0.5 hover:text-bubble-mine-text/80 transition-colors"
                                    >
                                        <svg className="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5"><path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        {readerCount}
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>
                );
            })}
            <div ref={bottomRef} />
            {seenMessageId && groupId && (
                <SeenByPopup
                    groupId={groupId}
                    messageId={seenMessageId}
                    onClose={() => setSeenMessageId(null)}
                />
            )}
        </div>
    );
}
