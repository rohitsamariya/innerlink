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
            <div className="flex gap-2">
                <input
                    type="text"
                    value={text}
                    onChange={(e) => { setText(e.target.value); handleTyping(); }}
                    placeholder="Type a message..."
                    maxLength={10000}
                    className="flex-1 border border-border bg-transparent px-3 sm:px-4 py-2.5 sm:py-2 text-sm text-primary placeholder:text-muted focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent rounded-md"
                />
                <button
                    type="submit"
                    disabled={!text.trim()}
                    className="px-4 sm:px-5 py-2.5 sm:py-2 bg-accent text-white text-sm font-medium hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed rounded-md"
                >
                    Send
                </button>
            </div>
        </form>
    );
}
