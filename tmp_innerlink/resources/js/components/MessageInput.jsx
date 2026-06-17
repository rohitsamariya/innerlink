import { useState, useRef, useCallback } from 'react';

export default function MessageInput({ onSend, onTyping }) {
    const [text, setText] = useState('');
    const typingTimeoutRef = useRef(null);
    const isTypingRef = useRef(false);

    const handleTyping = useCallback(() => {
        if (!isTypingRef.current) {
            isTypingRef.current = true;
            onTyping?.('started');
        }
        clearTimeout(typingTimeoutRef.current);
        typingTimeoutRef.current = setTimeout(() => {
            isTypingRef.current = false;
            onTyping?.('stopped');
        }, 2000);
    }, [onTyping]);

    const handleSubmit = (e) => {
        e.preventDefault();
        const trimmed = text.trim();
        if (!trimmed) return;
        onSend(trimmed);
        setText('');
        clearTimeout(typingTimeoutRef.current);
        if (isTypingRef.current) {
            isTypingRef.current = false;
            onTyping?.('stopped');
        }
    };

    return (
        <form onSubmit={handleSubmit} className="border-t border-border p-3 sm:p-4 bg-surface">
            <div className="flex gap-2 items-center">
                <input
                    type="text"
                    value={text}
                    onChange={(e) => { setText(e.target.value); handleTyping(); }}
                    placeholder="Type a message..."
                    maxLength={10000}
                    className="flex-1 border border-border bg-transparent px-3 sm:px-4 py-2.5 sm:py-2 text-[16px] text-primary placeholder:text-muted focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent rounded-md"
                />
                {text.trim() && (
                    <button
                        type="submit"
                        className="shrink-0 w-9 h-9 sm:w-10 sm:h-10 flex items-center justify-center bg-accent text-white rounded-full hover:opacity-90 transition-opacity"
                    >
                        <svg className="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 19.5v-15m0 0l-7 7m7-7l7 7" />
                        </svg>
                    </button>
                )}
            </div>
        </form>
    );
}
