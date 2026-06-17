export default function TypingIndicator({ typingUsers }) {
    if (!typingUsers?.length) return null;

    const names = typingUsers.map((u) => u.user_name || u.full_name).join(', ');
    const suffix = typingUsers.length === 1 ? 'is' : 'are';

    return (
        <div className="px-4 py-1 text-xs text-muted italic">
            {names} {suffix} typing...
        </div>
    );
}
